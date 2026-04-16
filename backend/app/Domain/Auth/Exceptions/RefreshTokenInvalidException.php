<?php

namespace App\Domain\Auth\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class RefreshTokenInvalidException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::AuthRefreshTokenInvalid,
            httpStatus: 401,
        );
    }
}
