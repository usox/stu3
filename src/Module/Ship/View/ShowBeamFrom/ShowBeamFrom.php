<?php

declare(strict_types=1);

namespace Stu\Module\Ship\View\ShowBeamFrom;

use request;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Ship\Lib\InteractionChecker;
use Stu\Module\Ship\Lib\ShipLoaderInterface;

final class ShowBeamFrom implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_BEAMFROM';

    private ShipLoaderInterface $shipLoader;

    public function __construct(
        ShipLoaderInterface $shipLoader
    ) {
        $this->shipLoader = $shipLoader;
    }

    public function handle(GameControllerInterface $game): void
    {
        $user = $game->getUser();

        $shipId = request::indInt('id');
        $targetId = request::getIntFatal('target');

        $shipArray = $this->shipLoader->getByIdAndUserAndTarget(
            $shipId,
            $user->getId(),
            $targetId
        );

        $ship = $shipArray[$shipId];
        $target = $shipArray[$targetId];

        $game->setPageTitle(_('Von Schiff beamen'));
        $game->setMacroInAjaxWindow('html/shipmacros.xhtml/entity_not_available');

        if ($target === null) {
            return;
        }
        if (!InteractionChecker::canInteractWith($ship, $target, $game, false, true)) {
            return;
        }

        $game->setMacroInAjaxWindow('html/shipmacros.xhtml/show_ship_beamfrom');

        $game->setTemplateVar('targetShip', $target);
        $game->setTemplateVar('SHIP', $ship);
        $game->setTemplateVar('OWNS_TARGET', $target->getUser() === $user);
    }
}
