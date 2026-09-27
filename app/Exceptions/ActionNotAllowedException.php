<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * A business rule forbids this action (for example deleting yourself or the last Super Admin).
 * The message is safe to show to the user. Over HTTP it becomes a 403 response.
 */
class ActionNotAllowedException extends AuthorizationException {}
