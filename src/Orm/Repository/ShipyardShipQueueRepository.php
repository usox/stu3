<?php

declare(strict_types=1);

namespace Stu\Orm\Repository;

use Doctrine\ORM\EntityRepository;
use Stu\Orm\Entity\ShipyardShipQueue;
use Stu\Orm\Entity\ShipyardShipQueueInterface;

final class ShipyardShipQueueRepository extends EntityRepository implements ShipyardShipQueueRepositoryInterface
{
    public function prototype(): ShipyardShipQueueInterface
    {
        return new ShipyardShipQueue();
    }

    public function save(ShipyardShipQueueInterface $post): void
    {
        $em = $this->getEntityManager();

        $em->persist($post);
    }

    public function delete(ShipyardShipQueueInterface $post): void
    {
        $em = $this->getEntityManager();

        $em->remove($post);
        $em->flush();
    }

    public function getAmountByShipyard(int $shipId): int
    {
        return $this->count([
            'ship_id' => $shipId
        ]);
    }

    public function stopQueueByShipyard(int $shipId): void
    {
        $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'UPDATE %s sq SET sq.stop_date = :time WHERE sq.ship_id = :shipId',
                    ShipyardShipQueue::class
                )
            )
            ->setParameters([
                'time' => time(),
                'shipId' => $shipId
            ])
            ->execute();
    }

    public function restartQueueByShipyard(int $shipId): void
    {
        $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'UPDATE %s sq SET sq.finish_date = (:time + sq.finish_date - sq.stop_date), sq.stop_date = :stopDate WHERE sq.ship_id = :shipId',
                    ShipyardShipQueue::class
                )
            )
            ->setParameters([
                'stopDate' => 0,
                'time' => time(),
                'shipId' => $shipId
            ])
            ->execute();
    }
}
