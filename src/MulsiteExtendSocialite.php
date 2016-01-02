<?php
namespace SocialiteProviders\Mulsite;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MulsiteExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('mulsite', __NAMESPACE__.'\Provider');
    }
}
