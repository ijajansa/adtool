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

    public function retryable(): bool
    {
        return ($this->context['reason'] ?? null) === 'connection_failure'
            || ($this->context['http_status'] ?? null) === 429
            || in_array($this->context['meta_code'] ?? null, [1, 2, 4, 17, 32, 613], true);
    }
}
