<?php

namespace Database\Seeders;

use App\Domain\Assistant\Models\Friendship;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AssistantUserSeeder extends Seeder
{
    private const USERS = [
        ['name' => 'Assistant Free',    'email' => 'assistant-free@test.com',    'plan' => 'Free'],
        ['name' => 'Assistant Pro',     'email' => 'assistant-pro@test.com',     'plan' => 'Pro', 'is_admin' => true],
        ['name' => 'Assistant Premium', 'email' => 'assistant-premium@test.com', 'plan' => 'Premium'],
    ];

    public function run(): void
    {
        $created = [];

        foreach (self::USERS as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'        => $data['name'],
                    'password'    => Hash::make('password'),
                    'friend_code' => $this->uniqueFriendCode(),
                    'is_admin'    => $data['is_admin'] ?? false,
                ],
            );

            if ($user->wasRecentlyCreated) {
                $plan       = Plan::whereHas('product', fn ($q) => $q->where('slug', 'assistant'))
                    ->where('name', $data['plan'])
                    ->firstOrFail();
                $enterprise = $user->createEnterprise($data['name']);
                $enterprise->createEnterpriseProduct($plan);
                $user->assignOwnerRole($enterprise);
            }

            $created[$data['email']] = $user;
        }

        // free y pro son amigos
        Friendship::firstOrCreate(
            ['requester_id' => $created['assistant-free@test.com']->id, 'addressee_id' => $created['assistant-pro@test.com']->id],
            ['status' => 'accepted'],
        );
    }

    private function uniqueFriendCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('friend_code', $code)->exists());

        return $code;
    }
}
