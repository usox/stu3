<?php

declare(strict_types=1);

namespace Stu\Module\Colony\View\RefreshSubspaceSection;

use Stu\Component\Game\ModuleViewEnum;
use Stu\Exception\SanityCheckException;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Starmap\Lib\MapSectionHelper;
use Stu\Module\Starmap\View\ShowSection\ShowSectionRequestInterface;
use Stu\Orm\Repository\LayerRepositoryInterface;

final class RefreshSubspaceSection implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'REFRESH_SUBSPACE_SECTION';

    private ShowSectionRequestInterface $request;

    private LayerRepositoryInterface $layerRepository;

    public function __construct(
        ShowSectionRequestInterface $request,
        LayerRepositoryInterface $layerRepository
    ) {
        $this->request = $request;
        $this->layerRepository = $layerRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $layerId = $this->request->getLayerId();
        $layer = $this->layerRepository->find($layerId);

        $xCoordinate = $this->request->getXCoordinate($layer);
        $yCoordinate = $this->request->getYCoordinate($layer);
        $sectionId = $this->request->getSectionId();

        //sanity check if user knows layer
        if (!$game->getUser()->hasSeen($layer->getId())) {
            throw new SanityCheckException('user tried to access unseen layer');
        }

        $game->showMacro('html/colonymacros.xhtml/telescope_nav');

        $helper = new MapSectionHelper();
        $helper->setTemplateVars(
            $game,
            $layer,
            $xCoordinate,
            $yCoordinate,
            $sectionId,
            ModuleViewEnum::MODULE_VIEW_COLONY,
            self::VIEW_IDENTIFIER
        );
    }
}
