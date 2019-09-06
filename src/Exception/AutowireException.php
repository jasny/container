<?php declare(strict_types=1);

namespace Jasny\Container\Exception;

/**
 * Exception when class doesn't lend itself for auto wiring.
 */
class AutowireException extends \BadMethodCallException
{
}
