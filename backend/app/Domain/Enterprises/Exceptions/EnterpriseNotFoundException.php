<?php

namespace App\Domain\Enterprises\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class EnterpriseNotFoundException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::EnterpriseNotFound,
            httpStatus: 404,
        );
    }
}
