<?php

declare(strict_types=1);

namespace Stu\Orm\Repository;

use Doctrine\ORM\EntityRepository;
use Stu\Module\Ship\Action\OpenAdventDoor\OpenAdventDoor;
use Stu\Orm\Entity\GameRequest;
use Stu\Orm\Entity\GameRequestInterface;

/**
 * @extends EntityRepository<GameRequest>
 */
final class GameRequestRepository extends EntityRepository implements GameRequestRepositoryInterface
{
    public function prototype(): GameRequestInterface
    {
        return new GameRequest();
    }

    public function save(GameRequestInterface $gameRequest): void
    {
        $em = $this->getEntityManager();
        $em->persist($gameRequest);
    }

    public function delete(GameRequestInterface $gameRequest): void
    {
        $em = $this->getEntityManager();
        $em->remove($gameRequest);
    }

    public function getOpenAdventDoorTriesForUser(int $userId): int
    {
        return (int) $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'SELECT COUNT(gr.id) FROM %s gr
                    WHERE gr.user_id = :userId
                    AND gr.params LIKE :params
                    AND gr.action = :action',
                    GameRequest::class
                )
            )
            ->setParameters([
                'userId' => $userId,
                'params' => sprintf('%%[advent] => %s%%', date("d.m.y")),
                'action' => OpenAdventDoor::ACTION_IDENTIFIER
            ])
            ->getSingleScalarResult();
    }
}
