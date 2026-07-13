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
            return ActionResult::error(__('Sekcija nije pronađena.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $section)) {
            return $result;
        }

        $validator = Validator::make($data, $this->rules(), attributes: $this->attributes());

        if ($validator->fails()) {
            return ActionResult::error(__('Sekciju nije moguće ažurirati.'), errors: $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $expectedLockVersion = $this->pullExpectedLockVersion($validated);

        $saved = DB::transaction(function () use ($section, $validated, $expectedLockVersion): bool {
            return $this->saveWithOptimisticLock($section, $validated, $expectedLockVersion);
        });

        if (! $saved) {
            return $this->staleModelResult();
        }

        $section->refresh();
        SectionUpdated::dispatch($section);

        return ActionResult::success(__('Sekcija je ažurirana.'), $section);
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
            'type' => __('vrsta sekcije'),
            'title' => __('naziv'),
            'subtitle' => __('podnaslov'),
            'description' => __('opis'),
            'content' => __('sadržaj'),
            'button_text' => __('tekst gumba'),
            'button_url' => __('poveznica gumba'),
            'is_visible' => __('vidljivost'),
            'sort_order' => __('redoslijed'),
            'settings' => __('postavke'),
        ];
    }
}
