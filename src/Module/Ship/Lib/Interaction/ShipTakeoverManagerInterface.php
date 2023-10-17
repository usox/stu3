<?php

namespace Stu\Module\Ship\Lib\Interaction;

use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Entity\ShipTakeoverInterface;

interface ShipTakeoverManagerInterface
{
    public function getPrestigeForBoardingAttempt(ShipInterface $target): int;

    public function getPrestigeForTakeover(ShipInterface $target): int;

    public function startTakeover(ShipInterface $source, ShipInterface $target, int $prestige): void;

    public function isTakeoverReady(ShipTakeoverInterface $takeover): bool;

    public function cancelTakeover(
        ?ShipTakeoverInterface $takeover,
        string $cause = null
    ): void;

    public function cancelBothTakeover(ShipInterface $ship, string $passiveCause = null): void;

    public function finishTakeover(ShipTakeoverInterface $takeover): void;
}