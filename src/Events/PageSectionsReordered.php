<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IvanBaric\Corexis\Contracts\Events\DomainEvent;
use IvanBaric\Pages\Models\Page;

final readonly class PageSectionsReordered implements DomainEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<int, string>  $sectionUuids
     */
    public function __construct(
        public Page $page,
        public array $sectionUuids,
    ) {}
}
