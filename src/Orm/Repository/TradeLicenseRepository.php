<?php

declare(strict_types=1);

namespace Stu\Orm\Repository;

use Doctrine\ORM\EntityRepository;
use Stu\Orm\Entity\TradeLicense;
use Stu\Orm\Entity\TradeLicenseInterface;
use Stu\Orm\Entity\TradePost;

final class TradeLicenseRepository extends EntityRepository implements TradeLicenseRepositoryInterface
{

    public function prototype(): TradeLicenseInterface
    {
        return new TradeLicense();
    }

    public function save(TradeLicenseInterface $post): void
    {
        $em = $this->getEntityManager();

        $em->persist($post);
    }

    public function delete(TradeLicenseInterface $post): void
    {
        $em = $this->getEntityManager();

        $em->remove($post);
        $em->flush();
    }

    public function truncateByUser(int $userId): void
    {
        $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'DELETE FROM %s t WHERE t.user_id = :userId',
                    TradeLicense::class
                )
            )
            ->setParameter('userId', $userId)
            ->execute();
    }

    public function truncateByUserAndTraitpost(int $userId, int $tradePostId): void
    {
        $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'DELETE FROM %s t WHERE t.user_id = :userId AND t.posts_id = :TradePostId',
                    TradeLicense::class
                )
            )
            ->setParameter('userId', $userId)
            ->setParameter('TradePostId', $tradePostId)
            ->execute();
    }

    public function getByTradePost(int $tradePostId): array
    {
        return $this->findBy([
            'posts_id' => $tradePostId
        ]);
    }

    public function getByTradePostAndExpired(int $tradePostId): array
    {
        return $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'SELECT tl FROM %s tl WHERE tl.posts_id = :tradePostId AND tl.expired > :actualTime',
                    TradeLicense::class
                )
            )
            ->setParameters([
                'tradePostId' => $tradePostId,
                'actualTime' => time()
            ])
            ->getResult();
    }

    public function getByUser(int $userId): array
    {
        return $this->findBy(
            [
                'user_id' => $userId
            ],
            ['posts_id' => 'asc']
        );
    }

    public function getLicencesCountbyUser(int $userId): array
    {
        $time = time();
        return $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'SELECT tl FROM %s tl WHERE tl.user_id = :userId AND tl.expired > :actime',
                    TradeLicense::class
                )
            )
            ->setParameters([
                'userId' => $userId,
                'actime' => $time
            ])
            ->getResult();
    }

    public function getAmountByUser(int $userId): int
    {
        return $this->count([
            'user_id' => $userId
        ]);
    }

    public function hasLicenseByUserAndTradePost(int $userId, int $tradePostId): bool
    {
        return (int) $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'SELECT COUNT(c.id) as amount
                    FROM %s c
                    WHERE c.user_id = :userId
                        AND c.posts_id = :tradePostId
                        AND c.expired > :actime',
                    TradeLicense::class
                )
            )
            ->setParameters([
                'userId' => $userId,
                'tradePostId' => $tradePostId,
                'actime' => time()
            ])
            ->getSingleScalarResult() > 0;
    }

    public function getAmountByTradePost(int $tradePostId): int
    {
        return $this->count([
            'posts_id' => $tradePostId
        ]);
    }

    public function hasLicenseByUserAndNetwork(int $userId, int $tradeNetworkId): bool
    {
        return (int) $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'SELECT COUNT(tl.id)
                    FROM %s tl
                    WHERE tl.user_id = :userId
                        AND tl.posts_id IN (
                            SELECT id FROM %s WHERE trade_network = :tradeNetworkId
                            )',
                    TradeLicense::class,
                    TradePost::class
                )
            )
            ->setParameters([
                'userId' => $userId,
                'tradeNetworkId' => $tradeNetworkId
            ])
            ->getSingleScalarResult() > 0;
    }
}
