<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Events\SectionCreated;
use IvanBaric\Pages\Models\Page;

final class CreateSectionAction
{
    use AuthorizesPageActions;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Page|string $page, array $data): ActionResult
    {
        $page = $this->findPage($page);

        if (! $page) {
            return ActionResult::failure(__('Page not found.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $page)) {
            return $result;
        }

        $validator = Validator::make($data, $this->rules(), attributes: $this->attributes());

        if ($validator->fails()) {
            return ActionResult::failure(__('The section could not be created.'), $validator->errors());
        }

        $validated = $validator->validated();
        $section = DB::transaction(static fn () => $page->addSection($validated['type'], $validated));

        SectionCreated::dispatch($section);

        return ActionResult::success(__('Section created.'), $section);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_keys(config('pages.section_types', [])))],
            'title' => ['nullable', 'array'],
            'subtitle' => ['nullable', 'array'],
            'description' => ['nullable', 'array'],
            'content' => ['nullable', 'array'],
            'image' => ['nullable', 'string', 'max:2048'],
            'button_text' => ['nullable', 'array'],
            'button_url' => ['nullable', 'string', 'max:2048'],
            'is_visible' => ['nullable', 'boolean'],
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
            'type' => __('section type'),
            'title' => __('title'),
            'subtitle' => __('subtitle'),
            'description' => __('description'),
            'content' => __('content'),
            'image' => __('image'),
            'button_text' => __('button text'),
            'button_url' => __('button URL'),
            'is_visible' => __('visible'),
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
}
