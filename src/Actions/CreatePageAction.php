<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Pages\Actions\Concerns\AuthorizesPageActions;
use IvanBaric\Pages\Events\PageCreated;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Support\PagesModels;

final class CreatePageAction
{
    use AuthorizesPageActions;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): ActionResult
    {
        if ($result = $this->authorizePageAction('pages.create')) {
            return $result;
        }

        $validator = Validator::make($data, $this->rules(), attributes: $this->attributes());

        if ($validator->fails()) {
            return ActionResult::error(__('Stranicu nije moguće izraditi.'), errors: $validator->errors()->toArray());
        }

        $data = $validator->validated();
        if (($data['is_home'] ?? false) && $this->homeExists()) {
            return ActionResult::error(__('Naslovnica već postoji.'));
        }

        $parentUuid = $data['parent_uuid'] ?? null;
        unset($data['parent_uuid']);

        if (($data['is_home'] ?? false) === true) {
            $parentUuid = null;
        }

        if ($parentUuid !== null) {
            $parent = $this->resolveParent((string) $parentUuid, $data['team_id'] ?? corexis_tenant_id());

            if (! $parent) {
                return ActionResult::error(__('Odabrana nadređena stranica nije dostupna.'));
            }

            $data['parent_id'] = $parent->getKey();
        }

        $model = PagesModels::page();
        $page = $model::query()->create($data);

        PageCreated::dispatch($page);

        return ActionResult::success(__('Stranica je izrađena.'), $page);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
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
            'parent_uuid' => ['nullable', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function attributes(): array
    {
        return [
            'title' => __('naziv'),
            'excerpt' => __('sažetak'),
            'content' => __('sadržaj'),
            'status' => __('status'),
            'template' => __('predložak'),
            'is_home' => __('naslovnica'),
            'is_published' => __('objavljeno'),
            'published_at' => __('datum objave'),
            'sort_order' => __('redoslijed'),
            'settings' => __('postavke'),
            'parent_uuid' => __('nadređena stranica'),
        ];
    }

    private function homeExists(): bool
    {
        $model = PagesModels::page();

        return $model::query()->where('is_home', true)->exists();
    }

    private function resolveParent(string $uuid, mixed $tenantId): ?Page
    {
        if (! is_numeric($tenantId)) {
            return null;
        }

        $model = PagesModels::page();

        return $model::query()
            ->forTenant((int) $tenantId)
            ->where('uuid', $uuid)
            ->whereNull('parent_id')
            ->where('is_home', false)
            ->first();
    }
}
