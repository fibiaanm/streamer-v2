<?php

namespace App\Domain\Workspaces\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class WorkspaceOrphanedException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::WorkspaceOrphaned,
            httpStatus: 422,
        );
    }
}
