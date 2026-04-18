<?php

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\EnterpriseMember;
use App\Models\EnterpriseRole;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class EnterpriseTeamSeeder extends Seeder
{
    private const TEAM_MEMBERS = [
        ['name' => 'Admin User',   'email' => 'admin@teams.test',   'role' => 'admin'],
        ['name' => 'Member One',   'email' => 'member1@teams.test',  'role' => 'member'],
        ['name' => 'Member Two',   'email' => 'member2@teams.test',  'role' => 'member'],
        ['name' => 'Billing User', 'email' => 'billing@teams.test', 'role' => 'billing'],
    ];

    public function run(): void
    {
        $teamsUser  = User::where('email', 'teams@test.com')->firstOrFail();
        $enterprise = EnterpriseMember::where('user_id', $teamsUser->id)
            ->with('enterprise')
            ->firstOrFail()
            ->enterprise;

        foreach (self::TEAM_MEMBERS as $data) {
            $role = EnterpriseRole::where('name', $data['role'])
                ->whereNull('enterprise_id')
                ->firstOrFail();

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')],
            );

            EnterpriseMember::firstOrCreate(
                ['user_id' => $user->id, 'enterprise_id' => $enterprise->id],
                ['role_id' => $role->id, 'status' => 'active'],
            );
        }

        $memberRole = EnterpriseRole::where('name', 'member')
            ->whereNull('enterprise_id')
            ->firstOrFail();

        Invitation::firstOrCreate(
            [
                'invitable_type' => Enterprise::class,
                'invitable_id'   => $enterprise->id,
                'email'          => 'invited@teams.test',
            ],
            [
                'invited_by_user_id' => $teamsUser->id,
                'enterprise_role_id' => $memberRole->id,
                'token'              => Str::uuid()->toString(),
                'status'             => 'pending',
                'expires_at'         => now()->addDays(7),
            ],
        );
    }
}
