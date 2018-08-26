<?php

declare(strict_types=1);

namespace Jasny\Container\Exception;

use OutOfBoundsException;
use Interop\Container\Exception\NotFoundException as Psr11Exception;

/**
 * This exception is thrown when an identifier is passed to the container and is not found.
 */
class NotFoundException extends OutOfBoundsException implements Psr11Exception
{
}
