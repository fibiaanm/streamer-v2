<?php

namespace App\Domain\Auth\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class UnauthorizedException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::AuthUnauthorized,
            httpStatus: 401,
        );
    }
}
