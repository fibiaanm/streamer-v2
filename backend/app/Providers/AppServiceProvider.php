<?php

namespace App\Providers;

use App\Domain\Enterprises\Events\EnterpriseUpdated;
use App\Domain\Enterprises\Events\InvitationCancelled;
use App\Domain\Enterprises\Events\InvitationCreated;
use App\Domain\Enterprises\Events\MemberRemoved;
use App\Domain\Enterprises\Events\RoleCreated;
use App\Domain\Enterprises\Events\RoleDeleted;
use App\Domain\Enterprises\Events\RoleUpdated;
use App\Domain\Enterprises\Listeners\PublishEnterpriseUpdated;
use App\Domain\Enterprises\Listeners\PublishInvitationCancelled;
use App\Domain\Enterprises\Listeners\PublishInvitationCreated;
use App\Domain\Enterprises\Listeners\PublishMemberRemoved;
use App\Domain\Enterprises\Listeners\PublishRoleCreated;
use App\Domain\Enterprises\Listeners\PublishRoleDeleted;
use App\Domain\Enterprises\Listeners\PublishRoleUpdated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(EnterpriseUpdated::class,    PublishEnterpriseUpdated::class);
        Event::listen(MemberRemoved::class,        PublishMemberRemoved::class);
        Event::listen(InvitationCreated::class,    PublishInvitationCreated::class);
        Event::listen(InvitationCancelled::class,  PublishInvitationCancelled::class);
        Event::listen(RoleCreated::class,          PublishRoleCreated::class);
        Event::listen(RoleUpdated::class,          PublishRoleUpdated::class);
        Event::listen(RoleDeleted::class,          PublishRoleDeleted::class);
    }
}
