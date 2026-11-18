<?php

namespace App\Exceptions;

use RuntimeException;

/** A cart operation the customer must be told about (message is user-facing, in Spanish). */
class CartException extends RuntimeException
{
}
