<?php

namespace Stu\Module\Colony\Lib\Gui\Component;

use Override;
use request;
use Stu\Component\Building\BuildingFunctionEnum;
use Stu\Component\Colony\ColonyFunctionManagerInterface;
use Stu\Component\Colony\OrbitShipListRetrieverInterface;
use Stu\Lib\Colony\PlanetFieldHostInterface;
use Stu\Module\Colony\Lib\ColonyLibFactoryInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\StuTime;
use Stu\Module\Database\View\Category\Wrapper\DatabaseCategoryWrapperFactoryInterface;
use Stu\Module\Spacecraft\Lib\SpacecraftWrapperFactoryInterface;
use Stu\Orm\Entity\ColonyDepositMiningInterface;
use Stu\Orm\Entity\ColonyInterface;
use Stu\Orm\Repository\TorpedoTypeRepositoryInterface;

final class ManagementProvider implements GuiComponentProviderInterface
{
    public function __construct(
        private TorpedoTypeRepositoryInterface $torpedoTypeRepository,
        private DatabaseCategoryWrapperFactoryInterface $databaseCategoryWrapperFactory,
        private OrbitShipListRetrieverInterface $orbitShipListRetriever,
        private ColonyFunctionManagerInterface $colonyFunctionManager,
        private SpacecraftWrapperFactoryInterface $spacecraftWrapperFactory,
        private ColonyLibFactoryInterface $colonyLibFactory,
        private StuTime $stuTime
    ) {}

    #[Override]
    public function setTemplateVariables(
        PlanetFieldHostInterface $host,
        GameControllerInterface $game
    ): void {

        if (!$host instanceof ColonyInterface) {
            return;
        }

        $systemDatabaseEntry = $host->getSystem()->getDatabaseEntry();
        if ($systemDatabaseEntry !== null) {
            $starsystem = $this->databaseCategoryWrapperFactory->createDatabaseCategoryEntryWrapper($systemDatabaseEntry, $game->getUser());
            $game->setTemplateVar('STARSYSTEM_ENTRY_TAL', $starsystem);
        }

        $firstOrbitSpacecraft = null;

        $shipList = $this->orbitShipListRetriever->retrieve($host);
        if ($shipList !== []) {
            // if selected, return the current target
            $target = request::postInt('target');

            if ($target !== 0) {
                foreach ($shipList as $fleet) {
                    foreach ($fleet['ships'] as $idx => $ship) {
                        if ($idx == $target) {
                            $firstOrbitSpacecraft = $ship;
                        }
                    }
                }
            }
            if ($firstOrbitSpacecraft === null) {
                $firstOrbitSpacecraft = current(current($shipList)['ships']);
            }
        }

        $game->setTemplateVar(
            'POPULATION_CALCULATOR',
            $this->colonyLibFactory->createColonyPopulationCalculator($host)
        );

        $game->setTemplateVar(
            'FIRST_ORBIT_SPACECRAFT',
            $firstOrbitSpacecraft ? $this->spacecraftWrapperFactory->wrapSpacecraft($firstOrbitSpacecraft) : null
        );

        $particlePhalanx = $this->colonyFunctionManager->hasFunction($host, BuildingFunctionEnum::BUILDING_FUNCTION_PARTICLE_PHALANX);
        $game->setTemplateVar(
            'BUILDABLE_TORPEDO_TYPES',
            $particlePhalanx ? $this->torpedoTypeRepository->getForUser($game->getUser()->getId()) : null
        );

        $shieldingManager = $this->colonyLibFactory->createColonyShieldingManager($host);
        $game->setTemplateVar('SHIELDING_MANAGER', $shieldingManager);
        $game->setTemplateVar('DEPOSIT_MININGS', $this->getUserDepositMinings($host));
        $game->setTemplateVar('VISUAL_PANEL', $this->colonyLibFactory->createColonyScanPanel($host));

        $timestamp = $this->stuTime->time();
        $game->setTemplateVar('COLONY_TIME_HOUR', $host->getColonyTimeHour($timestamp));
        $game->setTemplateVar('COLONY_TIME_MINUTE', $host->getColonyTimeMinute($timestamp));
        $game->setTemplateVar('COLONY_DAY_TIME_PREFIX', $host->getDayTimePrefix($timestamp));
        $game->setTemplateVar('COLONY_DAY_TIME_NAME', $host->getDayTimeName($timestamp));
    }

    /**
     * @return array<int, array{deposit: ColonyDepositMiningInterface, currentlyMined: int}>
     */
    private function getUserDepositMinings(PlanetFieldHostInterface $host): array
    {
        $production = $this->colonyLibFactory->createColonyCommodityProduction($host)->getProduction();

        $result = [];
        if (!$host instanceof ColonyInterface) {
            return $result;
        }

        foreach ($host->getDepositMinings() as $deposit) {
            if ($deposit->getUser() === $host->getUser()) {
                $prod = $production[$deposit->getCommodity()->getId()] ?? null;

                $result[$deposit->getCommodity()->getId()] = [
                    'deposit' => $deposit,
                    'currentlyMined' => $prod === null ? 0 : $prod->getProduction()
                ];
            }
        }

        return $result;
    }
}
