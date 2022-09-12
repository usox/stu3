<?php

declare(strict_types=1);

namespace Stu\Module\Database\Lib;

use Stu\Component\Database\DatabaseCategoryTypeEnum;
use Stu\Orm\Entity\DatabaseEntryInterface;
use Stu\Orm\Entity\UserInterface;
use Stu\Orm\Repository\DatabaseEntryRepositoryInterface;
use Stu\Orm\Repository\DatabaseUserRepositoryInterface;
use Stu\Orm\Repository\PrestigeLogRepositoryInterface;
use Stu\Orm\Repository\UserAwardRepositoryInterface;

final class CreateDatabaseEntry implements CreateDatabaseEntryInterface
{
    private DatabaseEntryRepositoryInterface $databaseEntryRepository;

    private DatabaseUserRepositoryInterface $databaseUserRepository;

    private UserAwardRepositoryInterface $userAwardRepository;

    private PrestigeLogRepositoryInterface $prestigeLogRepository;

    public function __construct(
        DatabaseEntryRepositoryInterface $databaseEntryRepository,
        DatabaseUserRepositoryInterface $databaseUserRepository,
        UserAwardRepositoryInterface $userAwardRepository,
        PrestigeLogRepositoryInterface $prestigeLogRepository
    ) {
        $this->databaseEntryRepository = $databaseEntryRepository;
        $this->databaseUserRepository = $databaseUserRepository;
        $this->userAwardRepository = $userAwardRepository;
        $this->prestigeLogRepository = $prestigeLogRepository;
    }

    public function createDatabaseEntryForUser(UserInterface $user, int $databaseEntryId): ?DatabaseEntryInterface
    {
        if ($databaseEntryId === 0) {
            return null;
        }

        $databaseEntry = $this->databaseEntryRepository->find($databaseEntryId);

        if ($databaseEntry === null) {
            return null;
        }

        //create new user entry
        $userEntry = $this->databaseUserRepository->prototype()
            ->setUser($user)
            ->setDatabaseEntry($databaseEntry)
            ->setDate(time());

        $this->databaseUserRepository->save($userEntry);


        if ($user->getId() > 100) {

            //create prestige log
            $this->createPrestigeLog($databaseEntry, $user->getId());

            $this->checkForCompletion($user, $databaseEntry->getCategory()->getId());
        }

        return $databaseEntry;
    }

    private function createPrestigeLog(DatabaseEntryInterface $databaseEntry, int $userId): void
    {
        $prestigeLog = $this->prestigeLogRepository->prototype();
        $prestigeLog->setUserId($userId);
        $prestigeLog->setAmount($databaseEntry->getCategory()->getPrestige());
        $prestigeLog->setDescription(sprintf(
            '%d Prestige erhalten für die Entdeckung von "%s" in der Kategorie "%s"',
            $prestigeLog->getAmount(),
            $databaseEntry->getDescription(),
            $databaseEntry->getCategory()->getDescription()
        ));

        $this->prestigeLogRepository->save($prestigeLog);
    }

    private function checkForCompletion(UserInterface $user, int $categoryId): void
    {
        if ($this->databaseUserRepository->hasUserCompletedCategory($user->getId(), $categoryId)) {

            //check if an award is configured for this category
            //TODO add award reference to database category
            if (!array_key_exists($categoryId, DatabaseCategoryTypeEnum::CATEGORY_TO_AWARD)) {
                return;
            }

            $award = $this->userAwardRepository->prototype();
            $award->setUser($user);
            $award->setType(DatabaseCategoryTypeEnum::CATEGORY_TO_AWARD[$categoryId]);

            $this->userAwardRepository->save($award);
        }
    }
}
