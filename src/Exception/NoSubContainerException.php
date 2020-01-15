<?php

declare(strict_types=1);

namespace Jasny\Container\Exception;

use Psr\Container\NotFoundExceptionInterface as Psr11Exception;

/**
 * This exception is thrown when an identifier is passed to the container and is not found.
 */
class NoSubContainerException extends \UnexpectedValueException implements Psr11Exception
{
}
