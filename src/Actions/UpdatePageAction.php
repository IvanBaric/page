<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use IvanBaric\Corexis\Concerns\UsesOptimisticLocking;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Data\ActionResult;
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
            return ActionResult::failure(__('Page not found.'));
        }

        if ($result = $this->authorizePageAction('pages.update', $page)) {
            return $result;
        }

        $validator = Validator::make($data, $this->rules(), attributes: $this->attributes());

        if ($validator->fails()) {
            return ActionResult::failure(__('The page could not be updated.'), $validator->errors());
        }

        $data = $validator->validated();
        $expectedLockVersion = $this->pullExpectedLockVersion($data);

        if (($data['is_home'] ?? false) && $this->homeExists($page, $data['team_id'] ?? $page->team_id)) {
            return ActionResult::failure(__('A home page already exists for this team.'));
        }

        $saved = DB::transaction(function () use ($page, $data, $expectedLockVersion): bool {
            return $this->saveWithOptimisticLock($page, $data, $expectedLockVersion);
        });

        if (! $saved) {
            return ActionResult::fromCorexis($this->staleModelResult());
        }

        $page->refresh();
        PageUpdated::dispatch($page);

        return ActionResult::success(__('Page updated.'), $page);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'team_id' => ['nullable', 'integer'],
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
            'title' => __('title'),
            'excerpt' => __('excerpt'),
            'content' => __('content'),
            'status' => __('status'),
            'template' => __('template'),
            'is_home' => __('home page'),
            'is_published' => __('published'),
            'published_at' => __('published date'),
            'sort_order' => __('sort order'),
            'settings' => __('settings'),
        ];
    }

    private function homeExists(Page $page, ?int $teamId): bool
    {
        return $page->newQuery()
            ->where('is_home', true)
            ->when($teamId === null, fn (Builder $query) => $query->whereNull('team_id'), fn (Builder $query) => $query->where('team_id', $teamId))
            ->whereKeyNot($page->getKey())
            ->exists();
    }
}
