<?php

namespace Stu\Orm\Repository;

use Doctrine\Persistence\ObjectRepository;
use Stu\Orm\Entity\TorpedoHullInterface;
use Stu\Orm\Entity\TorpedoHull;

/**
 * @extends ObjectRepository<TorpedoHull>
 *
 * @method null|TorpedoHullInterface find(integer $id)
 */
interface TorpedoHullRepositoryInterface extends ObjectRepository
{
    public function prototype(): TorpedoHullInterface;

    public function save(TorpedoHullInterface $storage): void;

    public function delete(TorpedoHullInterface $storage): void;

    public function getByModuleAndTorpedo(
        int $moduleId,
        int $torpedoId
    ): ?TorpedoHullInterface;
}