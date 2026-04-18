<?php

namespace App\Domain\Auth;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Payload;

class AuthPayload
{
    public function __construct(private readonly Payload $payload) {}

    public static function from(string $token): self
    {
        return new self(JWTAuth::setToken($token)->getPayload());
    }

    public function isGuest(): bool
    {
        return $this->payload->get('guest') === true;
    }

    public function subject(): mixed
    {
        return $this->payload->get('sub');
    }
}
