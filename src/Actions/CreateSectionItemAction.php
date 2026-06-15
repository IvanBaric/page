<?php

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\Validator;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Section;

final class CreateSectionItemAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Section|string $section, array $data): ActionResult
    {
        $section = $this->findSection($section);

        if (! $section) {
            return ActionResult::failure(__('Section not found.'));
        }

        $validator = Validator::make($data, $this->rules(), attributes: $this->attributes());

        if ($validator->fails()) {
            return ActionResult::failure(__('The section item could not be created.'), $validator->errors());
        }

        $item = $section->addItem($validator->validated());

        return ActionResult::success(__('Section item created.'), $item);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'title' => ['nullable', 'array'],
            'subtitle' => ['nullable', 'array'],
            'description' => ['nullable', 'array'],
            'content' => ['nullable', 'array'],
            'image' => ['nullable', 'string', 'max:2048'],
            'icon' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048'],
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
            'title' => __('title'),
            'subtitle' => __('subtitle'),
            'description' => __('description'),
            'content' => __('content'),
            'image' => __('image'),
            'icon' => __('icon'),
            'url' => __('URL'),
            'button_text' => __('button text'),
            'button_url' => __('button URL'),
            'is_visible' => __('visible'),
            'sort_order' => __('sort order'),
            'settings' => __('settings'),
        ];
    }

    private function findSection(Section|string $section): ?Section
    {
        if ($section instanceof Section) {
            return $section;
        }

        $model = config('pages.models.section', Section::class);

        return $model::query()->where('uuid', $section)->first();
    }
}
