<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\Validator;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Actions\Concerns\ResolvesPageModels;
use IvanBaric\Pages\Events\SectionItemCreated;
use IvanBaric\Pages\Models\Section;

final class CreateSectionItemAction
{
    use AuthorizesPageActions, ResolvesPageModels;

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
            return ActionResult::error(__('Stavku nije moguće izraditi.'), errors: $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $item = $section->addItem($validated);

        SectionItemCreated::dispatch($item);

        return ActionResult::success(__('Stavka je izrađena.'), $item);
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
