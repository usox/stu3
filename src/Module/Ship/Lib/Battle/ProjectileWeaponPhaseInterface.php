<?php

namespace Stu\Module\Ship\Lib\Battle;

use Stu\Module\Ship\Lib\ShipWrapperInterface;
use Stu\Orm\Entity\PlanetFieldInterface;

interface ProjectileWeaponPhaseInterface
{
    /**
     * @param ShipWrapperInterface[] $targetPool
     */
    public function fire(
        ?ShipWrapperInterface $wrapper,
        $attackingPhalanx,
        array $targetPool,
        bool $isAlertRed = false
    ): array;

    public function fireAtBuilding(
        ShipWrapperInterface $attackerWrapper,
        PlanetFieldInterface $target,
        $isOrbitField,
        &$antiParticleCount
    ): array;
}
