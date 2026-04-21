<?php

namespace App\Domain\Workspaces\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class WorkspaceRoleHasMembersException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::WorkspaceRoleHasMembers,
            httpStatus: 422,
        );
    }
}
