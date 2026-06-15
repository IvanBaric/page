<?php

namespace IvanBaric\Pages\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Page;

final class UpdatePageAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Page|string $page, array $data): ActionResult
    {
        $page = $this->findPage($page);

        if (! $page) {
            return ActionResult::failure(__('Page not found.'));
        }

        $validator = Validator::make($data, $this->rules(), attributes: $this->attributes());

        if ($validator->fails()) {
            return ActionResult::failure(__('The page could not be updated.'), $validator->errors());
        }

        $data = $validator->validated();

        if (($data['is_home'] ?? false) && $this->homeExists($page, $data['team_id'] ?? $page->team_id)) {
            return ActionResult::failure(__('A home page already exists for this team.'));
        }

        $page->fill($data)->save();

        return ActionResult::success(__('Page updated.'), $page->refresh());
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

    private function findPage(Page|string $page): ?Page
    {
        if ($page instanceof Page) {
            return $page;
        }

        $model = config('pages.models.page', Page::class);

        return $model::query()->where('uuid', $page)->first();
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
