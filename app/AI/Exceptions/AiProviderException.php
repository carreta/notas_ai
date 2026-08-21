<?php

namespace App\AI\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base class for application-owned AI provider failures that map to a single,
 * safe failure category. Provider SDK exceptions must never leave an adapter;
 * they are translated into one of these types first.
 */
abstract class AiProviderException extends RuntimeException
{
    abstract public function errorCategory(): string;

    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
