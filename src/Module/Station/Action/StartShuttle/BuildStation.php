<?php

declare(strict_types=1);

namespace Stu\Module\Station\Action\BuildStation;

use request;
use Stu\Component\Ship\ShipModuleTypeEnum;
use Stu\Component\Ship\Storage\Exception\CommodityMissingException;
use Stu\Component\Ship\Storage\Exception\QuantityTooSmallException;
use Stu\Component\Ship\Storage\ShipStorageManagerInterface;
use Stu\Component\Station\StationUtilityInterface;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Logging\LoggerUtilInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Orm\Entity\ModuleInterface;
use Stu\Orm\Entity\ShipBuildplanInterface;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Entity\ShipRumpInterface;
use Stu\Orm\Repository\ConstructionProgressModuleRepositoryInterface;
use Stu\Orm\Repository\ConstructionProgressRepositoryInterface;
use Stu\Orm\Repository\ModuleRepositoryInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;

final class BuildStation implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_BUILD_STATION';

    private StationUtilityInterface $stationUtility;

    private ShipLoaderInterface $shipLoader;

    private ShipRepositoryInterface $shipRepository;

    private LoggerUtilInterface $loggerUtil;

    private ModuleRepositoryInterface $moduleRepository;

    private ShipStorageManagerInterface $shipStorageManager;

    private ConstructionProgressRepositoryInterface $constructionProgressRepository;

    private ConstructionProgressModuleRepositoryInterface $constructionProgressModuleRepository;

    public function __construct(
        StationUtilityInterface $stationUtility,
        ShipLoaderInterface $shipLoader,
        ShipRepositoryInterface $shipRepository,
        LoggerUtilInterface $loggerUtil,
        ModuleRepositoryInterface $moduleRepository,
        ShipStorageManagerInterface $shipStorageManager,
        ConstructionProgressRepositoryInterface $constructionProgressRepository,
        ConstructionProgressModuleRepositoryInterface $constructionProgressModuleRepository
    ) {
        $this->stationUtility = $stationUtility;
        $this->shipLoader = $shipLoader;
        $this->shipRepository = $shipRepository;
        $this->loggerUtil = $loggerUtil;
        $this->moduleRepository = $moduleRepository;
        $this->shipStorageManager = $shipStorageManager;
        $this->constructionProgressRepository = $constructionProgressRepository;
        $this->constructionProgressModuleRepository = $constructionProgressModuleRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowShip::VIEW_IDENTIFIER);

        $this->loggerUtil->init();

        $game->setTemplateVar('ERROR', true);

        $ship = $this->shipLoader->getByIdAndUser(
            request::indInt('id'),
            $game->getUser()->getId()
        );

        $userId = $game->getUser()->getId();

        $wantedPlanId = (int)request::postIntFatal('plan_select');

        $plan = $this->stationUtility->getBuidplanIfResearchedByUser($wantedPlanId, $userId);
        if ($plan === null) {
            return;
        }

        $rump = $plan->getRump();

        // check if enough workbees
        if (!$this->stationUtility->hasEnoughDockedWorkbees($ship, $rump)) {
            $game->addInformation('Nicht genügend Workbees angedockt');
            return;
        }

        $availableMods = $this->getSpecialModules($ship, $rump);

        // check if special modules allowed
        $wantedSpecialModuleIds = request::postArray('mod_9');
        $wantedSpecialModules = [];
        foreach ($wantedSpecialModuleIds as $wantedModId) {
            $mod = $this->getModuleIfAllowed((int) $wantedModId, $availableMods);

            if ($mod === null) {
                return;
            } else {
                $wantedSpecialModules[] = $mod;
            }
        }

        // try to consume needed goods
        if (!$this->consumeNeededModules($ship, $plan, $wantedSpecialModules)) {
            $game->addInformation('Nicht alle erforderlichen Module geladen');
            return;
        }

        // transform construction
        $this->startTransformation($ship, $rump, $wantedSpecialModules);

        $game->addInformation(sprintf(
            _('%s befindet sich nun im Bau. Fertigstellung bestenfalls in %d Ticks'),
            $rump->getName(),
            $rump->getBuildtime()
        ));
    }

    private function startTransformation(
        ShipInterface $ship,
        ShipBuildplanInterface $plan,
        array $wantedSpecialModules
    ): void {
        $rump = $plan->getRump();

        $ship->setName(sprintf('%s in Bau', $rump->getName()));
        $ship->setHuell(intdiv($rump->getBaseHull(), 2));
        $ship->setMaxHuell($rump->getBaseHull());
        $ship->setRump($rump);
        $ship->setBuildplan($plan);

        $this->shipRepository->save($ship);

        $progress = $this->constructionProgressRepository->prototype();
        $progress->setShipId($ship->getId());
        $progress->setRemainingTicks($rump->getBuildtime());

        $this->constructionProgressRepository->save($progress);

        foreach ($wantedSpecialModules as $mod) {
            $progressModule = $this->constructionProgressModuleRepository->prototype();
            $progressModule->setConstructionProgress($progress);
            $progressModule->setModule($mod);

            $this->constructionProgressModuleRepository->save($progressModule);
        }
    }

    private function getModuleIfAllowed(int $wantedModId, array $availableMods): ModuleInterface
    {
        foreach ($availableMods as $mod) {
            if ($mod->getId() === $wantedModId) {
                return $mod;
            }
        }

        return null;
    }

    private function getSpecialModules(ShipInterface $ship, ShipRumpInterface $rump): array
    {
        return $this->moduleRepository->getBySpecialTypeShipAndRump(
            $ship->getId(),
            ShipModuleTypeEnum::MODULE_TYPE_SPECIAL,
            $rump->getId(),
            $rump->getShipRumpRole()->getId()
        );
    }

    public function consumeNeededModules(ShipInterface $ship, ShipBuildplanInterface $plan, array $wantedSpecialModules): bool
    {
        try {
            foreach ($plan->getModules() as $buildplanModule) {
                $commodity = $buildplanModule->getModule()->getCommodity();

                $this->shipStorageManager->lowerStorage($ship, $commodity, $buildplanModule->getModuleCount());
            }

            foreach ($wantedSpecialModules as $mod) {
                $this->shipStorageManager->lowerStorage($ship, $mod->getCommodity(), 1);
            }
        } catch (CommodityMissingException | QuantityTooSmallException $e) {
            return false;
        }

        return true;
    }

    public function performSessionCheck(): bool
    {
        return false;
    }
}
