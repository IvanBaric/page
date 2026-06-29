<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use IvanBaric\Corexis\Concerns\UsesOptimisticLocking;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Data\ActionResult;
use IvanBaric\Pages\Events\SectionItemUpdated;
use IvanBaric\Pages\Models\SectionItem;

final class UpdateSectionItemAction
{
    use AuthorizesPageActions, ResolvesPageModels, UsesOptimisticLocking;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(SectionItem|string $item, array $data): ActionResult
    {
        $item = $this->resolveSectionItem($item);

        if (! $item) {
            return ActionResult::failure(__('Section item not found.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $item)) {
            return $result;
        }

        $validator = Validator::make($data, $this->rules(), attributes: $this->attributes());

        if ($validator->fails()) {
            return ActionResult::failure(__('The section item could not be updated.'), $validator->errors());
        }

        $validated = $validator->validated();
        $expectedLockVersion = $this->pullExpectedLockVersion($validated);

        $saved = DB::transaction(function () use ($item, $validated, $expectedLockVersion): bool {
            return $this->saveWithOptimisticLock($item, $validated, $expectedLockVersion);
        });

        if (! $saved) {
            return ActionResult::fromCorexis($this->staleModelResult());
        }

        $item->refresh();
        SectionItemUpdated::dispatch($item);

        return ActionResult::success(__('Section item updated.'), $item);
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

}
