<?php

namespace Stu\Orm\Entity;

use Stu\Component\Building\BuildingFunctionEnum;

interface ShipRumpBuildingFunctionInterface
{
    public function getId(): int;

    public function getShipRumpId(): int;

    public function setShipRumpId(int $shipRumpId): ShipRumpBuildingFunctionInterface;

    public function getBuildingFunction(): BuildingFunctionEnum;

    public function setBuildingFunction(BuildingFunctionEnum $buildingFunction): ShipRumpBuildingFunctionInterface;
}
