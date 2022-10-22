<?php

declare(strict_types=1);

namespace Stu\Module\Tick\Ship;

use Stu\Component\Building\BuildingEnum;
use Stu\Component\Colony\Storage\ColonyStorageManagerInterface;
use Stu\Component\Game\GameEnum;
use Stu\Component\Ship\RepairTaskEnum;
use Stu\Component\Ship\ShipStateEnum;
use Stu\Component\Ship\Storage\ShipStorageManagerInterface;
use Stu\Component\Ship\System\ShipSystemManagerInterface;
use Stu\Component\Ship\System\ShipSystemTypeEnum;
use Stu\Module\Commodity\CommodityTypeEnum;
use Stu\Module\Logging\LoggerEnum;
use Stu\Module\Logging\LoggerUtilFactoryInterface;
use Stu\Module\Logging\LoggerUtilInterface;
use Stu\Module\Message\Lib\PrivateMessageSenderInterface;
use Stu\Module\Message\Lib\PrivateMessageFolderSpecialEnum;
use Stu\Module\Ship\Lib\AlertRedHelperInterface;
use Stu\Module\Ship\Lib\ShipRemoverInterface;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Entity\ShipSystemInterface;
use Stu\Orm\Entity\UserInterface;
use Stu\Orm\Repository\ColonyShipRepairRepositoryInterface;
use Stu\Orm\Repository\CrewRepositoryInterface;
use Stu\Orm\Repository\ModuleQueueRepositoryInterface;
use Stu\Orm\Repository\ShipCrewRepositoryInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\Orm\Repository\StationShipRepairRepositoryInterface;
use Stu\Orm\Repository\UserRepositoryInterface;

final class ShipTickManager implements ShipTickManagerInterface
{
    private PrivateMessageSenderInterface $privateMessageSender;

    private ShipRemoverInterface $shipRemover;

    private ShipTickInterface $shipTick;

    private ShipRepositoryInterface $shipRepository;

    private UserRepositoryInterface $userRepository;

    private CrewRepositoryInterface $crewRepository;

    private ShipCrewRepositoryInterface $shipCrewRepository;

    private ShipSystemManagerInterface $shipSystemManager;

    private AlertRedHelperInterface $alertRedHelper;

    private ColonyShipRepairRepositoryInterface $colonyShipRepairRepository;

    private StationShipRepairRepositoryInterface $stationShipRepairRepository;

    private ColonyStorageManagerInterface $colonyStorageManager;

    private ShipStorageManagerInterface $shipStorageManager;

    private ModuleQueueRepositoryInterface $moduleQueueRepository;

    private LoggerUtilInterface $loggerUtil;

    public function __construct(
        PrivateMessageSenderInterface $privateMessageSender,
        ShipRemoverInterface $shipRemover,
        ShipTickInterface $shipTick,
        ShipRepositoryInterface $shipRepository,
        UserRepositoryInterface $userRepository,
        CrewRepositoryInterface $crewRepository,
        ShipCrewRepositoryInterface $shipCrewRepository,
        ShipSystemManagerInterface $shipSystemManager,
        AlertRedHelperInterface $alertRedHelper,
        ColonyShipRepairRepositoryInterface $colonyShipRepairRepository,
        StationShipRepairRepositoryInterface $stationShipRepairRepository,
        ColonyStorageManagerInterface $colonyStorageManager,
        ShipStorageManagerInterface $shipStorageManager,
        ModuleQueueRepositoryInterface $moduleQueueRepository,
        LoggerUtilFactoryInterface $loggerUtilFactory
    ) {
        $this->privateMessageSender = $privateMessageSender;
        $this->shipRemover = $shipRemover;
        $this->shipTick = $shipTick;
        $this->shipRepository = $shipRepository;
        $this->userRepository = $userRepository;
        $this->crewRepository = $crewRepository;
        $this->shipCrewRepository = $shipCrewRepository;
        $this->shipSystemManager = $shipSystemManager;
        $this->alertRedHelper = $alertRedHelper;
        $this->colonyShipRepairRepository = $colonyShipRepairRepository;
        $this->stationShipRepairRepository = $stationShipRepairRepository;
        $this->colonyStorageManager = $colonyStorageManager;
        $this->shipStorageManager = $shipStorageManager;
        $this->moduleQueueRepository = $moduleQueueRepository;
        $this->loggerUtil = $loggerUtilFactory->getLoggerUtil();
    }

    public function work(): void
    {
        //$this->loggerUtil->init('stu', LoggerEnum::LEVEL_ERROR);

        if ($this->loggerUtil->doLog()) {
            $startTime = microtime(true);
        }
        $this->checkForCrewLimitation();
        if ($this->loggerUtil->doLog()) {
            $endTime = microtime(true);
            $this->loggerUtil->log(sprintf("\t\tcheckForCrewLimitation, seconds: %F", $endTime - $startTime));
        }

        if ($this->loggerUtil->doLog()) {
            $startTime = microtime(true);
        }
        $this->removeEmptyEscapePods();
        if ($this->loggerUtil->doLog()) {
            $endTime = microtime(true);
            $this->loggerUtil->log(sprintf("\t\tremoveEmptyEscapePods, seconds: %F", $endTime - $startTime));
        }
        //$this->loggerUtil->init();

        //spare parts and system components are generated by ship tick, to avoid dead locks
        if ($this->loggerUtil->doLog()) {
            $startTime = microtime(true);
        }
        $this->proceedSpareParts();
        $this->repairShipsOnColonies(1);
        $this->repairShipsOnStations();
        if ($this->loggerUtil->doLog()) {
            $endTime = microtime(true);
            $this->loggerUtil->log(sprintf("\t\tRepairStuff, seconds: %F", $endTime - $startTime));
        }

        if ($this->loggerUtil->doLog()) {
            $startTime = microtime(true);
        }
        foreach ($this->shipRepository->getPlayerShipsForTick() as $ship) {
            //echo "Processing Ship ".$ship->getId()." at ".microtime()."\n";

            $this->shipTick->work($ship);
        }
        if ($this->loggerUtil->doLog()) {
            $endTime = microtime(true);
            $this->loggerUtil->log(sprintf("\t\tshipTick, seconds: %F", $endTime - $startTime));
        }

        if ($this->loggerUtil->doLog()) {
            $startTime = microtime(true);
        }
        $this->handleNPCShips();
        if ($this->loggerUtil->doLog()) {
            $endTime = microtime(true);
            $this->loggerUtil->log(sprintf("\t\thandleNPCShips, seconds: %F", $endTime - $startTime));
        }

        if ($this->loggerUtil->doLog()) {
            $startTime = microtime(true);
        }
        $this->lowerTrumfieldHull();
        $this->lowerStationConstructionHull();
        if ($this->loggerUtil->doLog()) {
            $endTime = microtime(true);
            $this->loggerUtil->log(sprintf("\t\tloweringTrumfieldConstruction, seconds: %F", $endTime - $startTime));
        }
    }

    private function removeEmptyEscapePods(): void
    {
        foreach ($this->shipRepository->getEscapePods() as $ship) {
            if ($ship->getCrewCount() == 0) {
                $this->shipRemover->remove($ship);
            }
        }
    }

    private function checkForCrewLimitation(): void
    {
        $userList = $this->userRepository->getNonNpcList();

        foreach ($userList as $user) {
            //only handle user that are not on vacation
            if ($user->isVacationRequestOldEnough()) {
                continue;
            }

            $crewLimit = $user->getGlobalCrewLimit();
            $crewOnShips = $this->shipCrewRepository->getAmountByUser($user->getId());
            $freeCrewCount = $this->crewRepository->getFreeAmountByUser($user->getId());

            if (($crewOnShips + $freeCrewCount) > $crewLimit) {
                if ($freeCrewCount > 0) {
                    $deleteAmount = min($crewOnShips + $freeCrewCount - $crewLimit, $freeCrewCount);

                    foreach ($this->crewRepository->getFreeByUser($user->getId(), $deleteAmount) as $crew) {
                        $this->crewRepository->delete($crew);
                    }

                    $msg = sprintf(_('Wegen Überschreitung des globalen Crewlimits haben %d freie Crewman ihren Dienst quittiert'), $deleteAmount);
                    $this->privateMessageSender->send(
                        GameEnum::USER_NOONE,
                        (int) $user->getId(),
                        $msg,
                        PrivateMessageFolderSpecialEnum::PM_SPECIAL_COLONY
                    );

                    $freeCrewCount = $freeCrewCount - $deleteAmount;
                }
                if (($crewOnShips + $freeCrewCount) > $crewLimit) {
                    $crewToQuit = $crewOnShips - $crewLimit;

                    while ($crewToQuit > 0) {
                        $quitAmount = $this->letCrewQuit($user);

                        if ($quitAmount === null) {
                            break;
                        }

                        $crewToQuit -= $quitAmount;
                    }
                }
            }
        }
    }

    private function letCrewQuit(UserInterface $user): ?int
    {
        $randomShipId = $this->shipRepository->getRandomShipIdWithCrewByUser($user->getId());

        if ($randomShipId === null) {
            return null;
        }

        $randomShip = $this->shipRepository->find($randomShipId);
        $doAlertRedCheck = $randomShip->getWarpState() || $randomShip->getCloakState();
        //deactivate ship
        $this->shipSystemManager->deactivateAll($randomShip);
        $randomShip->setAlertStateGreen();

        $this->shipRepository->save($randomShip);

        $crewArray = [];
        foreach ($randomShip->getCrewlist() as $shipCrew) {
            $crewArray[] = $shipCrew->getCrew();
        }
        $randomShip->getCrewlist()->clear();

        //remove crew
        $this->shipCrewRepository->truncateByShip($randomShipId);
        foreach ($crewArray as $crew) {
            $this->crewRepository->delete($crew);
        }

        $msg = sprintf(_('Wegen Überschreitung des globalen Crewlimits hat die Crew der %s gemeutert und das Schiff verlassen'), $randomShip->getName());
        $this->privateMessageSender->send(
            GameEnum::USER_NOONE,
            (int) $user->getId(),
            $msg,
            $randomShip->isBase() ?  PrivateMessageFolderSpecialEnum::PM_SPECIAL_STATION : PrivateMessageFolderSpecialEnum::PM_SPECIAL_SHIP
        );

        //do alert red stuff
        if ($doAlertRedCheck) {
            $this->alertRedHelper->doItAll($randomShip, null);
        }

        return count($crewArray);
    }

    private function lowerTrumfieldHull(): void
    {
        foreach ($this->shipRepository->getDebrisFields() as $ship) {
            $lower = rand(5, 15);
            if ($ship->getHuell() <= $lower) {
                $this->shipRemover->remove($ship);
                continue;
            }
            $ship->setHuell($ship->getHuell() - $lower);

            $this->shipRepository->save($ship);
        }
    }

    private function lowerStationConstructionHull(): void
    {
        foreach ($this->shipRepository->getStationConstructions() as $ship) {
            $lower = rand(5, 15);
            if ($ship->getHuell() <= $lower) {

                $msg = sprintf(_('Dein Konstrukt bei %s war zu lange ungenutzt und ist daher zerfallen'), $ship->getSectorString());
                $this->privateMessageSender->send(
                    GameEnum::USER_NOONE,
                    $ship->getUser()->getId(),
                    $msg,
                    PrivateMessageFolderSpecialEnum::PM_SPECIAL_STATION
                );

                $this->shipRemover->remove($ship);
                continue;
            }
            $ship->setHuell($ship->getHuell() - $lower);

            $this->shipRepository->save($ship);
        }
    }

    private function handleNPCShips(): void
    {
        // @todo
        foreach ($this->shipRepository->getNpcShipsForTick() as $ship) {
            if ($ship->hasShipSystem(ShipSystemTypeEnum::SYSTEM_EPS)) {
                $eps = (int) ceil($ship->getReactorOutput() - $ship->getEpsUsage());
                if ($eps + $ship->getEps() > $ship->getMaxEps()) {
                    $eps = $ship->getMaxEps() - $ship->getEps();
                }
                $ship->setEps($ship->getEps() + $eps);
            } else {
                $eps = (int) ceil($ship->getTheoreticalMaxEps() / 10);
                if ($eps + $ship->getEps() > $ship->getTheoreticalMaxEps()) {
                    $eps = $ship->getTheoreticalMaxEps() - $ship->getEps();
                }
                $ship->setEps($ship->getEps() + $eps);
            }

            $this->shipRepository->save($ship);
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

                if ($colony->hasActiveBuildingWithFunction($buildingFunction)) {
                    $this->colonyStorageManager->upperStorage(
                        $colony,
                        $queue->getModule()->getCommodity(),
                        $queue->getAmount()
                    );

                    $this->privateMessageSender->send(
                        GameEnum::USER_NOONE,
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
        foreach ($this->colonyShipRepairRepository->getMostRecentJobs($tickId) as $obj) {

            $ship = $obj->getShip();
            $colony = $obj->getColony();

            if ($colony->isBlocked()) {
                continue;
            }

            if (!$obj->getField()->isActive()) {
                continue;
            }

            if ($this->repairShipOnEntity($ship, $colony, true)) {
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

            if ($this->repairShipOnEntity($ship, $station, false)) {
                $this->stationShipRepairRepository->delete($obj);
                $this->shipRepository->save($ship);
            }
        }
    }

    private function repairShipOnEntity(ShipInterface $ship, $entity, bool $isColony): bool
    {
        // check for U-Mode
        if ($entity->getUser()->isVacationRequestOldEnough()) {
            return false;
        }

        $neededParts = $this->determineSpareParts($ship);

        // parts stored?
        if (!$this->enoughSparePartsOnEntity($neededParts, $entity, $isColony, $ship)) {
            return false;
        }

        $repairFinished = false;

        $ship->setHuell($ship->getHuell() + $ship->getRepairRate());
        if ($ship->getHuell() > $ship->getMaxHuell()) {
            $ship->setHuell($ship->getMaxHuell());
        }

        //repair ship systems
        $damagedSystems = $ship->getDamagedSystems();
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
        }

        // consume spare parts
        $this->consumeSpareParts($neededParts, $entity, $isColony);

        if (!$ship->canBeRepaired()) {
            $repairFinished = true;

            $ship->setHuell($ship->getMaxHuell());
            $ship->setState(ShipStateEnum::SHIP_STATE_NONE);

            $shipOwnerMessage = $isColony ? sprintf(
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
                GameEnum::USER_NOONE,
                $ship->getUser()->getId(),
                $shipOwnerMessage,
                PrivateMessageFolderSpecialEnum::PM_SPECIAL_SHIP
            );

            if ($ship->getUser()->getId() != $entity->getUserId()) {
                $entityOwnerMessage = $isColony ? sprintf(
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
                    GameEnum::USER_NOONE,
                    $entity->getUserId(),
                    $entityOwnerMessage,
                    $isColony ? PrivateMessageFolderSpecialEnum::PM_SPECIAL_COLONY :
                        PrivateMessageFolderSpecialEnum::PM_SPECIAL_STATION
                );
            }
        }
        $this->shipRepository->save($ship);

        return $repairFinished;
    }

    private function determineSpareParts(ShipInterface $ship): array
    {
        $neededSpareParts = 0;
        $neededSystemComponents = 0;

        $hull = $ship->getHuell();
        $maxHull = $ship->getMaxHuell();

        if ($hull < $maxHull) {
            $neededSpareParts += (int)($ship->getRepairRate() / RepairTaskEnum::HULL_HITPOINTS_PER_SPARE_PART);
        }

        $damagedSystems = $ship->getDamagedSystems();
        if (!empty($damagedSystems)) {
            $firstSystem = $damagedSystems[0];
            $firstSystemLvl = $this->determinSystemLevel($firstSystem);
            $healingPercentage = (100 - $firstSystem->getStatus()) / 100;

            $neededSpareParts += (int)ceil($healingPercentage * RepairTaskEnum::SHIPYARD_PARTS_USAGE[$firstSystemLvl][RepairTaskEnum::SPARE_PARTS_ONLY]);
            $neededSystemComponents += (int)ceil($healingPercentage * RepairTaskEnum::SHIPYARD_PARTS_USAGE[$firstSystemLvl][RepairTaskEnum::SYSTEM_COMPONENTS_ONLY]);

            // maximum of two systems get repaired
            if (count($damagedSystems) > 1) {
                $secondSystem = $damagedSystems[1];
                $secondSystemLvl = $this->determinSystemLevel($secondSystem);
                $healingPercentage = (100 - $secondSystem->getStatus()) / 100;

                $neededSpareParts += (int)ceil($healingPercentage * RepairTaskEnum::SHIPYARD_PARTS_USAGE[$secondSystemLvl][RepairTaskEnum::SPARE_PARTS_ONLY]);
                $neededSystemComponents += (int)ceil($healingPercentage * RepairTaskEnum::SHIPYARD_PARTS_USAGE[$secondSystemLvl][RepairTaskEnum::SYSTEM_COMPONENTS_ONLY]);
            }
        }

        return [
            CommodityTypeEnum::COMMODITY_SPARE_PART => $neededSpareParts,
            CommodityTypeEnum::COMMODITY_SYSTEM_COMPONENT => $neededSystemComponents
        ];
    }

    private function enoughSparePartsOnEntity(array $neededParts, $entity, bool $isColony, ShipInterface $ship): bool
    {
        $neededSpareParts = $neededParts[CommodityTypeEnum::COMMODITY_SPARE_PART];
        $neededSystemComponents = $neededParts[CommodityTypeEnum::COMMODITY_SYSTEM_COMPONENT];

        if ($neededSpareParts > 0) {
            $spareParts = $entity->getStorage()->get(CommodityTypeEnum::COMMODITY_SPARE_PART);

            if ($spareParts === null || $spareParts->getAmount() < $neededSpareParts) {
                $this->sendNeededAmountMessage($neededSpareParts, $neededSystemComponents, $ship, $entity, $isColony);
                return false;
            }
        }

        if ($neededSystemComponents > 0) {
            $systemComponents = $entity->getStorage()->get(CommodityTypeEnum::COMMODITY_SYSTEM_COMPONENT);

            if ($systemComponents === null || $systemComponents->getAmount() < $neededSystemComponents) {
                $this->sendNeededAmountMessage($neededSpareParts, $neededSystemComponents, $ship, $entity, $isColony);
                return false;
            }
        }

        return true;
    }

    private function determinSystemLevel(ShipSystemInterface $system): int
    {
        $module = $system->getModule();

        if ($module !== null) {
            return $module->getLevel();
        } else {
            return $system->getShip()->getRump()->getModuleLevel();
        }
    }

    private function consumeSpareParts(array $neededParts, $entity, bool $isColony): void
    {
        foreach ($neededParts as $commodityKey => $amount) {
            //$this->loggerUtil->log(sprintf('consume, cid: %d, amount: %d', $commodityKey, $amount));

            if ($amount < 1) {
                continue;
            }

            $commodity = $entity->getStorage()->get($commodityKey)->getCommodity();

            if ($isColony) {
                $this->colonyStorageManager->lowerStorage($entity, $commodity, $amount);
            } else {
                $this->shipStorageManager->lowerStorage($entity, $commodity, $amount);
            }
        }
    }

    private function sendNeededAmountMessage(int $neededSpareParts, int $neededSystemComponents, ShipInterface $ship, $entity, bool $isColony): void
    {
        $neededPartsString = sprintf(
            "%d %s%s",
            $neededSpareParts,
            CommodityTypeEnum::getDescription(CommodityTypeEnum::COMMODITY_SPARE_PART),
            ($neededSystemComponents > 0 ? sprintf(
                "\n%d %s",
                $neededSystemComponents,
                CommodityTypeEnum::getDescription(CommodityTypeEnum::COMMODITY_SYSTEM_COMPONENT)
            ) : '')
        );

        $entityOwnerMessage = $isColony ? sprintf(
            "Die Reparatur der %s von Siedler %s wurde in Sektor %s bei der Kolonie %s angehalten.\nEs werden folgende Waren benötigt:\n%s",
            $ship->getName(),
            $ship->getUser()->getName(),
            $ship->getSectorString(),
            $entity->getName(),
            $neededPartsString
        ) : sprintf(
            "Die Reparatur der %s von Siedler %s wurde in Sektor %s bei der %s %s angehalten.\nEs werden folgende Waren benötigt:\n%s",
            $ship->getName(),
            $ship->getUser()->getName(),
            $ship->getSectorString(),
            $entity->getRump()->getName(),
            $entity->getName(),
            $neededPartsString
        );

        $this->privateMessageSender->send(
            GameEnum::USER_NOONE,
            $entity->getUserId(),
            $entityOwnerMessage,
            PrivateMessageFolderSpecialEnum::PM_SPECIAL_COLONY
        );
    }
}
