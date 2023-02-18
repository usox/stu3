<?php

namespace Stu\Module\Colony\Lib;

use Stu\Orm\Entity\ColonyDepositMiningInterface;

interface ColonySurfaceInterface
{
    public function getSurface(): array;

    public function getSurfaceTileStyle(): string;

    public function getEpsBoxTitleString(): string;

    public function getShieldBoxTitleString(): string;

    public function getPositiveEffectPrimaryDescription(): string;

    public function getPositiveEffectSecondaryDescription(): string;

    public function getNegativeEffectDescription(): string;

    public function getStorageSumPercent(): float;

    public function updateSurface(): array;

    /**
     * @return array<int, array{deposit: ColonyDepositMiningInterface, currentlyMined: int}>
     */
    public function getUserDepositMinings(): array;

    public function getEnergyProduction(): int;

    public function hasShipyard(): bool;

    public function hasModuleFab(): bool;

    public function hasAirfield(): bool;
}
