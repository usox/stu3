<?php

namespace Stu\Component\Ship\System\Utility;

use Stu\Module\Ship\Lib\ShipWrapperInterface;
use Stu\Orm\Entity\ShipInterface;

interface TractorMassPayloadUtilInterface
{
    public function tryToTow(ShipInterface $ship, ShipInterface $tractoredShip): ?string;

    public function tractorSystemSurvivedTowing(ShipWrapperInterface $wrapper, ShipInterface $tractoredShip, &$informations): bool;
}
