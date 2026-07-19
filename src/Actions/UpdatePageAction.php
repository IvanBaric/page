<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use IvanBaric\Corexis\Concerns\UsesOptimisticLocking;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\MergesTranslatableAttributes;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Events\PageUpdated;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Rules\NavigationUrl;
use IvanBaric\Pages\Support\PageHierarchy;

final class UpdatePageAction
{
    use AuthorizesPageActions, MergesTranslatableAttributes, ResolvesPageModels, UsesOptimisticLocking;

    public function __construct(private readonly PageHierarchy $hierarchy) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Page|string $page, array $data): ActionResult
    {
        $page = $this->resolvePage($page);

        if (! $page) {
            return ActionResult::error(__('Stranica nije pronađena.'));
        }

        if ($result = $this->authorizePageAction('pages.update', $page)) {
            return $result;
        }

        if (($data['is_home'] ?? $page->is_home) === true) {
            $data['navigation_type'] = 'page';
            $data['navigation_url'] = null;
            $data['navigation_target'] = '_self';
        }

        $validator = Validator::make($data, $this->rules(), attributes: $this->attributes());

        if ($validator->fails()) {
            return ActionResult::error(__('Stranicu nije moguće ažurirati.'), errors: $validator->errors()->toArray());
        }

        $data = $this->mergeTranslatableAttributes(
            $page,
            $validator->validated(),
            ['title', 'excerpt', 'content'],
        );
        $expectedLockVersion = $this->pullExpectedLockVersion($data);
        $originalParentId = $page->parent_id;
        $parentChanged = false;

        if (($data['is_home'] ?? false) && $this->homeExists($page)) {
            return ActionResult::error(__('Naslovnica već postoji.'));
        }

        if (array_key_exists('parent_uuid', $data)) {
            $parentUuid = $data['parent_uuid'];
            unset($data['parent_uuid']);

            if (($data['is_home'] ?? $page->is_home) === true) {
                $parentUuid = null;
            }

            if ($parentUuid !== null) {
                $parent = $page->newQuery()
                    ->forTenant((int) $page->getAttribute('team_id'))
                    ->where('uuid', (string) $parentUuid)
                    ->whereKeyNot($page->getKey())
                    ->where('is_home', false)
                    ->first();

                if (! $parent) {
                    return ActionResult::error(__('Odabrana nadređena stranica nije dostupna.'));
                }

                if (! $this->hierarchy->canMoveUnder($page, $parent)) {
                    return ActionResult::error(__('Odabrano mjesto prelazi dopuštene :count razine ili pripada podstablu ove stranice.', ['count' => $this->hierarchy->maxDepth()]));
                }

                $data['parent_id'] = $parent->getKey();
            } else {
                $data['parent_id'] = null;
            }

            $parentChanged = $originalParentId !== ($data['parent_id'] ?? null);
        }

        $saved = DB::transaction(function () use ($page, $data, $expectedLockVersion, $originalParentId, $parentChanged): bool {
            $saved = $this->saveWithOptimisticLock($page, $data, $expectedLockVersion);

            if (! $saved) {
                return false;
            }

            if ($saved && ($data['is_home'] ?? false) === true) {
                $page->children()->update(['parent_id' => null]);
            }

            if ($parentChanged) {
                $page->refresh();
                $this->normalizeGroup($page, $originalParentId);
                $this->normalizeGroup($page, $page->parent_id, $page);
            }

            return true;
        });

        if (! $saved) {
            return $this->staleModelResult();
        }

        $page->refresh();
        PageUpdated::dispatch($page);

        return ActionResult::success(__('Stranica je ažurirana.'), $page);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'excerpt' => ['nullable', 'array'],
            'content' => ['nullable', 'array'],
            'status' => ['required', 'string', Rule::in(array_keys(config('pages.statuses', [])))],
            'template' => ['nullable', 'string', Rule::in(array_keys(config('pages.templates', [])))],
            'navigation_type' => ['nullable', 'string', Rule::in(['page', 'url'])],
            'navigation_url' => ['nullable', 'required_if:navigation_type,url', 'string', 'max:2048', new NavigationUrl],
            'navigation_target' => ['nullable', 'string', Rule::in(['_self', '_blank'])],
            'is_home' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'settings' => ['nullable', 'array'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
            'parent_uuid' => ['nullable', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function attributes(): array
    {
        return [
            'title' => __('naziv'),
            'excerpt' => __('sažetak'),
            'content' => __('sadržaj'),
            'status' => __('status'),
            'template' => __('predložak'),
            'is_home' => __('naslovnica'),
            'is_published' => __('objavljeno'),
            'published_at' => __('datum objave'),
            'sort_order' => __('redoslijed'),
            'settings' => __('postavke'),
            'parent_uuid' => __('nadređena stranica'),
        ];
    }

    private function homeExists(Page $page): bool
    {
        return $page->newQuery()
            ->where('is_home', true)
            ->whereKeyNot($page->getKey())
            ->exists();
    }

    private function normalizeGroup(Page $page, ?int $parentId, ?Page $moving = null): void
    {
        /** @var Collection<int, Page> $siblings */
        $siblings = $page->newQuery()
            ->where('team_id', $page->team_id)
            ->when(
                $parentId === null,
                fn (Builder $query): Builder => $query->whereNull('parent_id'),
                fn (Builder $query): Builder => $query->where('parent_id', $parentId),
            )
            ->lockForUpdate()
            ->ordered()
            ->get();

        if ($moving) {
            $siblings = $siblings
                ->reject(fn (Page $sibling): bool => $sibling->is($moving))
                ->push($moving)
                ->values();
        }

        $siblings->each(function (Page $sibling, int $index): void {
            if ($sibling->sort_order !== $index) {
                $sibling->forceFill(['sort_order' => $index])->save();
            }
        });
    }
}
