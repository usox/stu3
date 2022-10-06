<?php

declare(strict_types=1);

namespace Stu\Module\Trade\Action\CreateOffer;

use Stu\Exception\AccessViolation;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Trade\Lib\TradeLibFactoryInterface;
use Stu\Module\Trade\View\ShowAccounts\ShowAccounts;
use Stu\Orm\Entity\CommodityInterface;
use Stu\Orm\Entity\TradeOfferInterface;
use Stu\Orm\Entity\TradePostInterface;
use Stu\Orm\Entity\UserInterface;
use Stu\Orm\Repository\CommodityRepositoryInterface;
use Stu\Orm\Repository\StorageRepositoryInterface;
use Stu\Orm\Repository\TradeOfferRepositoryInterface;
use Stu\Orm\Repository\TradeStorageRepositoryInterface;

final class CreateOffer implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_CREATE_OFFER';

    private CreateOfferRequestInterface $createOfferRequest;

    private CommodityRepositoryInterface $commodityRepository;

    private TradeLibFactoryInterface $tradeLibFactory;

    private TradeOfferRepositoryInterface $tradeOfferRepository;

    private TradeStorageRepositoryInterface $tradeStorageRepository;

    private StorageRepositoryInterface $storageRepository;

    public function __construct(
        CreateOfferRequestInterface $createOfferRequest,
        CommodityRepositoryInterface $commodityRepository,
        TradeLibFactoryInterface $tradeLibFactory,
        TradeOfferRepositoryInterface $tradeOfferRepository,
        TradeStorageRepositoryInterface $tradeStorageRepository,
        StorageRepositoryInterface $storageRepository
    ) {
        $this->createOfferRequest = $createOfferRequest;
        $this->commodityRepository = $commodityRepository;
        $this->tradeLibFactory = $tradeLibFactory;
        $this->tradeOfferRepository = $tradeOfferRepository;
        $this->tradeStorageRepository = $tradeStorageRepository;
        $this->storageRepository = $storageRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowAccounts::VIEW_IDENTIFIER);

        $userId = $game->getUser()->getId();

        $storage = $this->tradeStorageRepository->find($this->createOfferRequest->getStorageId());
        if ($storage === null) {
            throw new AccessViolation(sprintf("Storage not existent! Fool: %d", $userId));
        }
        if ($storage->getUserId() !== $userId) {
            throw new AccessViolation(sprintf("Storage belongs to other user! Fool: %d", $userId));
        }

        $tradePost = $storage->getTradePost();

        $giveGoodId = $this->createOfferRequest->getGiveGoodId();
        $giveAmount = $this->createOfferRequest->getGiveAmount();
        $wantedGoodId = $this->createOfferRequest->getWantedGoodId();
        $wantedAmount = $this->createOfferRequest->getWantedAmount();
        $offerAmount = $this->createOfferRequest->getOfferAmount();

        if ($giveGoodId === $wantedGoodId) {
            $game->addInformation("Es kann nicht die gleiche Ware eingetauscht werden");
            return;
        }
        if ($giveAmount < 1) {
            $game->addInformation("Es wurde keine angebotene Menge angeben");
            return;
        }

        if ($wantedAmount < 1) {
            $game->addInformation("Es wurde keine verlangte Menge");
            return;
        }

        if ($offerAmount < 1) {
            $game->addInformation("Es wurde keine Anzahl an Angeboten angegeben");
            return;
        }

        $offeredCommodity = $this->commodityRepository->find($giveGoodId);
        if ($offeredCommodity === null) {
            return;
        }
        $wantedCommodity = $this->commodityRepository->find($wantedGoodId);
        if ($wantedCommodity === null) {
            return;
        }

        // is tradeable?
        if (!$offeredCommodity->isTradeable() || !$wantedCommodity->isTradeable()) {
            return;
        }

        // is there already an equal offer?
        if ($this->isEquivalentOfferExistent(
            $userId,
            $tradePost->getId(),
            $giveGoodId,
            $giveAmount,
            $wantedGoodId,
            $wantedAmount
        )) {
            $game->addInformation("Du hast auf diesem Handelsposten bereits ein vergleichbares Angebot");
            return;
        }

        $storageManager = $this->tradeLibFactory->createTradePostStorageManager($tradePost, $userId);

        if ($storageManager->getFreeStorage() <= 0) {
            $game->addInformation("Dein Warenkonto auf diesem Handelsposten ist überfüllt - Angebot kann nicht erstellt werden");
            return;
        }

        if ($offerAmount < 1 || $offerAmount > 99) {
            $offerAmount = 1;
        }
        if ($offerAmount * $giveAmount > $storage->getAmount()) {
            $offerAmount = floor($storage->getAmount() / $giveAmount);
        }
        if ($offerAmount < 1) {
            return;
        }

        $offer = $this->saveOffer(
            $game->getUser(),
            $tradePost,
            $offeredCommodity,
            $giveAmount,
            $wantedCommodity,
            $wantedAmount,
            $offerAmount
        );

        $this->saveStorage($offer);

        $storageManager->lowerStorage($giveGoodId, (int) $offerAmount * $giveAmount);


        $game->addInformation('Das Angebot wurde erstellt');
    }

    private function saveOffer(
        UserInterface $user,
        TradePostInterface $tradePost,
        CommodityInterface $offeredCommodity,
        int $giveAmount,
        CommodityInterface $wantedCommodity,
        int $wantedAmount,
        int $offerAmount
    ): TradeOfferInterface {
        $offer = $this->tradeOfferRepository->prototype();
        $offer->setUser($user);
        $offer->setTradePost($tradePost);
        $offer->setDate(time());
        $offer->setOfferedCommodity($offeredCommodity);
        $offer->setOfferedGoodCount($giveAmount);
        $offer->setWantedCommodity($wantedCommodity);
        $offer->setWantedGoodCount($wantedAmount);
        $offer->setOfferCount($offerAmount);

        $this->tradeOfferRepository->save($offer);

        return $offer;
    }

    private function saveStorage(TradeOfferInterface $tradeOffer): void
    {
        $storage = $this->storageRepository->prototype();
        $storage->setUserId($tradeOffer->getUser()->getId());
        $storage->setTradeOffer($tradeOffer);
        $storage->setCommodity($tradeOffer->getOfferedCommodity());
        $storage->setAmount($tradeOffer->getOfferedGoodCount() * $tradeOffer->getOfferCount());

        $this->storageRepository->save($storage);
    }

    private function isEquivalentOfferExistent(
        int $userId,
        int $tradePostId,
        int $giveGoodId,
        int $giveAmount,
        int $wantedGoodId,
        int $wantedAmount
    ): bool {
        $offers = $this->tradeOfferRepository->getByTradePostAndUserAndCommodities($tradePostId, $userId, $giveGoodId, $wantedGoodId);

        foreach ($offers as $offer) {
            if (round($giveAmount / $wantedAmount, 2) == round($offer->getOfferedGoodCount() / $offer->getWantedGoodCount(), 2)) {
                return true;
            }
        }

        return false;
    }

    public function performSessionCheck(): bool
    {
        return false;
    }
}
