<?php

declare(strict_types=1);

namespace Stu\Module\Trade\Lib;

use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Entity\TradePostInterface;
use Stu\Orm\Entity\TradeStorageInterface;
use Stu\Orm\Repository\TradeStorageRepositoryInterface;

final class BasicTradeAccountTal implements BasicTradeAccountTalInterface
{
    private TradeStorageRepositoryInterface $tradeStorageRepository;

    private TradePostInterface $tradePost;

    private array $basicTrades;

    private int $userId;

    private $storage;

    public function __construct(
        TradeStorageRepositoryInterface $tradeStorageRepository,
        TradePostInterface $tradePost,
        array $basicTrades,
        int $userId
    ) {
        $this->tradeStorageRepository = $tradeStorageRepository;
        $this->tradePost = $tradePost;
        $this->basicTrades = $basicTrades;
        $this->userId = $userId;
    }

    public function getId(): int
    {
        return $this->tradePost->getId();
    }

    public function getShip(): ShipInterface
    {
        return $this->tradePost->getShip();
    }

    public function getTradePostDescription(): string
    {
        return $this->tradePost->getDescription();
    }

    public function getBasicTradeItems(): array
    {
        $result = [];

        $storage = $this->getStorage();

        foreach ($this->basicTrades as $basicTrade) {
            $commodityId = $basicTrade->getCommodity()->getId();
            $result[] = new BasicTradeItem($basicTrade, $storage[$commodityId]);
        }

        return $result;
    }

    private function getStorage(): array
    {
        if ($this->storage === null) {
            $this->storage = $this->tradeStorageRepository->getByTradePostAndUser(
                $this->tradePost->getId(),
                $this->userId
            );
        }
        return $this->storage;
    }

    public function getStorageSum(): int
    {
        return array_reduce(
            $this->getStorage(),
            function (int $value, TradeStorageInterface $storage): int {
                return $value + $storage->getAmount();
            },
            0
        );
    }

    public function isOverStorage(): bool
    {
        return $this->getStorageSum() > $this->tradePost->getStorage();
    }

    public function getStorageCapacity(): int
    {
        return $this->tradePost->getStorage();
    }
}
