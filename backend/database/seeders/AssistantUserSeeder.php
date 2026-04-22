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
        ['name' => 'User One',   'email' => 'user1@test.com', 'plan' => 'Free'],
        ['name' => 'User Two',   'email' => 'user2@test.com', 'plan' => 'Pro'],
        ['name' => 'User Three', 'email' => 'user3@test.com', 'plan' => 'Free'],
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

        // user1 and user2 are friends
        $user1 = $created['user1@test.com'];
        $user2 = $created['user2@test.com'];

        Friendship::firstOrCreate(
            ['requester_id' => $user1->id, 'addressee_id' => $user2->id],
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
