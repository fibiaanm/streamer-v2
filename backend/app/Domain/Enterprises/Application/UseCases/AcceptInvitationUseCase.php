<?php

namespace App\Domain\Enterprises\Application\UseCases;

use App\Domain\Auth\Application\TokenService;
use App\Domain\Auth\Exceptions\InvalidCredentialsException;
use App\Domain\Enterprises\Events\MemberAdded;
use App\Domain\Enterprises\Exceptions\InvitationMemberExistsException;
use App\Models\Enterprise;
use App\Models\EnterpriseMember;
use App\Models\Invitation;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AcceptInvitationUseCase
{
    public function __construct(private TokenService $tokenService) {}

    public function execute(Invitation $invitation, string $password, ?string $name): array
    {
        return DB::transaction(function () use ($invitation, $password, $name) {
            $user = User::where('email', $invitation->email)->first();

            if ($user) {
                if (!Hash::check($password, $user->password)) {
                    throw new InvalidCredentialsException();
                }
            } else {
                $user = User::create([
                    'name'     => $name,
                    'email'    => $invitation->email,
                    'password' => Hash::make($password),
                ]);

                $personal = $user->createEnterprise($user->name);
                $freePlan = Plan::freeFor('core');
                $personal->createEnterpriseProduct($freePlan);
                $user->assignOwnerRole($personal);
            }

            $existing = EnterpriseMember::where('user_id', $user->id)
                ->where('enterprise_id', $invitation->invitable_id)
                ->first();

            if ($existing) {
                if ($existing->status === 'active') {
                    throw new InvitationMemberExistsException();
                }

                $existing->update([
                    'role_id' => $invitation->enterprise_role_id,
                    'status'  => 'active',
                ]);

                $member = $existing->fresh();
            } else {
                $member = EnterpriseMember::create([
                    'user_id'       => $user->id,
                    'enterprise_id' => $invitation->invitable_id,
                    'role_id'       => $invitation->enterprise_role_id,
                    'status'        => 'active',
                ]);
            }

            $invitation->update([
                'status'      => 'accepted',
                'accepted_at' => now(),
            ]);

            $enterprise = Enterprise::findOrFail($invitation->invitable_id);
            event(new MemberAdded($enterprise, $member, $invitation));

            return $this->tokenService->issueTokens($user);
        });
    }
}
