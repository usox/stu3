<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\ColonyDefending;

use Override;
use request;

use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Message\Lib\PrivateMessageFolderTypeEnum;
use Stu\Module\Message\Lib\PrivateMessageSenderInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;
use Stu\Orm\Repository\ColonyRepositoryInterface;
use Stu\Orm\Repository\FleetRepositoryInterface;

final class StopDefending implements ActionControllerInterface
{
    public const string ACTION_IDENTIFIER = 'B_STOP_DEFENDING';

    public function __construct(private ShipLoaderInterface $shipLoader, private ColonyRepositoryInterface $colonyRepository, private FleetRepositoryInterface $fleetRepository, private PrivateMessageSenderInterface $privateMessageSender)
    {
    }

    #[Override]
    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowShip::VIEW_IDENTIFIER);

        $userId = $game->getUser()->getId();

        $ship = $this->shipLoader->getByIdAndUser(
            request::indInt('id'),
            $userId
        );

        $currentColony = $this->colonyRepository->getByPosition(
            $ship->getStarsystemMap()
        );

        if ($currentColony === null) {
            return;
        }

        if (!$ship->isFleetLeader()) {
            return;
        }

        $fleet = $ship->getFleet();
        if ($fleet->getDefendedColony() === null) {
            return;
        }

        $fleet->setDefendedColony(null);
        $this->fleetRepository->save($fleet);

        $text = sprintf(_('Die Flotte %s hat die Verteidigung der Kolonie %s beendet'), $fleet->getName(), $currentColony->getName());
        $game->addInformation($text);

        if (!$currentColony->isFree()) {
            $this->privateMessageSender->send(
                $userId,
                $currentColony->getUser()->getId(),
                $text,
                PrivateMessageFolderTypeEnum::SPECIAL_COLONY
            );
        }
    }

    #[Override]
    public function performSessionCheck(): bool
    {
        return true;
    }
}
