<?php

namespace App\Domain\Workspaces\Exceptions;

use App\Exceptions\AppException;
use App\Exceptions\ErrorCode;

class WorkspaceDepthExceededException extends AppException
{
    public function __construct()
    {
        parent::__construct(
            errorCode:  ErrorCode::WorkspaceDepthExceeded,
            httpStatus: 422,
        );
    }
}
