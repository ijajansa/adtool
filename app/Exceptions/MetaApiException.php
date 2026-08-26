<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class MetaApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $safeMessage,
        private readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($safeMessage, 0, $previous);
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return $this->context;
    }
}
