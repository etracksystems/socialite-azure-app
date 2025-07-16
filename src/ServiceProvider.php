<?php

namespace EtrackSystems\SocialiteAzureApp;

use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {

    }
    public function boot()
    {
        Event::listen(
            SocialiteWasCalled::class,
            [AzureAppExtendSocialite::class, 'handle']
        );
    }
}