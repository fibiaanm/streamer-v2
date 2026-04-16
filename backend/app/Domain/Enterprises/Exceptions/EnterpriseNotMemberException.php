<?php

namespace App\Domain\Enterprises\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class EnterpriseNotMemberException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::EnterpriseNotMember,
            httpStatus: 403,
        );
    }
}
