<?php

namespace App\Domain\Workspaces\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class WorkspaceForbiddenException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::WorkspaceForbidden,
            httpStatus: 403,
        );
    }
}
