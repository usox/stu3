<?php

declare(strict_types=1);

namespace Stu\Module\Tick\Ship\Repair;

use Stu\Component\Building\BuildingEnum;
use Stu\Component\Colony\ColonyFunctionManagerInterface;
use Stu\Component\Colony\Storage\ColonyStorageManagerInterface;
use Stu\Component\Ship\Repair\RepairUtilInterface;
use Stu\Component\Ship\ShipStateEnum;
use Stu\Component\Ship\System\ShipSystemManagerInterface;
use Stu\Module\Logging\LoggerUtilFactoryInterface;
use Stu\Module\Logging\LoggerUtilInterface;
use Stu\Module\Message\Lib\PrivateMessageFolderSpecialEnum;
use Stu\Module\Message\Lib\PrivateMessageSenderInterface;
use Stu\Module\PlayerSetting\Lib\UserEnum;
use Stu\Module\Ship\Lib\ShipWrapperFactoryInterface;
use Stu\Orm\Entity\ColonyInterface;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Repository\ColonyShipRepairRepositoryInterface;
use Stu\Orm\Repository\ModuleQueueRepositoryInterface;
use Stu\Orm\Repository\PlanetFieldRepositoryInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\Orm\Repository\StationShipRepairRepositoryInterface;

final class RepairActions implements RepairActionsInterface
{
    private ShipRepositoryInterface $shipRepository;

    private PrivateMessageSenderInterface $privateMessageSender;

    private ShipSystemManagerInterface $shipSystemManager;

    private ColonyShipRepairRepositoryInterface $colonyShipRepairRepository;

    private StationShipRepairRepositoryInterface $stationShipRepairRepository;

    private ColonyStorageManagerInterface $colonyStorageManager;

    private ModuleQueueRepositoryInterface $moduleQueueRepository;

    private RepairUtilInterface $repairUtil;

    private ShipWrapperFactoryInterface $shipWrapperFactory;

    private PlanetFieldRepositoryInterface $planetFieldRepository;

    private ColonyFunctionManagerInterface $colonyFunctionManager;

    private LoggerUtilInterface $loggerUtil;

    public function __construct(
        ShipRepositoryInterface $shipRepository,
        PrivateMessageSenderInterface $privateMessageSender,
        ShipSystemManagerInterface $shipSystemManager,
        ColonyShipRepairRepositoryInterface $colonyShipRepairRepository,
        StationShipRepairRepositoryInterface $stationShipRepairRepository,
        ColonyStorageManagerInterface $colonyStorageManager,
        ModuleQueueRepositoryInterface $moduleQueueRepository,
        RepairUtilInterface $repairUtil,
        ShipWrapperFactoryInterface $shipWrapperFactory,
        PlanetFieldRepositoryInterface $planetFieldRepository,
        ColonyFunctionManagerInterface $colonyFunctionManager,
        LoggerUtilFactoryInterface $loggerUtilFactory,
    ) {
        $this->shipRepository = $shipRepository;
        $this->privateMessageSender = $privateMessageSender;
        $this->shipSystemManager = $shipSystemManager;
        $this->colonyShipRepairRepository = $colonyShipRepairRepository;
        $this->stationShipRepairRepository = $stationShipRepairRepository;
        $this->colonyStorageManager = $colonyStorageManager;
        $this->moduleQueueRepository = $moduleQueueRepository;
        $this->repairUtil = $repairUtil;
        $this->shipWrapperFactory = $shipWrapperFactory;
        $this->planetFieldRepository = $planetFieldRepository;
        $this->colonyFunctionManager = $colonyFunctionManager;
        $this->loggerUtil = $loggerUtilFactory->getLoggerUtil();
    }

    public function work(): void
    {
        $startTime = microtime(true);

        //spare parts and system components are generated by ship tick, to avoid dead locks
        $this->proceedSpareParts();
        $this->repairShipsOnColonies(1);
        $this->repairShipsOnStations();

        if ($this->loggerUtil->doLog()) {
            $endTime = microtime(true);
            $this->loggerUtil->log(sprintf("\t\tcheckForCrewLimitation, seconds: %F", $endTime - $startTime));
        }
    }

    private function proceedSpareParts(): void
    {
        foreach ($this->moduleQueueRepository->findAll() as $queue) {
            $buildingFunction = $queue->getBuildingFunction();

            if (
                $buildingFunction === BuildingEnum::BUILDING_FUNCTION_FABRICATION_HALL ||
                $buildingFunction === BuildingEnum::BUILDING_FUNCTION_TECH_CENTER
            ) {
                $colony = $queue->getColony();

                if ($this->colonyFunctionManager->hasActiveFunction($colony, $buildingFunction)) {
                    $this->colonyStorageManager->upperStorage(
                        $colony,
                        $queue->getModule()->getCommodity(),
                        $queue->getAmount()
                    );

                    $this->privateMessageSender->send(
                        UserEnum::USER_NOONE,
                        $colony->getUser()->getId(),
                        sprintf(
                            _('Es wurden %d %s hergestellt'),
                            $queue->getAmount(),
                            $queue->getModule()->getName()
                        ),
                        PrivateMessageFolderSpecialEnum::PM_SPECIAL_COLONY
                    );

                    $this->moduleQueueRepository->delete($queue);
                }
            }
        }
    }

    private function repairShipsOnColonies(int $tickId): void
    {
        $usedShipyards = [];

        foreach ($this->colonyShipRepairRepository->getMostRecentJobs($tickId) as $obj) {
            $ship = $obj->getShip();
            $colony = $obj->getColony();

            if ($colony->isBlocked()) {
                continue;
            }

            $field = $this->planetFieldRepository->getByColonyAndFieldId(
                $obj->getColonyId(),
                $obj->getFieldId()
            );

            if ($field === null) {
                continue;
            }

            if (!$field->isActive()) {
                continue;
            }

            if (!array_key_exists($colony->getId(), $usedShipyards)) {
                $usedShipyards[$colony->getId()] = [];
            }

            $isRepairStationBonus = $this->colonyFunctionManager->hasActiveFunction($colony, BuildingEnum::BUILDING_FUNCTION_REPAIR_SHIPYARD);

            //already repaired a ship on this colony field, max is one without repair station
            if (
                !$isRepairStationBonus
                && array_key_exists($field->getFieldId(), $usedShipyards[$colony->getId()])
            ) {
                continue;
            }

            $usedShipyards[$colony->getId()][$field->getFieldId()] = [$field->getFieldId()];

            if ($this->repairShipOnEntity($ship, $colony, true, $isRepairStationBonus)) {
                $this->colonyShipRepairRepository->delete($obj);
                $this->shipRepository->save($ship);
            }
        }
    }

    private function repairShipsOnStations(): void
    {
        foreach ($this->stationShipRepairRepository->getMostRecentJobs() as $obj) {
            $this->loggerUtil->log('stationRepairJobId: ' . $obj->getId());

            $ship = $obj->getShip();
            $station = $obj->getStation();

            if (!$station->hasEnoughCrew()) {
                continue;
            }

            if ($this->repairShipOnEntity($ship, $station, false, false)) {
                $this->stationShipRepairRepository->delete($obj);
                $this->shipRepository->save($ship);
            }
        }
    }

    private function repairShipOnEntity(ShipInterface $ship, ColonyInterface|ShipInterface $entity, bool $isColony, bool $isRepairStationBonus): bool
    {
        // check for U-Mode
        if ($entity->getUser()->isVacationRequestOldEnough()) {
            return false;
        }

        $wrapper = $this->shipWrapperFactory->wrapShip($ship);
        $neededParts = $this->repairUtil->determineSpareParts($wrapper);

        // parts stored?
        if (!$this->repairUtil->enoughSparePartsOnEntity($neededParts, $entity, $isColony, $ship)) {
            return false;
        }

        $repairFinished = false;

        $hullRepairRate = $isRepairStationBonus ? $ship->getRepairRate() * 2 : $ship->getRepairRate();
        $ship->setHuell($ship->getHull() + $hullRepairRate);
        if ($ship->getHull() > $ship->getMaxHull()) {
            $ship->setHuell($ship->getMaxHull());
        }


        //repair ship systems
        $damagedSystems = $wrapper->getDamagedSystems();
        if (!empty($damagedSystems)) {
            $firstSystem = $damagedSystems[0];
            $firstSystem->setStatus(100);

            if ($ship->getCrewCount() > 0) {
                $firstSystem->setMode($this->shipSystemManager->lookupSystem($firstSystem->getSystemType())->getDefaultMode());
            }

            // maximum of two systems get repaired
            if (count($damagedSystems) > 1) {
                $secondSystem = $damagedSystems[1];
                $secondSystem->setStatus(100);

                if ($ship->getCrewCount() > 0) {
                    $secondSystem->setMode($this->shipSystemManager->lookupSystem($secondSystem->getSystemType())->getDefaultMode());
                }
            }

            // maximum of two additional systems get repaired
            if ($isRepairStationBonus) {
                if (count($damagedSystems) > 2) {
                    $thirdSystem = $damagedSystems[2];
                    $thirdSystem->setStatus(100);

                    if ($ship->getCrewCount() > 0) {
                        $thirdSystem->setMode($this->shipSystemManager->lookupSystem($thirdSystem->getSystemType())->getDefaultMode());
                    }
                }
                if (count($damagedSystems) > 3) {
                    $fourthSystem = $damagedSystems[3];
                    $fourthSystem->setStatus(100);

                    if ($ship->getCrewCount() > 0) {
                        $fourthSystem->setMode($this->shipSystemManager->lookupSystem($fourthSystem->getSystemType())->getDefaultMode());
                    }
                }
            }
        }

        // consume spare parts
        $this->repairUtil->consumeSpareParts($neededParts, $entity, $isColony);

        if (!$wrapper->canBeRepaired()) {
            $repairFinished = true;

            $ship->setHuell($ship->getMaxHull());
            $ship->setState(ShipStateEnum::SHIP_STATE_NONE);

            $shipOwnerMessage = $entity instanceof ColonyInterface ? sprintf(
                "Die Reparatur der %s wurde in Sektor %s bei der Kolonie %s des Spielers %s fertiggestellt",
                $ship->getName(),
                $ship->getSectorString(),
                $entity->getName(),
                $entity->getUser()->getName()
            ) : sprintf(
                "Die Reparatur der %s wurde in Sektor %s von der %s %s des Spielers %s fertiggestellt",
                $ship->getName(),
                $ship->getSectorString(),
                $entity->getRump()->getName(),
                $entity->getName(),
                $entity->getUser()->getName()
            );

            $this->privateMessageSender->send(
                $entity->getUser()->getId(),
                $ship->getUser()->getId(),
                $shipOwnerMessage,
                PrivateMessageFolderSpecialEnum::PM_SPECIAL_SHIP
            );

            $entityOwnerMessage = $entity instanceof ColonyInterface ? sprintf(
                "Die Reparatur der %s von Siedler %s wurde in Sektor %s bei der Kolonie %s fertiggestellt",
                $ship->getName(),
                $ship->getUser()->getName(),
                $ship->getSectorString(),
                $entity->getName()
            ) : sprintf(
                "Die Reparatur der %s von Siedler %s wurde in Sektor %s von der %s %s fertiggestellt",
                $ship->getName(),
                $ship->getUser()->getName(),
                $ship->getSectorString(),
                $entity->getRump()->getName(),
                $entity->getName()
            );

            $this->privateMessageSender->send(
                UserEnum::USER_NOONE,
                $entity->getUser()->getId(),
                $entityOwnerMessage,
                $isColony ? PrivateMessageFolderSpecialEnum::PM_SPECIAL_COLONY :
                    PrivateMessageFolderSpecialEnum::PM_SPECIAL_STATION
            );
        }
        $this->shipRepository->save($ship);

        return $repairFinished;
    }
}
