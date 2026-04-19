<?php

namespace App\Domain\Enterprises\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class InvitationExpiredException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::EnterpriseInvitationExpired,
            httpStatus: 422,
        );
    }
}
