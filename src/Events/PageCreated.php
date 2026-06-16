<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IvanBaric\Corexis\Contracts\Events\DomainEvent;
use IvanBaric\Pages\Models\Page;

final readonly class PageCreated implements DomainEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Page $page) {}
}
