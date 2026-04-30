<?php

namespace App\Domain\Assistant\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class AssistantPersonalEnterpriseRequiredException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::AssistantPersonalEnterpriseRequired,
            httpStatus: 403,
        );
    }
}
