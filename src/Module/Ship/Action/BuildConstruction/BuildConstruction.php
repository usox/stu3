<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\BuildConstruction;

use request;
use Doctrine\ORM\EntityManagerInterface;
use Stu\Component\Ship\ShipRumpEnum;
use Stu\Component\Ship\Storage\ShipStorageManagerInterface;
use Stu\Component\Ship\System\ShipSystemModeEnum;
use Stu\Component\Ship\System\ShipSystemTypeEnum;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Ship\Lib\ShipCreatorInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Orm\Entity\ShipBuildplanInterface;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Repository\ShipBuildplanRepositoryInterface;
use Stu\Orm\Repository\ShipCrewRepositoryInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\Orm\Repository\ShipRumpRepositoryInterface;

final class BuildConstruction implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_BUILD_CONSTRUCTION';

    public const NEEDED_WORKBEES = 5;

    private ShipRepositoryInterface $shipRepository;

    private ShipLoaderInterface $shipLoader;

    private ShipCreatorInterface $shipCreator;

    private ShipBuildplanRepositoryInterface $shipBuildplanRepository;

    private ShipStorageManagerInterface $shipStorageManager;

    private ShipCrewRepositoryInterface $shipCrewRepository;

    private EntityManagerInterface $entityManager;

    private ShipRumpRepositoryInterface $shipRumpRepository;

    public function __construct(
        ShipRepositoryInterface $shipRepository,
        ShipLoaderInterface $shipLoader,
        ShipCreatorInterface $shipCreator,
        ShipBuildplanRepositoryInterface $shipBuildplanRepository,
        ShipStorageManagerInterface $shipStorageManager,
        ShipCrewRepositoryInterface $shipCrewRepository,
        EntityManagerInterface $entityManager,
        ShipRumpRepositoryInterface $shipRumpRepository
    ) {
        $this->shipRepository = $shipRepository;
        $this->shipLoader = $shipLoader;
        $this->shipCreator = $shipCreator;
        $this->shipBuildplanRepository = $shipBuildplanRepository;
        $this->shipStorageManager = $shipStorageManager;
        $this->shipCrewRepository = $shipCrewRepository;
        $this->entityManager = $entityManager;
        $this->shipRumpRepository = $shipRumpRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowShip::VIEW_IDENTIFIER);

        $userId = $game->getUser()->getId();

        $ship = $this->shipLoader->getByIdAndUser(
            request::indInt('id'),
            $userId
        );

        if (!$ship->canBuildConstruction()) {
            return;
        }

        if ($ship->getBuildplan()->getCrew() > 0 && $ship->getCrewCount() == 0) {
            $game->addInformationf(
                _("Es werden %d Crewmitglieder benötigt"),
                $ship->getBuildplan()->getCrew()
            );
            return;
        }

        //TODO check if there already is a base

        if (!$ship->isSystemHealthy(ShipSystemTypeEnum::SYSTEM_SHUTTLE_RAMP)) {
            $game->addInformation(_("Die Shuttle-Rampe ist zerstört"));
            return;
        }
        if ($ship->getEps() == 0) {
            $game->addInformation(_("Keine Energie vorhanden"));
            return;
        }
        if ($ship->getCloakState()) {
            $game->addInformation(_("Die Tarnung ist aktiviert"));
            return;
        }
        if ($ship->getWarpState()) {
            $game->addInformation(_("Der Warpantrieb ist aktiviert"));
            return;
        }
        if ($ship->getShieldState()) {
            $game->addInformation(_("Die Schilde sind aktiviert"));
            return;
        }

        //calculate needed eps and crew
        $workbeePlans = [];
        $neededCrew = 0;
        $neededEps = 0;
        foreach ($ship->getStorage() as $stor) {

            if (count($workbeePlans) === self::NEEDED_WORKBEES) {
                break;
            }

            $commodity = $stor->getCommodity();

            if (!$commodity->isWorkbee()) {
                continue;
            }

            $plan = $this->shipBuildplanRepository->getShuttleBuildplan($commodity->getId());
            if ($plan === null) {
                continue;
            }

            $amount = $stor->getAmount();
            $stillNeeded = self::NEEDED_WORKBEES - count($workbeePlans);

            for ($i = 0; $i < min($amount, $stillNeeded); $i++) {
                $workbeePlans[] = $plan;
                $neededCrew += $plan->getCrew();
                $neededEps += $plan->getRump()->getBaseEps();
            }
        }

        // check if ship has excess crew
        if ($ship->getCrewCount() - $ship->getBuildplan()->getCrew() < $neededCrew) {
            $game->addInformation(sprintf(
                _('Nicht genügend Crew für den Start der %d Workbees vorhanden, benötigt wird %d'),
                count($workbeePlans),
                $neededCrew
            ));
            return;
        }

        // check if ship got enough energy
        if ($ship->getEps() < $neededEps) {
            $game->addInformation(sprintf(
                _('Es wird insgesamt %d Energie für den Start der %d Workbees benötigt'),
                $neededEps,
                count($workbeePlans)
            ));
            return;
        }

        //start workbees
        $workbees = [];
        foreach ($workbeePlans as $plan) {
            $rump = $plan->getRump();

            // remove shuttle from storage
            $this->shipStorageManager->lowerStorage(
                $ship,
                $rump->getCommodity(),
                1
            );

            // start workbee and transfer crew
            $workbees[] = $this->startWorkbee($ship, $plan);
            $game->addInformation(sprintf(_('%s wurde erfolgreich gestartet'), $rump->getName()));
        }

        // build construction
        $construction = $this->buildConstruction($ship);

        // dock workbees to construction
        foreach ($workbees as $workbee) {
            $workbee->setDockedTo($construction);
            $this->shipRepository->save($workbee);
        }

        $game->addInformation(sprintf(_('%s wurde erfolgreich errichtet'), $construction->getName()));
        $game->addInformation('Die gestarteten Workbees haben an das Konstrukt angedockt');
    }

    private function startWorkbee(ShipInterface $ship, ShipBuildplanInterface $plan): ShipInterface
    {
        $rump = $plan->getRump();

        $workbee = $this->shipCreator->createBy(
            $ship->getUser()->getId(),
            $rump->getId(),
            $plan->getId()
        );

        $this->entityManager->flush();

        //reload ship with systems
        $workbee = $this->shipRepository->find($workbee->getId());

        $workbee->setEps($workbee->getMaxEps());
        $workbee->getShipSystem(ShipSystemTypeEnum::SYSTEM_LIFE_SUPPORT)->setMode(ShipSystemModeEnum::MODE_ALWAYS_ON);
        $workbee->getShipSystem(ShipSystemTypeEnum::SYSTEM_NBS)->setMode(ShipSystemModeEnum::MODE_ON);

        $workbee->setMap($ship->getMap());
        $workbee->setStarsystemMap($ship->getStarsystemMap());

        $shipCrewArray = $ship->getCrewlist()->getValues();
        for ($i = 0; $i < $plan->getCrew(); $i++) {
            $shipCrew = $shipCrewArray[$i];
            $shipCrew->setShip($workbee);
            $ship->getCrewlist()->removeElement($shipCrew);

            $this->shipCrewRepository->save($shipCrew);
        }

        $this->shipRepository->save($workbee);

        $ship->setEps($ship->getEps() - $workbee->getMaxEps());
        $this->shipRepository->save($ship);

        return $workbee;
    }

    private function buildConstruction(ShipInterface $ship): ShipInterface
    {
        $rump = $this->shipRumpRepository->find($ship->getUser()->getFactionId() + ShipRumpEnum::SHIP_RUMP_BASE_ID_CONSTRUCTION);


        $construction = $this->shipRepository->prototype();
        $construction->setUser($ship->getUser());
        $construction->setRump($rump);
        $construction->setName($rump->getName());
        $construction->setHuell($rump->getBaseHull());
        $construction->setMaxHuell($rump->getBaseHull());

        $construction->setMap($ship->getMap());
        $construction->setStarsystemMap($ship->getStarsystemMap());

        $this->shipRepository->save($construction);

        return $construction;
    }

    public function performSessionCheck(): bool
    {
        return false;
    }
}
