<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Data;

use Illuminate\Database\Eloquent\Model;

final readonly class PublicSiteSubject
{
    public function __construct(
        public Model $model,
        public int $tenantId,
        public string $slug,
    ) {}
}
