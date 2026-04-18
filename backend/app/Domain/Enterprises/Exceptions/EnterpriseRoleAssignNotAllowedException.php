<?php

namespace App\Domain\Enterprises\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class EnterpriseRoleAssignNotAllowedException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::EnterpriseRoleAssignNotAllowed,
            httpStatus: 403,
        );
    }
}
