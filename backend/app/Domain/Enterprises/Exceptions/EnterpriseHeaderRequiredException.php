<?php

namespace App\Domain\Enterprises\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class EnterpriseHeaderRequiredException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::EnterpriseHeaderRequired,
            httpStatus: 422,
        );
    }
}
