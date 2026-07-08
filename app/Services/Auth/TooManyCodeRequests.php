<?php

declare(strict_types=1);

namespace App\Services\Auth;

use RuntimeException;

/** Thrown when a sign-up code is requested too often for one address. */
class TooManyCodeRequests extends RuntimeException {}
