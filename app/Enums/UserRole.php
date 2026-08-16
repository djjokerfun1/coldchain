<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Planner = 'planner';
    case Driver = 'driver';
    case Client = 'client';
}
