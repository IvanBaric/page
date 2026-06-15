<?php

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Models\Section;

final class UpdateSectionAction
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
            return ActionResult::failure(__('The section could not be updated.'), $validator->errors());
        }

        $section->fill($validator->validated())->save();

        return ActionResult::success(__('Section updated.'), $section->refresh());
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

    private function findSection(Section|string $section): ?Section
    {
        if ($section instanceof Section) {
            return $section;
        }

        $model = config('pages.models.section', Section::class);

        return $model::query()->where('uuid', $section)->first();
    }
}
