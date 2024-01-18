<?php

declare(strict_types=1);

namespace Stu\Module\Colony\View\ShowBeamFrom;

use Stu\Module\Colony\Lib\ColonyLoaderInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Ship\Lib\Interaction\InteractionCheckerInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;

final class ShowBeamFrom implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_BEAMFROM';

    private ColonyLoaderInterface $colonyLoader;

    private ShowBeamFromRequestInterface $showBeamFromRequest;

    private ShipLoaderInterface $shipLoader;

    private InteractionCheckerInterface $interactionChecker;

    public function __construct(
        ColonyLoaderInterface $colonyLoader,
        ShowBeamFromRequestInterface $showBeamFromRequest,
        ShipLoaderInterface $shipLoader,
        InteractionCheckerInterface $interactionChecker
    ) {
        $this->colonyLoader = $colonyLoader;
        $this->showBeamFromRequest = $showBeamFromRequest;
        $this->shipLoader = $shipLoader;
        $this->interactionChecker = $interactionChecker;
    }

    public function handle(GameControllerInterface $game): void
    {
        $user = $game->getUser();
        $userId = $user->getId();

        $colony = $this->colonyLoader->loadWithOwnerValidation(
            $this->showBeamFromRequest->getColonyId(),
            $userId,
            false
        );

        $game->setPageTitle(_('Von Schiff beamen'));
        $game->setMacroInAjaxWindow('html/entityNotAvailable.twig');

        $wrapper = $this->shipLoader->find($this->showBeamFromRequest->getShipId(), false);
        if ($wrapper === null) {
            return;
        }

        $target = $wrapper->get();

        if (!$this->interactionChecker->checkColonyPosition($colony, $target)) {
            return;
        }

        if (($target->getCloakState() && $target->getUser() !== $user)) {
            return;
        }

        $game->setMacroInAjaxWindow('html/colonymacros.xhtml/show_ship_beamfrom');
        $game->setTemplateVar('targetShip', $target);
        $game->setTemplateVar('COLONY', $colony);
    }
}
