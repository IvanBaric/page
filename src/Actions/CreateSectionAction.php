<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Corexis\Rules\SafePublicUrl;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Events\SectionCreated;
use IvanBaric\Pages\Models\Page;

final class CreateSectionAction
{
    use AuthorizesPageActions, ResolvesPageModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Page|string $page, array $data): ActionResult
    {
        $page = $this->resolvePage($page);

        if (! $page) {
            return ActionResult::error(__('Stranica nije pronađena.'));
        }

        if ($result = $this->authorizePageAction('pages.sections.manage', $page)) {
            return $result;
        }

        $validator = Validator::make($data, $this->rules(), attributes: $this->attributes());

        if ($validator->fails()) {
            return ActionResult::error(__('Sekciju nije moguće izraditi.'), errors: $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $section = $page->addSection($validated['type'], $validated);

        SectionCreated::dispatch($section);

        return ActionResult::success(__('Sekcija je izrađena.'), $section);
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
            'button_url' => ['nullable', 'string', 'max:2048', new SafePublicUrl],
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
