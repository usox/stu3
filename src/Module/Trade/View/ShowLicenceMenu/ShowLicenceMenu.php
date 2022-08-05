<?php

declare(strict_types=1);

namespace Stu\Module\Trade\View\ShowLicenceMenu;

use Stu\Exception\AccessViolation;
use Stu\Module\Commodity\CommodityTypeEnum;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Orm\Entity\CommodityInterface;
use Stu\Orm\Repository\CommodityRepositoryInterface;
use Stu\Orm\Repository\TradeCreateLicenceRepositoryInterface;

final class ShowLicenceMenu implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_LICENCE_MENU';


    private ShowLicenceMenuRequestInterface $showLicenceMenuRequest;

    private CommodityRepositoryInterface $commodityRepository;

    private TradeCreateLicenceRepositoryInterface $tradeCreateLicenceRepository;

    public function __construct(
        ShowLicenceMenuRequestInterface $showLicenceMenuRequest,
        CommodityRepositoryInterface $commodityRepository,
        TradeCreateLicenceRepositoryInterface $tradeCreateLicenceRepository
    ) {
        $this->showLicenceMenuRequest = $showLicenceMenuRequest;
        $this->commodityRepository = $commodityRepository;
        $this->tradeCreateLicenceeRepository = $tradeCreateLicenceRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();


        $trade_post = $this->tradePostRepository->find($this->showLicenceMenuRequest->getTradePostId());
        if ($trade_post === null) {
            return;
        }
        if ($trade_post->getUserId() !== $userId) {
            throw new AccessViolation(sprintf("Tradepost belongs to other user! Fool: %d", $userId));
        }

        $commodityList = $this->commodityRepository->getTradeable();

        $game->setMacroInAjaxWindow('html/trademacros.xhtml/tradelicencemenu');
        $game->setPageTitle(sprintf(
            _('Lizenzmanagement')
        ));
        $game->setTemplateVar('POST', $trade_post);
        $game->setTemplateVar('SELECTABLE_GOODS', $commodityList);
    }
}