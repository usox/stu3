<?php

declare(strict_types=1);

namespace Stu\Module\Colony\View\ShowSubspaceTelescope;

use ColonyMenu;
use request;
use Stu\Component\Colony\ColonyEnum;
use Stu\Component\Game\ModuleViewEnum;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Colony\Lib\ColonyGuiHelperInterface;
use Stu\Module\Colony\Lib\ColonyLoaderInterface;
use Stu\Module\Starmap\Lib\MapSectionHelper;
use Stu\Module\Starmap\View\Overview\Overview;

final class ShowSubspaceTelescope implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_SUBSPACE_TELESCOPE';

    private ColonyLoaderInterface $colonyLoader;

    private ColonyGuiHelperInterface $colonyGuiHelper;

    public function __construct(
        ColonyLoaderInterface $colonyLoader,
        ColonyGuiHelperInterface $colonyGuiHelper
    ) {
        $this->colonyLoader = $colonyLoader;
        $this->colonyGuiHelper = $colonyGuiHelper;
    }

    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();

        $colony = $this->colonyLoader->byIdAndUser(
            request::indInt('id'),
            $userId
        );

        $this->colonyGuiHelper->register($colony, $game);

        $game->showMacro('html/colonymacros.xhtml/cm_telescope');

        $game->setTemplateVar('COLONY', $colony);
        $game->setTemplateVar('COLONY_MENU_SELECTOR', new ColonyMenu(ColonyEnum::MENU_SUBSPACE_TELESCOPE));

        $helper = new MapSectionHelper();
        $helper->setTemplateVars(
            $game,
            (int) ceil($colony->getSystem()->getCx() / Overview::FIELDS_PER_SECTION),
            (int) ceil($colony->getSystem()->getCy() / Overview::FIELDS_PER_SECTION),
            //TODO real sectionID
            0,
            ModuleViewEnum::MODULE_VIEW_COLONY,
            RefreshSubspaceSection::VIEW_IDENTIFIER
        );
    }
}
