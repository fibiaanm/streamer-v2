<?php

namespace App\Domain\Auth\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class InvalidCredentialsException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::AuthInvalidCredentials,
            httpStatus: 401,
        );
    }
}
