<?php

namespace App\AI\Exceptions;

use RuntimeException;
use Throwable;

final class AiInvalidResponseException extends RuntimeException
{
    public function __construct(
        string $message = 'The AI returned an invalid structured response.',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCategory(): string
    {
        return 'AI_INVALID_RESPONSE';
    }
}
