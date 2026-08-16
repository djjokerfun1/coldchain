<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Extends the routing base controller (not just the AuthorizesRequests
 * trait) because authorizeResource() registers its checks through
 * $this->middleware(), which only the base controller provides.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
}
