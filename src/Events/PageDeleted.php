<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IvanBaric\Corexis\Contracts\Events\DomainEvent;

final readonly class PageDeleted implements DomainEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int|string $pageKey,
        public string $uuid,
    ) {}
}
