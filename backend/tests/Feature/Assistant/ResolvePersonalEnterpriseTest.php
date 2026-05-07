<?php

use App\Exceptions\ErrorCode;
use App\Models\Enterprise;
use App\Models\User;

it('allows access to assistant endpoints with personal enterprise', function () {
    [$user, $enterprise, $token] = asstCtx();

    $this->withHeaders(asstHdr($token, $enterprise))
        ->getJson('/api/v1/assistant/conversation')
        ->assertOk();
});

it('returns 403 when active enterprise is not personal', function () {
    [$user, , $token] = asstCtx();

    $teamEnterprise = Enterprise::factory()->create([
        'name'     => 'Team Corp',
        'type'     => 'enterprise',
        'owner_id' => $user->id,
    ]);
    asstAddToEnterprise($user, $teamEnterprise);

    $this->withHeaders(asstHdr($token, $teamEnterprise))
        ->getJson('/api/v1/assistant/conversation')
        ->assertForbidden()
        ->assertJsonPath('error.code', ErrorCode::AssistantPersonalEnterpriseRequired->value);
});

it('returns 401 without authentication', function () {
    $enterprise = Enterprise::factory()->personal()->create(['owner_id' => User::factory()->create()->id]);

    $this->withHeaders(['X-Enterprise-ID' => $enterprise->getHashId()])
        ->getJson('/api/v1/assistant/conversation')
        ->assertUnauthorized();
});
