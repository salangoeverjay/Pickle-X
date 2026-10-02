<?php

namespace App\Support;

use Spatie\LaravelPasskeys\Actions\ConfigureCeremonyStepManagerFactoryAction;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;

/** WebAuthn checks need https, except on localhost (local dev), which browsers treat as secure too. */
class PasskeyCeremony extends ConfigureCeremonyStepManagerFactoryAction
{
    public function execute(): CeremonyStepManagerFactory
    {
        $factory = parent::execute();
        $factory->setSecuredRelyingPartyId(['localhost']);

        return $factory;
    }
}
