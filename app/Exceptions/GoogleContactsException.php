<?php

namespace App\Exceptions;

use RuntimeException;

class GoogleContactsException extends RuntimeException
{
    public $reason;
    public $retryable;

    public function __construct(string $reason, string $message, bool $retryable = false)
    {
        parent::__construct($message);
        $this->reason = $reason;
        $this->retryable = $retryable;
    }
}
