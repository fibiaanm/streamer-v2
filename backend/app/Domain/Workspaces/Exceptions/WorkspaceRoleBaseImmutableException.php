<?php

namespace App\Domain\Workspaces\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class WorkspaceRoleBaseImmutableException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::WorkspaceRoleBaseImmutable,
            httpStatus: 422,
        );
    }
}
