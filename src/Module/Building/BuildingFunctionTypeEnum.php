<?php

declare(strict_types=1);

namespace Stu\Module\Building;

use Stu\Component\Building\BuildingFunctionEnum;
use Stu\Component\Colony\ColonyMenuEnum;

final class BuildingFunctionTypeEnum
{
    /**
     * @return array<BuildingFunctionEnum>
     */
    public static function getModuleFabOptions(): array
    {
        return [
            BuildingFunctionEnum::BUILDING_FUNCTION_MODULEFAB_TYPE1_LVL1,
            BuildingFunctionEnum::BUILDING_FUNCTION_MODULEFAB_TYPE1_LVL2,
            BuildingFunctionEnum::BUILDING_FUNCTION_MODULEFAB_TYPE1_LVL3,
            BuildingFunctionEnum::BUILDING_FUNCTION_MODULEFAB_TYPE2_LVL1,
            BuildingFunctionEnum::BUILDING_FUNCTION_MODULEFAB_TYPE2_LVL2,
            BuildingFunctionEnum::BUILDING_FUNCTION_MODULEFAB_TYPE2_LVL3,
            BuildingFunctionEnum::BUILDING_FUNCTION_MODULEFAB_TYPE3_LVL1,
            BuildingFunctionEnum::BUILDING_FUNCTION_MODULEFAB_TYPE3_LVL2,
            BuildingFunctionEnum::BUILDING_FUNCTION_MODULEFAB_TYPE3_LVL3,
        ];
    }

    /**
     * @return array<BuildingFunctionEnum>
     */
    public static function getShipyardOptions(): array
    {
        return [
            BuildingFunctionEnum::BUILDING_FUNCTION_FIGHTER_SHIPYARD,
            BuildingFunctionEnum::BUILDING_FUNCTION_ESCORT_SHIPYARD,
            BuildingFunctionEnum::BUILDING_FUNCTION_FRIGATE_SHIPYARD,
            BuildingFunctionEnum::BUILDING_FUNCTION_CRUISER_SHIPYARD,
            BuildingFunctionEnum::BUILDING_FUNCTION_DESTROYER_SHIPYARD
        ];
    }

    public static function isBuildingFunctionMandatory(ColonyMenuEnum $menu): bool
    {
        switch ($menu) {
            case ColonyMenuEnum::MENU_SHIPYARD:
            case ColonyMenuEnum::MENU_SHIP_REPAIR:
            case ColonyMenuEnum::MENU_SHIP_DISASSEMBLY:
            case ColonyMenuEnum::MENU_BUILDPLANS:
            case ColonyMenuEnum::MENU_SHIP_RETROFIT:
                return true;
            default:
                return false;
        }
    }
}
