<?php

namespace Stu\Orm\Repository;

use Doctrine\Persistence\ObjectRepository;
use Stu\Orm\Entity\TradePost;
use Stu\Orm\Entity\TradePostInterface;
use Stu\Orm\Entity\UserInterface;

/**
 * @extends ObjectRepository<TradePost>
 *
 * @method null|TradePostInterface find(integer $id)
 */
interface TradePostRepositoryInterface extends ObjectRepository
{
    public function prototype(): TradePostInterface;

    public function save(TradePostInterface $tradePost): void;

    public function delete(TradePostInterface $tradePost): void;

    /**
     * @return TradePostInterface[]
     */
    public function getByUser(int $userId): array;

    /**
     * @return TradePostInterface[]
     */
    public function getByUserLicense(int $userId): array;

    /**
     * @return array<TradePostInterface>
     */
    public function getByUserLicenseOnlyNPC(int $userId): array;

    /**
     * @return array<TradePostInterface>
     */
    public function getByUserLicenseOnlyFerg(int $userId): array;

    public function getClosestNpcTradePost(int $cx, int $cy): TradePostInterface;

    public function getFergTradePost(
        int $tradePostId
    ): ?TradePostInterface;


    /**
     * @return UserInterface[]
     */
    public function getUsersWithStorageOnTradepost(int $tradePostId): array;
}
