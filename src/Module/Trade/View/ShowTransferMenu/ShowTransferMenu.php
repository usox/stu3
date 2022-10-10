<?php

declare(strict_types=1);

namespace Stu\Module\Trade\View\ShowTransferMenu;

use Stu\Exception\AccessViolation;
use Stu\Module\Commodity\CommodityTypeEnum;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Trade\Lib\TradeLibFactoryInterface;
use Stu\Orm\Repository\StorageRepositoryInterface;
use Stu\Orm\Repository\TradePostRepositoryInterface;

final class ShowTransferMenu implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_OFFER_MENU_TRANSFER';

    private ShowTransferMenueRequestInterface $showTransferMenueRequest;

    private TradeLibFactoryInterface $tradeLibFactory;

    private TradePostRepositoryInterface $tradePostRepository;

    private StorageRepositoryInterface $storageRepository;

    public function __construct(
        ShowTransferMenueRequestInterface $showTransferMenueRequest,
        TradeLibFactoryInterface $tradeLibFactory,
        TradePostRepositoryInterface $tradePostRepository,
        StorageRepositoryInterface $storageRepository
    ) {
        $this->showTransferMenueRequest = $showTransferMenueRequest;
        $this->tradeLibFactory = $tradeLibFactory;
        $this->tradePostRepository = $tradePostRepository;
        $this->storageRepository = $storageRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();

        $storage = $this->storageRepository->find($this->showTransferMenueRequest->getStorageId());
        if ($storage === null || $storage->getUserId() !== $userId) {
            throw new AccessViolation();
        }

        $tradePost = $storage->getTradePost();

        $accounts = $this->tradePostRepository->getByUserLicense($userId);

        $trade_post_list = [];
        foreach ($accounts as $obj) {
            if ($tradePost->getId() != $obj->getId() && $obj->getTradeNetwork() == $tradePost->getTradeNetwork()) {
                $trade_post_list[] = $this->tradeLibFactory->createTradePostStorageManager($obj, $userId);
            }
        }

        $game->showMacro('html/trademacros.xhtml/newoffermenu_transfer');
        $game->setPageTitle(sprintf(
            _('Management %s'),
            $storage->getCommodity()->getName()
        ));
        $game->setTemplateVar('STOR', $storage);
        $game->setTemplateVar('IS_DILITHIUM', $storage->getCommodityId() === CommodityTypeEnum::COMMODITY_DILITHIUM);
        $game->setTemplateVar(
            'TRADE_POST',
            $this->tradeLibFactory->createTradeAccountTal($tradePost, $userId)
        );
        $game->setTemplateVar('IS_NPC_POST', $tradePost->getId < 18);
        $game->setTemplateVar(
            'AVAILABLE_TRADE_POSTS',
            $trade_post_list
        );
    }
}
