<?php

use App\Models\Enterprise;
use App\Models\EnterpriseMember;
use App\Models\EnterpriseRole;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

function asstCtx(): array
{
    $user       = User::factory()->create();
    $enterprise = $user->personalEnterprise;
    $token      = JWTAuth::fromUser($user);

    return [$user, $enterprise, $token];
}

function asstHdr(string $token, Enterprise $enterprise): array
{
    return [
        'Authorization'   => "Bearer $token",
        'X-Enterprise-ID' => $enterprise->getHashId(),
    ];
}

function asstAddToEnterprise(User $user, Enterprise $enterprise): void
{
    $memberRole = EnterpriseRole::whereNull('enterprise_id')
        ->where('name', 'member')
        ->firstOrFail();

    EnterpriseMember::create([
        'user_id'       => $user->id,
        'enterprise_id' => $enterprise->id,
        'role_id'       => $memberRole->id,
        'status'        => 'active',
    ]);
}
