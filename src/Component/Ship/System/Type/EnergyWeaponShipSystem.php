<?php

declare(strict_types=1);

namespace Stu\Component\Ship\System\Type;

use Stu\Component\Ship\System\ShipSystemTypeInterface;
use Stu\Orm\Entity\ShipInterface;

final class EnergyWeaponShipSystem implements ShipSystemTypeInterface
{

    public function checkActivationConditions(ShipInterface $ship): bool
    {
        echo "checkActivationConditions\n";

        $result = $ship->getPhaser() === false &&
               $ship->getCloakState() === false &&
               $ship->canActivatePhaser();
        
        echo "result: " .$result."\n";

        return $result;
    }

    public function getEnergyUsageForActivation(): int
    {
        return 1;
    }

    public function activate(ShipInterface $ship): void
    {
        $ship->setPhaser(true);
    }

    public function deactivate(ShipInterface $ship): void
    {
        $ship->setPhaser(false);
    }
}
