<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use IvanBaric\Corexis\Concerns\UsesOptimisticLocking;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Events\SectionUpdated;
use IvanBaric\Pages\Models\Section;

final class UpdateSectionAction
{
    use AuthorizesPageActions, ResolvesPageModels, UsesOptimisticLocking;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Section|string $section, array $data): ActionResult
    {
        $section = $this->resolveSection($section);

        if (! $section) {
            return ActionResult::failure(__('Section not found.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $section)) {
            return $result;
        }

        $validator = Validator::make($data, $this->rules(), attributes: $this->attributes());

        if ($validator->fails()) {
            return ActionResult::failure(__('The section could not be updated.'), $validator->errors());
        }

        $validated = $validator->validated();
        $expectedLockVersion = $this->pullExpectedLockVersion($validated);

        $saved = DB::transaction(function () use ($section, $validated, $expectedLockVersion): bool {
            return $this->saveWithOptimisticLock($section, $validated, $expectedLockVersion);
        });

        if (! $saved) {
            return ActionResult::fromCorexis($this->staleModelResult());
        }

        $section->refresh();
        SectionUpdated::dispatch($section);

        return ActionResult::success(__('Section updated.'), $section);
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
            'lock_version' => ['nullable', 'integer', 'min:0'],
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

}
