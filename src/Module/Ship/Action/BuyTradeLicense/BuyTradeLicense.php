<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\BuyTradeLicense;

use request;
use Stu\Component\Game\GameEnum;
use Stu\Component\Game\TimeConstants;
use Stu\Module\Message\Lib\PrivateMessageFolderSpecialEnum;
use Stu\Module\Message\Lib\PrivateMessageSenderInterface;
use Stu\Module\Ship\Lib\InteractionCheckerInterface;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Component\Ship\Storage\ShipStorageManagerInterface;
use Stu\Module\Ship\View\ShowTradeMenu\ShowTradeMenu;
use Stu\Module\Trade\Lib\TradeLibFactoryInterface;
use Stu\Orm\Entity\TradePostInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\Orm\Repository\TradeLicenseRepositoryInterface;
use Stu\Orm\Repository\TradePostRepositoryInterface;
use Stu\Orm\Repository\CommodityRepositoryInterface;
use Stu\Orm\Repository\TradeLicenseInfoRepositoryInterface;

final class BuyTradeLicense implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_PAY_TRADELICENSE';

    private ShipLoaderInterface $shipLoader;

    private TradeLicenseRepositoryInterface $tradeLicenseRepository;

    private TradeLicenseInfoRepositoryInterface $tradeCreateLicenseRepository;

    private TradeLibFactoryInterface $tradeLibFactory;

    private TradePostRepositoryInterface $tradePostRepository;

    private ShipStorageManagerInterface $shipStorageManager;

    private CommodityRepositoryInterface $commodityRepository;

    private ShipRepositoryInterface $shipRepository;

    private PrivateMessageSenderInterface $privateMessageSender;

    private InteractionCheckerInterface $interactionChecker;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        TradeLicenseRepositoryInterface $tradeLicenseRepository,
        TradeLicenseInfoRepositoryInterface $tradeCreateLicenseRepository,
        TradeLibFactoryInterface $tradeLibFactory,
        TradePostRepositoryInterface $tradePostRepository,
        ShipStorageManagerInterface $shipStorageManager,
        ShipRepositoryInterface $shipRepository,
        CommodityRepositoryInterface $commodityRepository,
        PrivateMessageSenderInterface $privateMessageSender,
        InteractionCheckerInterface $interactionChecker
    ) {
        $this->shipLoader = $shipLoader;
        $this->tradeLicenseRepository = $tradeLicenseRepository;
        $this->tradeCreateLicenseRepository = $tradeCreateLicenseRepository;
        $this->tradeLibFactory = $tradeLibFactory;
        $this->tradePostRepository = $tradePostRepository;
        $this->shipStorageManager = $shipStorageManager;
        $this->shipRepository = $shipRepository;
        $this->commodityRepository = $commodityRepository;
        $this->privateMessageSender = $privateMessageSender;
        $this->interactionChecker = $interactionChecker;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowTradeMenu::VIEW_IDENTIFIER, ['noAjaxTemplate']);

        $userId = $game->getUser()->getId();

        $ship = $this->shipLoader->getByIdAndUser(
            request::indInt('id'),
            $userId
        );

        /** @var TradePostInterface $tradepost */
        $tradepost = $this->tradePostRepository->find((int) request::getIntFatal('postid'));
        if ($tradepost === null) {
            return;
        }

        if (!$this->interactionChecker->checkPosition($ship, $tradepost->getShip())) {
            return;
        }
        $targetId = (int) request::getIntFatal('target');
        $mode = request::getStringFatal('method');

        if ($this->tradeLicenseRepository->getAmountByUser($userId) >= GameEnum::MAX_TRADELICENSE_COUNT) {
            return;
        }

        if ($this->tradeLicenseRepository->hasLicenseByUserAndTradePost($userId, (int) $tradepost->getId())) {
            return;
        }

        $licenseInfo = $this->tradeCreateLicenseRepository->getLatestLicenseInfo($tradepost->getId());
        $commodityId = $licenseInfo->getCommodityId();
        $commodity = $this->commodityRepository->find($commodityId);
        $costs = $licenseInfo->getAmount();

        switch ($mode) {
            case 'ship':
                $obj = $this->shipRepository->find($targetId);
                if ($obj === null || $obj->getUser()->getId() !== $userId) {
                    return;
                }
                if (!$this->interactionChecker->checkPosition($tradepost->getShip(), $obj)) {
                    return;
                }

                $storageManagerRemote = $this->tradeLibFactory->createTradePostStorageManager($tradepost, $tradepost->getUser());
                $storage = $obj->getStorage()[$commodityId] ?? null;
                if ($storage === null || $storage->getAmount() < $costs) {
                    return;
                }
                $storageManagerRemote->upperStorage($commodityId, $costs);
                $this->shipStorageManager->lowerStorage(
                    $obj,
                    $commodity,
                    $costs
                );
                break;
            case 'account':
                /** @var TradePostInterface $targetTradepost */
                $targetTradepost = $this->tradePostRepository->find($targetId);
                if ($targetTradepost === null) {
                    return;
                }

                $storageManagerRemote = $this->tradeLibFactory->createTradePostStorageManager($tradepost, $tradepost->getUser());
                $storageManager = $this->tradeLibFactory->createTradePostStorageManager($targetTradepost, $game->getUser());

                $stor = $storageManager->getStorage()->get($commodityId) ?? null;
                if ($stor === null) {
                    return;
                }
                if ($stor->getAmount() < $costs) {
                    return;
                }
                if ($targetTradepost->getTradeNetwork() != $tradepost->getTradeNetwork()) {
                    return;
                }

                $storageManagerRemote->upperStorage($commodityId, $costs);
                $storageManager->lowerStorage($commodityId, $costs);
                break;
            default:
                return;
        }

        $license = $this->tradeLicenseRepository->prototype();
        $license->setTradePost($tradepost);
        $license->setUser($game->getUser());
        $license->setDate(time());
        $license->setExpired(time() + $licenseInfo->getDays() * TimeConstants::ONE_DAY_IN_SECONDS);

        $game->addInformation('Handelslizenz wurde erteilt');

        $this->tradeLicenseRepository->save($license);
        $this->privateMessageSender->send(
            $userId,
            $tradepost->getUserId(),
            sprintf(
                'Am %s wurde eine Lizenz gekauft',
                $tradepost->getName()
            ),
            PrivateMessageFolderSpecialEnum::PM_SPECIAL_TRADE
        );
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
