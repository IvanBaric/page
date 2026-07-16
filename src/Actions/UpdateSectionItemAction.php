<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use IvanBaric\Corexis\Concerns\UsesOptimisticLocking;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Corexis\Rules\SafePublicUrl;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
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
            return ActionResult::error(__('Stavka nije pronađena.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $item)) {
            return $result;
        }

        $validator = Validator::make($data, $this->rules(), attributes: $this->attributes());

        if ($validator->fails()) {
            return ActionResult::error(__('Stavku nije moguće ažurirati.'), errors: $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $expectedLockVersion = $this->pullExpectedLockVersion($validated);

        $saved = DB::transaction(function () use ($item, $validated, $expectedLockVersion): bool {
            return $this->saveWithOptimisticLock($item, $validated, $expectedLockVersion);
        });

        if (! $saved) {
            return $this->staleModelResult();
        }

        $item->refresh();
        SectionItemUpdated::dispatch($item);

        return ActionResult::success(__('Stavka je ažurirana.'), $item);
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
            'icon' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048', new SafePublicUrl],
            'button_text' => ['nullable', 'array'],
            'button_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl],
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
            'title' => __('naziv'),
            'subtitle' => __('podnaslov'),
            'description' => __('opis'),
            'content' => __('sadržaj'),
            'icon' => __('ikona'),
            'url' => __('URL'),
            'button_text' => __('tekst gumba'),
            'button_url' => __('poveznica gumba'),
            'is_visible' => __('vidljivost'),
            'sort_order' => __('redoslijed'),
            'settings' => __('postavke'),
        ];
    }
}
