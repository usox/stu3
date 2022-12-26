<?php

declare(strict_types=1);

namespace Stu\Component\Ship\System\Type;

use Stu\Component\Ship\System\ShipSystemManagerInterface;
use Stu\Component\Ship\System\ShipSystemModeEnum;
use Stu\Component\Ship\System\ShipSystemTypeEnum;
use Stu\Component\Ship\System\ShipSystemTypeInterface;
use Stu\Module\Ship\Lib\ShipWrapperInterface;

final class BeamBlockerShipSystem extends AbstractShipSystemType implements ShipSystemTypeInterface
{
    public function activate(ShipWrapperInterface $wrapper, ShipSystemManagerInterface $manager): void
    {
        $wrapper->get()->getShipSystem(ShipSystemTypeEnum::SYSTEM_BEAM_BLOCKER)->setMode(ShipSystemModeEnum::MODE_ALWAYS_ON);
    }

    public function deactivate(ShipWrapperInterface $wrapper): void
    {
        $wrapper->get()->getShipSystem(ShipSystemTypeEnum::SYSTEM_BEAM_BLOCKER)->setMode(ShipSystemModeEnum::MODE_OFF);
    }

    public function getDefaultMode(): int
    {
        return ShipSystemModeEnum::MODE_ALWAYS_ON;
    }

    public function getEnergyUsageForActivation(): int
    {
        return 0;
    }

    public function getEnergyConsumption(): int
    {
        return 0;
    }
}
