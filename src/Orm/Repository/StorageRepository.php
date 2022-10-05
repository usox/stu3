<?php

declare(strict_types=1);

namespace Stu\Orm\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Stu\Orm\Entity\Storage;
use Stu\Orm\Entity\StorageInterface;

final class StorageRepository extends EntityRepository implements StorageRepositoryInterface
{
    public function prototype(): StorageInterface
    {
        return new Storage();
    }

    public function save(StorageInterface $storage): void
    {
        $em = $this->getEntityManager();

        $em->persist($storage);
    }

    public function delete(StorageInterface $storage): void
    {
        $em = $this->getEntityManager();

        $em->remove($storage);
    }

    public function getByUserAccumulated(int $userId): iterable
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('commodity_id', 'commodity_id', 'integer');
        $rsm->addScalarResult('amount', 'amount', 'integer');

        return $this->getEntityManager()->createNativeQuery(
            'SELECT s.commodity_id AS commodity_id, SUM(s.count) AS amount
            FROM stu_storage s
            WHERE s.user_id = :userId
            GROUP BY s.commodity_id
            ORDER BY s.commodity_id ASC',
            $rsm
        )->setParameters([
            'userId' => $userId
        ])->getResult();
    }

    public function getShipStorageByUserAndCommodity(int $userId, int $commodityId): iterable
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('commodity_id', 'commodity_id', 'integer');
        $rsm->addScalarResult('ships_id', 'ships_id', 'integer');
        $rsm->addScalarResult('amount', 'amount', 'integer');

        return $this->getEntityManager()->createNativeQuery(
            'SELECT s.commodity_id AS commodity_id, s.ship_id AS ships_id, s.count AS amount
            FROM stu_storage s
            LEFT JOIN stu_goods g ON g.id = s.commodity_id
            WHERE s.user_id = :userId
            AND s.ship_id IS NOT NULL
            AND g.id = :commodityId
            ORDER BY s.count DESC',
            $rsm
        )->setParameters([
            'userId' => $userId,
            'commodityId' => $commodityId
        ])->getResult();
    }
}
