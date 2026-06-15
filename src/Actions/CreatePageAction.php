<?php

namespace IvanBaric\Pages\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Support\TeamResolver;

final class CreatePageAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): ActionResult
    {
        $validator = Validator::make($data, $this->rules(), attributes: $this->attributes());

        if ($validator->fails()) {
            return ActionResult::failure(__('The page could not be created.'), $validator->errors());
        }

        $data = $validator->validated();
        $teamId = $data['team_id'] ?? app(TeamResolver::class)->resolve();

        if (($data['is_home'] ?? false) && $this->homeExists($teamId)) {
            return ActionResult::failure(__('A home page already exists for this team.'));
        }

        $model = config('pages.models.page', Page::class);
        $page = $model::query()->create($data);

        return ActionResult::success(__('Page created.'), $page);
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
            'status' => ['nullable', 'string', Rule::in(array_keys(config('pages.statuses', [])))],
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

    private function homeExists(?int $teamId): bool
    {
        $model = config('pages.models.page', Page::class);

        return $model::query()
            ->where('is_home', true)
            ->when($teamId === null, fn (Builder $query) => $query->whereNull('team_id'), fn (Builder $query) => $query->where('team_id', $teamId))
            ->exists();
    }
}
