<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Lib;

use Stu\Component\Ship\Repair\CancelRepairInterface;
use Stu\Component\Ship\RepairTaskEnum;
use Stu\Component\Ship\ShipAlertStateEnum;
use Stu\Component\Ship\System\Exception\InsufficientEnergyException;
use Stu\Component\Ship\System\ShipSystemManagerInterface;
use Stu\Component\Ship\System\ShipSystemTypeEnum;
use Stu\Component\Ship\System\Type\EpsShipSystem;
use Stu\Module\Colony\Lib\ColonyLibFactoryInterface;
use Stu\Module\Commodity\CommodityTypeEnum;
use Stu\Module\Control\GameControllerInterface;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Entity\ShipSystemInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;

final class ShipWrapper implements ShipWrapperInterface
{
    private ShipInterface $ship;

    private ShipSystemManagerInterface $shipSystemManager;

    private ShipRepositoryInterface $shipRepository;

    private ColonyLibFactoryInterface $colonyLibFactory;

    private CancelRepairInterface $cancelRepair;

    private GameControllerInterface $game;

    private $epsUsage;

    private $effectiveEpsProduction;

    public function __construct(
        ShipInterface $ship,
        ShipSystemManagerInterface $shipSystemManager,
        ShipRepositoryInterface $shipRepository,
        ColonyLibFactoryInterface $colonyLibFactory,
        CancelRepairInterface $cancelRepair,
        GameControllerInterface $game
    ) {
        $this->ship = $ship;
        $this->shipSystemManager = $shipSystemManager;
        $this->shipRepository = $shipRepository;
        $this->colonyLibFactory = $colonyLibFactory;
        $this->cancelRepair = $cancelRepair;
        $this->game = $game;
    }

    public function get(): ShipInterface
    {
        return $this->ship;
    }

    public function getEpsUsage(): int
    {
        if ($this->epsUsage === null) {
            $this->reloadEpsUsage();
        }
        return $this->epsUsage;
    }

    private function reloadEpsUsage(): void
    {
        $result = 0;

        foreach ($this->shipSystemManager->getActiveSystems($this->get()) as $shipSystem) {
            $result += $this->shipSystemManager->getEnergyConsumption($shipSystem->getSystemType());
        }

        if ($this->get()->getAlertState() == ShipAlertStateEnum::ALERT_YELLOW) {
            $result += ShipAlertStateEnum::ALERT_YELLOW_EPS_USAGE;
        }
        if ($this->get()->getAlertState() == ShipAlertStateEnum::ALERT_RED) {
            $result += ShipAlertStateEnum::ALERT_RED_EPS_USAGE;
        }

        $this->epsUsage = $result;
    }

    public function getEffectiveEpsProduction(): int
    {
        if ($this->effectiveEpsProduction === null) {
            $prod = $this->get()->getReactorOutputCappedByReactorLoad() - $this->getEpsUsage();
            if ($prod <= 0) {
                return $prod;
            }
            if ($this->get()->getEps() + $prod > $this->get()->getMaxEps()) {
                return $this->get()->getMaxEps() - $this->get()->getEps();
            }
            $this->effectiveEpsProduction = $prod;
        }
        return $this->effectiveEpsProduction;
    }

    public function getWarpcoreUsage(): int
    {
        return $this->getEffectiveEpsProduction() + $this->getEpsUsage();
    }

    public function setAlertState(int $alertState, &$msg): void
    {
        //check if enough energy
        if (
            $alertState == ShipAlertStateEnum::ALERT_YELLOW
            && $this->get()->getAlertState() == ShipAlertStateEnum::ALERT_GREEN
        ) {
            if ($this->get()->getEps() < 1) {
                throw new InsufficientEnergyException(1);
            }
            $this->get()->setEps($this->get()->getEps() - 1);
        }
        if (
            $alertState == ShipAlertStateEnum::ALERT_RED
            && $this->get()->getAlertState() !== ShipAlertStateEnum::ALERT_RED
        ) {
            if ($this->get()->getEps() < 2) {
                throw new InsufficientEnergyException(2);
            }
            $this->get()->setEps($this->get()->getEps() - 2);
        }

        // cancel repair if not on alert green
        if ($alertState !== ShipAlertStateEnum::ALERT_GREEN) {
            if ($this->cancelRepair->cancelRepair($this->get())) {
                $msg = _('Die Reparatur wurde abgebrochen');
            }
        }

        // now change
        $this->get()->setAlertState($alertState);
        $this->reloadEpsUsage();
    }

    public function leaveFleet(): void
    {
        $fleet = $this->get()->getFleet();

        if ($fleet !== null) {
            $fleet->getShips()->removeElement($this);

            $this->get()->setFleet(null);
            $this->get()->setIsFleetLeader(false);
            $this->get()->setFleetId(null);

            $this->shipRepository->save($this->get());
        }
    }

    //highest damage first, then prio
    public function getDamagedSystems(): array
    {
        $damagedSystems = [];
        $prioArray = [];
        foreach ($this->get()->getSystems() as $system) {
            if ($system->getStatus() < 100) {
                $damagedSystems[] = $system;
                $prioArray[$system->getSystemType()] = $this->shipSystemManager->lookupSystem($system->getSystemType())->getPriority();
            }
        }

        // sort by damage and priority
        usort(
            $damagedSystems,
            function (ShipSystemInterface $a, ShipSystemInterface $b) use ($prioArray): int {
                if ($a->getStatus() == $b->getStatus()) {
                    if ($prioArray[$a->getSystemType()] == $prioArray[$b->getSystemType()]) {
                        return 0;
                    }
                    return ($prioArray[$a->getSystemType()] > $prioArray[$b->getSystemType()]) ? -1 : 1;
                }
                return ($a->getStatus() < $b->getStatus()) ? -1 : 1;
            }
        );

        return $damagedSystems;
    }

    public function isOwnedByCurrentUser(): bool
    {
        if ($this->game->getUser() !== $this->get()->getUser()) {
            return false;
        }
        return true;
    }

    public function canLandOnCurrentColony(): bool
    {
        if (!$this->get()->getRump()->getCommodityId()) {
            return false;
        }
        if ($this->get()->isShuttle()) {
            return false;
        }

        $currentColony = $this->get()->getStarsystemMap() !== null ? $this->get()->getStarsystemMap()->getColony() : null;

        if ($currentColony === null) {
            return false;
        }
        if ($currentColony->getUser() !== $this->get()->getUser()) {
            return false;
        }

        return $this->colonyLibFactory
            ->createColonySurface($currentColony)
            ->hasAirfield();
    }

    public function canBeRepaired(): bool
    {
        if ($this->get()->getAlertState() !== ShipAlertStateEnum::ALERT_GREEN) {
            return false;
        }

        if ($this->get()->getShieldState()) {
            return false;
        }

        if ($this->get()->getCloakState()) {
            return false;
        }

        if (!empty($this->getDamagedSystems())) {
            return true;
        }

        return $this->get()->getHuell() < $this->get()->getMaxHuell();
    }

    public function getRepairDuration(): int
    {
        $ticks = (int) ceil(($this->get()->getMaxHuell() - $this->get()->getHuell()) / $this->get()->getRepairRate());
        $ticks = max($ticks, (int) ceil(count($this->getDamagedSystems()) / 2));

        return $ticks;
    }

    public function getRepairCosts(): array
    {
        $neededSpareParts = 0;
        $neededSystemComponents = 0;

        $hull = $this->get()->getHuell();
        $maxHull = $this->get()->getMaxHuell();

        if ($hull < $maxHull) {
            $ticks = (int) ceil(($this->get()->getMaxHuell() - $this->get()->getHuell()) / $this->get()->getRepairRate());
            $neededSpareParts += ((int)($this->get()->getRepairRate() / RepairTaskEnum::HULL_HITPOINTS_PER_SPARE_PART)) * $ticks;
        }

        $damagedSystems = $this->getDamagedSystems();
        foreach ($damagedSystems as $system) {
            $systemLvl = $this->determinSystemLevel($system);
            $healingPercentage = (100 - $system->getStatus()) / 100;

            $neededSpareParts += (int)ceil($healingPercentage * RepairTaskEnum::SHIPYARD_PARTS_USAGE[$systemLvl][RepairTaskEnum::SPARE_PARTS_ONLY]);
            $neededSystemComponents += (int)ceil($healingPercentage * RepairTaskEnum::SHIPYARD_PARTS_USAGE[$systemLvl][RepairTaskEnum::SYSTEM_COMPONENTS_ONLY]);
        }

        return [
            new ShipRepairCost($neededSpareParts, CommodityTypeEnum::COMMODITY_SPARE_PART, CommodityTypeEnum::getDescription(CommodityTypeEnum::COMMODITY_SPARE_PART)),
            new ShipRepairCost($neededSystemComponents, CommodityTypeEnum::COMMODITY_SYSTEM_COMPONENT, CommodityTypeEnum::getDescription(CommodityTypeEnum::COMMODITY_SYSTEM_COMPONENT))
        ];
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

    public function getEpsShipSystem(): ?EpsShipSystem
    {
        return $this->shipSystemManager->lookupSystem(ShipSystemTypeEnum::SYSTEM_EPS);
    }
}
