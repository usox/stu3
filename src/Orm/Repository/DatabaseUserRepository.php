<?php

declare(strict_types=1);

namespace Stu\Orm\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Stu\Orm\Entity\DatabaseEntry;
use Stu\Orm\Entity\DatabaseUser;
use Stu\Orm\Entity\DatabaseUserInterface;

/**
 * @extends EntityRepository<DatabaseUser>
 */
final class DatabaseUserRepository extends EntityRepository implements DatabaseUserRepositoryInterface
{

    public function truncateByUserId(int $userId)
    {
        $this->getEntityManager()->createQuery(
            sprintf(
                'delete from %s d where d.user_id = :userId',
                DatabaseUser::class
            )
        )
            ->setParameter('userId', $userId)
            ->execute();
    }

    public function findFor(int $databaseEntryId, int $userId): ?DatabaseUserInterface
    {
        return $this->findOneBy([
            'user_id' => $userId,
            'database_id' => $databaseEntryId,
        ]);
    }

    public function exists(int $userId, int $databaseEntryId): bool
    {
        return $this->count([
            'user_id' => $userId,
            'database_id' => $databaseEntryId
        ]) > 0;
    }

    public function prototype(): DatabaseUserInterface
    {
        return new DatabaseUser();
    }

    public function save(DatabaseUserInterface $entry): void
    {
        $em = $this->getEntityManager();
        $em->persist($entry);
        $em->flush();
    }

    public function getTopList(): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('user_id', 'user_id', 'integer');
        $rsm->addScalarResult('points', 'points', 'integer');
        return $this->getEntityManager()->createNativeQuery(
            'SELECT dbu.user_id, SUM(dbc.points) as points FROM stu_database_user dbu LEFT JOIN
            stu_database_entrys dbe ON dbe.id = dbu.database_id LEFT JOIN stu_database_categories dbc ON
            dbc.id = dbe.category_id WHERE dbu.user_id > 100 GROUP BY dbu.user_id ORDER BY points DESC LIMIT 10',
            $rsm
        )->getArrayResult();
    }

    public function getCountForUser(int $userId): int
    {
        return (int) $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'SELECT COUNT(dbu.id) FROM %s dbu WHERE dbu.user_id = :userId',
                    DatabaseUser::class
                )
            )
            ->setParameters([
                'userId' => $userId
            ])
            ->getSingleScalarResult();
    }

    public function hasUserCompletedCategory(int $userId, int $categoryId): bool
    {
        return (int) $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'SELECT count(de.id)
                    FROM %s de
                    WHERE de.category_id = :categoryId
                    AND NOT EXISTS
                        (SELECT du.id
                        FROM %s du
                        WHERE du.database_id = de.id
                        AND du.user_id = :userId)',
                    DatabaseEntry::class,
                    DatabaseUser::class
                )
            )
            ->setParameters([
                'userId' => $userId,
                'categoryId' => $categoryId
            ])
            ->getSingleScalarResult() == 0;
    }
}
