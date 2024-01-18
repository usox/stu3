<?php

declare(strict_types=1);

namespace Stu\Module\Colony\Lib;

use Stu\Exception\AccessViolation;
use Stu\Exception\EntityLockedException;
use Stu\Module\Tick\Lock\LockTypeEnum;
use Stu\Module\Tick\Lock\LockManagerInterface;
use Stu\Orm\Entity\ColonyInterface;
use Stu\Orm\Repository\ColonyRepositoryInterface;

final class ColonyLoader implements ColonyLoaderInterface
{
    private ColonyRepositoryInterface $colonyRepository;

    private LockManagerInterface $lockManager;

    public function __construct(
        ColonyRepositoryInterface $colonyRepository,
        LockManagerInterface $lockManager
    ) {
        $this->colonyRepository = $colonyRepository;
        $this->lockManager = $lockManager;
    }

    public function loadWithOwnerValidation(int $colonyId, int $userId, bool $checkForEntityLock = true): ColonyInterface
    {
        $colony = $this->loadInternal($colonyId, $checkForEntityLock);

        if ($colony->getUserId() !== $userId) {
            throw new AccessViolation(sprintf("Colony owned by another user (%d)! Fool: %d", $colony->getUserId(), $userId));
        }

        return $colony;
    }

    public function load(int $colonyId, bool $checkForEntityLock = true): ColonyInterface
    {
        return $this->loadInternal($colonyId, $checkForEntityLock);
    }

    private function loadInternal(int $colonyId, bool $checkForEntityLock): ColonyInterface
    {
        if ($checkForEntityLock && $this->lockManager->isLocked($colonyId, LockTypeEnum::COLONY_GROUP)) {
            throw new EntityLockedException('Tick läuft gerade, Zugriff auf Kolonie ist daher blockiert');
        }

        $colony = $this->colonyRepository->find($colonyId);
        if ($colony === null) {
            throw new AccessViolation("Colony not existent!");
        }

        return $colony;
    }
}
