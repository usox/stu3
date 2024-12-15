<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\ColonyBlocking;

use Override;
use request;
use Stu\Component\Building\BuildingFunctionEnum;
use Stu\Component\Colony\ColonyFunctionManagerInterface;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Message\Lib\PrivateMessageFolderTypeEnum;
use Stu\Module\Message\Lib\PrivateMessageSenderInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Spacecraft\View\ShowSpacecraft\ShowSpacecraft;
use Stu\Orm\Repository\ColonyRepositoryInterface;
use Stu\Orm\Repository\FleetRepositoryInterface;

final class StartBlocking implements ActionControllerInterface
{
    public const string ACTION_IDENTIFIER = 'B_START_BLOCKING';

    public function __construct(
        private ColonyFunctionManagerInterface $colonyFunctionManager,
        private ShipLoaderInterface $shipLoader,
        private ColonyRepositoryInterface $colonyRepository,
        private FleetRepositoryInterface $fleetRepository,
        private PrivateMessageSenderInterface $privateMessageSender
    ) {}

    #[Override]
    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowSpacecraft::VIEW_IDENTIFIER);

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

        if ($currentColony->isFree()) {
            return;
        }

        $fleet = $ship->getFleet();
        if ($fleet->getBlockedColony() !== null || $fleet->getDefendedColony() !== null) {
            return;
        }

        if ($currentColony->getUser()->isVacationRequestOldEnough()) {
            $game->addInformation(_('Aktion nicht möglich, der Spieler befindet sich im Urlaubsmodus!'));
            return;
        }

        if ($currentColony->isDefended()) {
            $game->addInformation(_('Aktion nicht möglich, die Kolonie wird verteidigt!'));
            return;
        }

        if (
            $this->colonyFunctionManager->hasActiveFunction($currentColony, BuildingFunctionEnum::BUILDING_FUNCTION_ENERGY_PHALANX)
            || $this->colonyFunctionManager->hasActiveFunction($currentColony, BuildingFunctionEnum::BUILDING_FUNCTION_PARTICLE_PHALANX)
            || $this->colonyFunctionManager->hasActiveFunction($currentColony, BuildingFunctionEnum::BUILDING_FUNCTION_ANTI_PARTICLE)
        ) {
            $game->addInformation(_('Aktion nicht möglich, die Kolonie verfügt über aktive Orbitalverteidigung'));
            return;
        }

        $fleet->setBlockedColony($currentColony);
        $this->fleetRepository->save($fleet);

        $text = sprintf(_('Die Kolonie %s wird nun von der Flotte %s blockiert'), $currentColony->getName(), $fleet->getName());
        $game->addInformation($text);

        $this->privateMessageSender->send(
            $userId,
            $currentColony->getUser()->getId(),
            $text,
            PrivateMessageFolderTypeEnum::SPECIAL_COLONY
        );
    }

    #[Override]
    public function performSessionCheck(): bool
    {
        return true;
    }
}
