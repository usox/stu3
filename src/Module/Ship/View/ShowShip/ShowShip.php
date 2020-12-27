<?php

declare(strict_types=1);

namespace Stu\Module\Ship\View\ShowShip;

use NavPanel;
use request;
//use Stu\Component\Database\DatabaseCategoryTypeEnum;
use Stu\Component\Player\ColonizationCheckerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Database\View\Category\Tal\DatabaseCategoryTalFactoryInterface;
use Stu\Lib\SessionInterface;
use Stu\Module\Ship\Lib\FleetNfsItem;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\Lib\ShipRumpSpecialAbilityEnum;
use Stu\Orm\Repository\ColonyRepositoryInterface;
use Stu\Orm\Repository\FleetRepositoryInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\Orm\Repository\TachyonScanRepositoryInterface;
use VisualNavPanel;

final class ShowShip implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_SHIP';

    private SessionInterface $session;

    private ShipLoaderInterface $shipLoader;

    private FleetRepositoryInterface $fleetRepository;

    private ShipRepositoryInterface $shipRepository;

    private ColonyRepositoryInterface $colonyRepository;

    private ColonizationCheckerInterface $colonizationChecker;

    private DatabaseCategoryTalFactoryInterface $databaseCategoryTalFactory;
    
    private TachyonScanRepositoryInterface $tachyonScanRepository;

    public function __construct(
        SessionInterface $session,
        ShipLoaderInterface $shipLoader,
        FleetRepositoryInterface $fleetRepository,
        ShipRepositoryInterface $shipRepository,
        ColonyRepositoryInterface $colonyRepository,
        ColonizationCheckerInterface $colonizationChecker,
        DatabaseCategoryTalFactoryInterface $databaseCategoryTalFactory,
        TachyonScanRepositoryInterface $tachyonScanRepository
    ) {
        $this->session = $session;
        $this->shipLoader = $shipLoader;
        $this->fleetRepository = $fleetRepository;
        $this->shipRepository = $shipRepository;
        $this->colonyRepository = $colonyRepository;
        $this->colonizationChecker = $colonizationChecker;
        $this->databaseCategoryTalFactory = $databaseCategoryTalFactory;
        $this->tachyonScanRepository = $tachyonScanRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $user = $game->getUser();
        $userId = $user->getId();
        $ownsCurrentColony = false;

        $ship = $this->shipLoader->getByIdAndUser(
            request::indInt('id'),
            $userId
        );

        $colony = $this->colonyRepository->getByPosition(
            $ship->getSystem(),
            $ship->getPosX(),
            $ship->getPosY()
        );

        $shipId = $ship->getId();

        // check if tachyon scan still active
        $tachyonActive = false;
        foreach ($this->tachyonScanRepository->findActiveByShipLocationAndOwner($ship) as $entry)
        {
            if ($ship->getId() === 2245)
            {
                echo "- scanId: ".$entry->getId()."\n";
            }
            $tachyonActive = true;
        }
        if ($ship->getId() === 2245)
        {
            echo "- tachyonActive: ".$tachyonActive."\n";
        }
        $nbs = $this->shipRepository->getSingleShipScannerResults(
            $ship->getSystem(),
            $ship->getSx(),
            $ship->getSy(),
            $ship->getCx(),
            $ship->getCy(),
            $ship->getId(),
            true,
            $tachyonActive
        );

        $singleShipsNbs = $this->shipRepository->getSingleShipScannerResults(
            $ship->getSystem(),
            $ship->getSx(),
            $ship->getSy(),
            $ship->getCx(),
            $ship->getCy(),
            $ship->getId(),
            false,
            $tachyonActive
        );

        $fleets = $this->fleetRepository->getByPositition(
            $ship->getSystem(),
            $ship->getCx(),
            $ship->getCy(),
            $ship->getSx(),
            $ship->getSy()
        );

        $fnbs = [];
        foreach ($fleets as $fleet) {
            $fnbs[] = new FleetNfsItem(
                $this->session,
                $fleet,
                $ship,
                $tachyonActive
            );
        }

        $canColonize = false;
        if ($colony) {
            if ($ship->getRump()->hasSpecialAbility(ShipRumpSpecialAbilityEnum::COLONIZE)) {
                $canColonize = $this->colonizationChecker->canColonize($user, $colony);
            }
            $ownsCurrentColony = $colony->getUser() === $user;
        }

        //Forschungseintrag erstellen, damit System-Link optional erstellt werden kann
        $starsystem = null;
        if ($ship->getSystem() !== null) {
            //$entry = $this->databaseEntryRepository->getByCategoryIdAndObjectId(DatabaseCategoryTypeEnum::DATABASE_CATEGORY_STARSYSTEM, $ship->getSystem()->getId());
            $starsystem = $this->databaseCategoryTalFactory->createDatabaseCategoryEntryTal($ship->getSystem()->getDatabaseEntry(), $user);
        }

        $game->appendNavigationPart(
            'ship.php',
            _('Schiffe')
        );
        $game->appendNavigationPart(
            sprintf('?%s=1&id=%d', static::VIEW_IDENTIFIER, $shipId),
            $ship->getName()
        );
        $game->setPagetitle($ship->getName());
        $game->setTemplateFile('html/ship.xhtml');

        $game->setTemplateVar('SHIP', $ship);
        if ($starsystem !== null) {
            $game->setTemplateVar('STARSYSTEM_ENTRY_TAL', $starsystem);
        }
        $game->setTemplateVar('VISUAL_NAV_PANEL', new VisualNavPanel($ship, $game->getUser(), $ship->getTachyonState()));
        $game->setTemplateVar('NAV_PANEL', new NavPanel($ship));
        $game->setTemplateVar(
            'HAS_NBS',
            $fnbs !== [] || $nbs !== [] || $singleShipsNbs !== []
        );
        $game->setTemplateVar('TACHYON_ACTIVE', $tachyonActive);
        //$game->setTemplateVar('CLOAK_NBS', $fnbs);//TODO via ShipRepo Methode isCloakedShipAt?
        $game->setTemplateVar('FLEET_NBS', $fnbs);
        $game->setTemplateVar('STATION_NBS', $nbs);
        $game->setTemplateVar('SHIP_NBS', $singleShipsNbs);
        $game->setTemplateVar('CAN_COLONIZE_CURRENT_COLONY', $canColonize);
        $game->setTemplateVar('OWNS_CURRENT_COLONY', $ownsCurrentColony);
        $game->setTemplateVar('CURRENT_COLONY', $colony);
    }
}
