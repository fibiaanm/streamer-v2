<?php

namespace App\Domain\Auth\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class TokenExpiredException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::AuthTokenExpired,
            httpStatus: 401,
        );
    }
}
