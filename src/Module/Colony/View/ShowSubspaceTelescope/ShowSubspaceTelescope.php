<?php

declare(strict_types=1);

namespace Stu\Module\Colony\View\ShowSubspaceTelescope;

use request;
use Stu\Component\Building\BuildingEnum;
use Stu\Component\Colony\ColonyFunctionManagerInterface;
use Stu\Component\Colony\ColonyMenuEnum;
use Stu\Component\Game\GameEnum;
use Stu\Component\Game\ModuleViewEnum;
use Stu\Component\Map\MapEnum;
use Stu\Module\Colony\Lib\ColonyLoaderInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Starmap\Lib\StarmapUiFactoryInterface;
use Stu\Module\Starmap\View\RefreshSection\RefreshSection;

final class ShowSubspaceTelescope implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_SUBSPACE_TELESCOPE';

    private ColonyLoaderInterface $colonyLoader;

    private StarmapUiFactoryInterface $starmapUiFactory;

    private ColonyFunctionManagerInterface $colonyFunctionManager;

    public function __construct(
        ColonyLoaderInterface $colonyLoader,
        StarmapUiFactoryInterface $starmapUiFactory,
        ColonyFunctionManagerInterface $colonyFunctionManager
    ) {
        $this->colonyLoader = $colonyLoader;
        $this->starmapUiFactory = $starmapUiFactory;
        $this->colonyFunctionManager = $colonyFunctionManager;
    }

    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();

        $colony = $this->colonyLoader->byIdAndUser(
            request::indInt('id'),
            $userId,
            false
        );

        if (!$this->colonyFunctionManager->hasFunction($colony, BuildingEnum::BUILDING_FUNCTION_SUBSPACE_TELESCOPE)) {
            return;
        }

        $template = ColonyMenuEnum::MENU_SUBSPACE_TELESCOPE->getTemplate();
        $game->showMacro($template);

        $game->setTemplateVar('COLONY', $colony);

        $mapX =  (int) ceil($colony->getSystem()->getCx() / MapEnum::FIELDS_PER_SECTION);
        $mapY =  (int) ceil($colony->getSystem()->getCy() / MapEnum::FIELDS_PER_SECTION);
        $layer = $colony->getSystem()->getLayer();

        $game->addExecuteJS(sprintf(
            "registerNavKeys('%s.php', '%s', '%s', false);",
            ModuleViewEnum::MODULE_VIEW_COLONY,
            RefreshSection::VIEW_IDENTIFIER,
            'html/colony/telescopeSectionTable.twig'
        ), GameEnum::JS_EXECUTION_AJAX_UPDATE);

        $game->addExecuteJS("updateNavigation();", GameEnum::JS_EXECUTION_AFTER_RENDER);

        $helper = $this->starmapUiFactory->createMapSectionHelper();
        $helper->setTemplateVars(
            $game,
            $layer,
            $mapX + ($mapY - 1) * ((int) ceil($layer->getWidth() / MapEnum::FIELDS_PER_SECTION)),
        );
    }
}
