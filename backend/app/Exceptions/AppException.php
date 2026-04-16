<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

abstract class AppException extends RuntimeException
{
    public function __construct(
        protected readonly ErrorCode $errorCode,
        protected readonly int       $httpStatus = 422,
        protected readonly array     $context    = [],
        ?Throwable                   $previous   = null,
    ) {
        parent::__construct($errorCode->value, $httpStatus, $previous);
    }

    public function getErrorCode(): ErrorCode { return $this->errorCode; }
    public function getHttpStatus(): int      { return $this->httpStatus; }
    public function getContext(): array       { return $this->context; }
}
