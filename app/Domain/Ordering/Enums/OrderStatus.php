<?php

declare(strict_types=1);

namespace App\Domain\Ordering\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Placed = 'placed';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';
}
