<?php

namespace App\Domain\Enterprises\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class InvitationInvalidException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::EnterpriseInvitationInvalid,
            httpStatus: 404,
        );
    }
}
