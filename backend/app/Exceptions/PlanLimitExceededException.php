<?php

namespace App\Exceptions;

class PlanLimitExceededException extends AppException
{
    public function __construct(string $limitKey)
    {
        parent::__construct(
            errorCode:  ErrorCode::PlanLimitExceeded,
            httpStatus: 422,
            context:    ['limit' => $limitKey],
        );
    }
}
