<?php

namespace App\Providers;

use App\Domain\Enterprises\Events\EnterpriseUpdated;
use App\Domain\Enterprises\Listeners\PublishEnterpriseUpdated;
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
        Event::listen(EnterpriseUpdated::class, PublishEnterpriseUpdated::class);
    }
}
