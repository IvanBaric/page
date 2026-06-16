<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Data;

use IvanBaric\Corexis\Data\ActionResult as CorexisActionResult;

final readonly class ActionResult
{
    public function __construct(
        public bool $successful,
        public string $message,
        public mixed $data = null,
        public ?string $code = null,
        public array $errors = [],
    ) {}

    public static function success(string $message, mixed $data = null, ?string $code = null, array $errors = []): self
    {
        return new self(true, $message, $data, $code, $errors);
    }

    public static function failure(string $message, mixed $data = null, ?string $code = null, array $errors = []): self
    {
        return new self(false, $message, $data, $code, $errors);
    }

    public function toCorexis(): CorexisActionResult
    {
        return new CorexisActionResult(
            success: $this->successful,
            message: $this->message,
            data: $this->data,
            code: $this->code,
            errors: $this->errors,
        );
    }

    public static function fromCorexis(CorexisActionResult $result): self
    {
        return new self(
            successful: $result->success,
            message: $result->message,
            data: $result->data,
            code: $result->code,
            errors: $result->errors,
        );
    }
}
