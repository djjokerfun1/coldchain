<?php

declare(strict_types=1);

namespace App\Domain\ColdChain\Enums;

enum ExcursionStatus: string
{
    // Opened on the first out-of-range reading; not yet confirmed because a
    // short blip (e.g. a door opening at delivery) is normal, not a breach.
    case Candidate = 'candidate';
    // The out-of-range condition persisted past the product's tolerance window.
    case Confirmed = 'confirmed';
    // Readings are back in range and the excursion has a closed_at.
    case Resolved = 'resolved';
}
