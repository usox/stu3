<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\OpenAdventDoor;

use request;
use Stu\Component\Ship\ShipRumpEnum;
use Stu\Component\Ship\Storage\ShipStorageManager;
use Stu\Exception\SanityCheckException;
use Stu\Module\Commodity\CommodityTypeEnum;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Logging\LoggerUtilFactoryInterface;
use Stu\Module\Logging\LoggerUtilInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;
use Stu\Orm\Repository\CommodityRepositoryInterface;
use Stu\Orm\Repository\GameRequestRepositoryInterface;

final class OpenAdventDoor implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_ADVENT_DOOR';

    private ShipLoaderInterface $shipLoader;

    private GameRequestRepositoryInterface $gameRequestRepository;

    private CommodityRepositoryInterface $commodityRepository;

    private ShipStorageManager $shipStorageManager;

    private LoggerUtilInterface $loggerUtil;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        GameRequestRepositoryInterface $gameRequestRepository,
        CommodityRepositoryInterface $commodityRepository,
        ShipStorageManager $shipStorageManager,
        LoggerUtilFactoryInterface $loggerUtilFactory
    ) {
        $this->shipLoader = $shipLoader;
        $this->gameRequestRepository = $gameRequestRepository;
        $this->commodityRepository = $commodityRepository;
        $this->shipStorageManager = $shipStorageManager;
        $this->loggerUtil = $loggerUtilFactory->getLoggerUtil();
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowShip::VIEW_IDENTIFIER);
        $userId = $game->getUser()->getId();

        $shipId = request::indInt('id');
        $targetId = request::postIntFatal('target');

        $shipArray = $this->shipLoader->getByIdAndUserAndTarget(
            $shipId,
            $userId,
            $targetId
        );

        $ship = $shipArray[$shipId];
        $target = $shipArray[$targetId];
        if ($target === null) {
            return;
        }
        if ($target->getRump()->getRoleId() !== ShipRumpEnum::SHIP_ROLE_ADVENT_DOOR) {
            throw new SanityCheckException('target is not an advent door');
        }
        if (!$ship->canInteractWith($target)) {
            throw new SanityCheckException('can not interact with target');
        }

        if (!$ship->hasEnoughCrew($game)) {
            return;
        }

        if ($this->gameRequestRepository->hasUserOpenedAdventDoor($userId)) {
            $game->addInformation("Du hast heute bereits ein Türchen geöffnet");
            return;
        }

        if ($ship->getStorageSum() === $ship->getMaxStorage()) {
            $game->addInformation(sprintf(_('Der Lagerraum der %s ist voll'), $ship->getName()));
            return;
        }

        $commodity = $this->commodityRepository->find(CommodityTypeEnum::COMMODITY_ADVENT_POINT);
        $this->shipStorageManager->upperStorage($ship, $commodity, 1);

        $game->addInformation(sprintf('1 %s wurde in den Frachraum deines Schiffes transferiert', $commodity->getName()));
    }

    public function performSessionCheck(): bool
    {
        return false;
    }
}
