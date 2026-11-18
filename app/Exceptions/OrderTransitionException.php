<?php

namespace App\Exceptions;

use RuntimeException;

/** An order status change that the business rules don't allow (message is user-facing). */
class OrderTransitionException extends RuntimeException
{
}
