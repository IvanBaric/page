<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use IvanBaric\Corexis\Concerns\UsesOptimisticLocking;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Events\PageUpdated;
use IvanBaric\Pages\Models\Page;

final class UpdatePageAction
{
    use AuthorizesPageActions, ResolvesPageModels, UsesOptimisticLocking;

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

        $validator = Validator::make($data, $this->rules(), attributes: $this->attributes());

        if ($validator->fails()) {
            return ActionResult::error(__('Stranicu nije moguće ažurirati.'), errors: $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $expectedLockVersion = $this->pullExpectedLockVersion($data);

        if (($data['is_home'] ?? false) && $this->homeExists($page)) {
            return ActionResult::error(__('Naslovnica već postoji.'));
        }

        $saved = DB::transaction(function () use ($page, $data, $expectedLockVersion): bool {
            return $this->saveWithOptimisticLock($page, $data, $expectedLockVersion);
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
            'is_home' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'settings' => ['nullable', 'array'],
            'lock_version' => ['nullable', 'integer', 'min:0'],
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
        ];
    }

    private function homeExists(Page $page): bool
    {
        return $page->newQuery()
            ->where('is_home', true)
            ->whereKeyNot($page->getKey())
            ->exists();
    }
}
