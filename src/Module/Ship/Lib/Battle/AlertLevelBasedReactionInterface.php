<?php

namespace Stu\Module\Ship\Lib\Battle;

use Stu\Lib\InformationWrapper;
use Stu\Module\Ship\Lib\ShipWrapperInterface;

interface AlertLevelBasedReactionInterface
{
    public function react(ShipWrapperInterface $wrapper): InformationWrapper;
}
