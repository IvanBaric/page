<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Admin\Contracts;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Pages\Admin\Field;

interface FieldOptionsProvider
{
    /** @return array<int|string, mixed> */
    public function options(Model $context, Field $field): array;
}
