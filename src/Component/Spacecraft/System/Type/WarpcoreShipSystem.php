<?php

declare(strict_types=1);

namespace Stu\Component\Spacecraft\System\Type;

use Override;
use RuntimeException;
use Stu\Component\Spacecraft\System\SpacecraftSystemManagerInterface;
use Stu\Component\Spacecraft\System\SpacecraftSystemModeEnum;
use Stu\Component\Spacecraft\System\SpacecraftSystemTypeEnum;
use Stu\Component\Spacecraft\System\SpacecraftSystemTypeInterface;
use Stu\Module\Spacecraft\Lib\SpacecraftWrapperInterface;

final class WarpcoreShipSystem extends AbstractSpacecraftSystemType implements SpacecraftSystemTypeInterface
{
    #[Override]
    public function getSystemType(): SpacecraftSystemTypeEnum
    {
        return SpacecraftSystemTypeEnum::WARPCORE;
    }

    #[Override]
    public function checkActivationConditions(SpacecraftWrapperInterface $wrapper, string &$reason): bool
    {
        $reactor = $wrapper->getReactorWrapper();
        if ($reactor === null) {
            throw new RuntimeException('this should not happen');
        }

        if ($reactor->getLoad() === 0) {
            $reason = _('keine Warpkernladung vorhanden ist');
            return false;
        }

        return true;
    }

    #[Override]
    public function activate(SpacecraftWrapperInterface $wrapper, SpacecraftSystemManagerInterface $manager): void
    {
        $wrapper->get()->getSpacecraftSystem($this->getSystemType())->setMode(SpacecraftSystemModeEnum::MODE_ALWAYS_ON);
    }

    #[Override]
    public function getEnergyUsageForActivation(): int
    {
        return 0;
    }

    #[Override]
    public function getEnergyConsumption(): int
    {
        return 0;
    }

    #[Override]
    public function getDefaultMode(): SpacecraftSystemModeEnum
    {
        return SpacecraftSystemModeEnum::MODE_ALWAYS_ON;
    }

    #[Override]
    public function handleDestruction(SpacecraftWrapperInterface $wrapper): void
    {
        $ship = $wrapper->get();
        if ($ship->hasSpacecraftSystem(SpacecraftSystemTypeEnum::WARPDRIVE)) {
            $ship->getSpacecraftSystem(SpacecraftSystemTypeEnum::WARPDRIVE)->setMode(SpacecraftSystemModeEnum::MODE_OFF);
        }
    }
}
