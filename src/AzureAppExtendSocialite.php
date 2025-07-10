<?php

namespace EtrackSystems\SocialiteAzureApp;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AzureAppExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('azure-app', AzureAppProvider::class);
    }
}
