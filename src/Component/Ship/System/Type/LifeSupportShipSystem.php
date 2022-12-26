<?php

declare(strict_types=1);

namespace Stu\Component\Ship\System\Type;

use Stu\Component\Ship\System\ShipSystemManagerInterface;
use Stu\Component\Ship\System\ShipSystemModeEnum;
use Stu\Component\Ship\System\ShipSystemTypeEnum;
use Stu\Component\Ship\System\ShipSystemTypeInterface;
use Stu\Module\Ship\Lib\ShipWrapperInterface;
use Stu\Orm\Entity\ShipInterface;

final class LifeSupportShipSystem extends AbstractShipSystemType implements ShipSystemTypeInterface
{
    public function getEnergyUsageForActivation(): int
    {
        return 0;
    }

    public function getPriority(): int
    {
        return ShipSystemTypeEnum::SYSTEM_PRIORITIES[ShipSystemTypeEnum::SYSTEM_LIFE_SUPPORT];
    }

    public function checkActivationConditions(ShipInterface $ship, &$reason): bool
    {
        if ($ship->getCrewCount() === 0) {
            $reason = _('keine Crew vorhanden ist');
            return false;
        }

        return true;
    }

    public function activate(ShipWrapperInterface $wrapper, ShipSystemManagerInterface $manager): void
    {
        $wrapper->get()->getShipSystem(ShipSystemTypeEnum::SYSTEM_LIFE_SUPPORT)->setMode(ShipSystemModeEnum::MODE_ALWAYS_ON);
    }

    public function deactivate(ShipWrapperInterface $wrapper): void
    {
        $wrapper->get()->getShipSystem(ShipSystemTypeEnum::SYSTEM_LIFE_SUPPORT)->setMode(ShipSystemModeEnum::MODE_OFF);
    }

    public function handleDestruction(ShipWrapperInterface $wrapper): void
    {
        //crew flees ship when tick happens!
    }

    public function getDefaultMode(): int
    {
        return ShipSystemModeEnum::MODE_ALWAYS_ON;
    }
}
