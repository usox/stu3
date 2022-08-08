<?php

declare(strict_types=1);

namespace Stu\Module\Trade\Action\CreateLicence;

use Stu\Exception\AccessViolation;
use Stu\Module\Commodity\CommodityTypeEnum;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Trade\Lib\TradeLibFactoryInterface;
use Stu\Module\Trade\View\ShowAccounts\ShowAccounts;
use Stu\Orm\Entity\CommodityInterface;
use Stu\Orm\Repository\CommodityRepositoryInterface;
use Stu\Orm\Repository\TradeCreateLicenceRepositoryInterface;
use Stu\Orm\Repository\TradePostRepositoryInterface;

final class CreateLicence implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_CREATE_LICENCE';

    private CreateLicenceRequestInterface $createLicenceRequest;

    private CommodityRepositoryInterface $commodityRepository;

    private TradeLibFactoryInterface $tradeLibFactory;

    private TradeCreateLicenceRepositoryInterface $tradeLicenceRepository;

    private TradePostRepositoryInterface $tradePostRepository;

    public function __construct(
        CreateLicenceRequestInterface $createLicenceRequest,
        CommodityRepositoryInterface $commodityRepository,
        TradeLibFactoryInterface $tradeLibFactory,
        TradeCreateLicenceRepositoryInterface $tradeLicenceRepository,
        TradePostRepositoryInterface $tradePostRepository
    ) {
        $this->createLicenceRequest = $createLicenceRequest;
        $this->commodityRepository = $commodityRepository;
        $this->tradeLibFactory = $tradeLibFactory;
        $this->tradeLicenceRepository = $tradeLicenceRepository;
        $this->tradePostRepository = $tradePostRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowAccounts::VIEW_IDENTIFIER);

        $userId = $game->getUser()->getId();

        $trade_post = $this->tradePostRepository->find($this->createLicenceRequest->getTradePostId());
        if ($trade_post === null) {
            return;
        }
        if ($trade_post->getUserId() !== $userId) {
            throw new AccessViolation(sprintf("Tradepost belongs to other user! Fool: %d", $userId));
        }

        $wantedGoodId = $this->createLicenceRequest->getWantedLicenceGoodId();
        $giveAmount = $this->createLicenceRequest->getWantedLicenceAmount();
        $licenceDays = $this->createLicenceRequest->getLicenceDays();


        if ($giveAmount < 1 || $wantedGoodId < 1 ) {
            return;
        }

        $setLicence = $this->tradeLicenceRepository->prototype();
        $setLicence->setTradePost($trade_post);
        $setLicence->setDate(time());
        $setLicence->setwantedGoodId($this->commodityRepository->find($wantedGoodId));
        $setLicence->setLicenceGoodCount((int) $giveAmount);
        $setLicence->setLicenceDays($licenceDays);

        $this->tradeLicenceRepository->save($setLicence);


        $game->addInformation('Handelslizenz geändert');
    }

    public function performSessionCheck(): bool
    {
        return false;
    }
}