<?php

namespace Stu\Lib\ModuleRumpWrapper;

use Override;
use Stu\Component\Spacecraft\SpacecraftModuleTypeEnum;
use Stu\Module\Ship\Lib\ShipWrapperInterface;
use Stu\Orm\Entity\ModuleInterface;

final class ModuleRumpWrapperSpecial extends ModuleRumpWrapperBase implements ModuleRumpWrapperInterface
{
    #[Override]
    public function getValue(?ModuleInterface $module = null): int
    {
        return 0;
    }

    #[Override]
    public function getSecondValue(?ModuleInterface $module = null): ?int
    {
        return null;
    }

    #[Override]
    public function getModuleType(): SpacecraftModuleTypeEnum
    {
        return SpacecraftModuleTypeEnum::SPECIAL;
    }

    #[Override]
    public function apply(ShipWrapperInterface $wrapper): void {}
}
