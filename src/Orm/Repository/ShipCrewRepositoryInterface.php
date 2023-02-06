<?php

namespace Stu\Orm\Repository;

use Doctrine\Persistence\ObjectRepository;
use Stu\Orm\Entity\ShipCrew;
use Stu\Orm\Entity\ShipCrewInterface;
use Stu\Orm\Entity\UserInterface;

/**
 * @extends ObjectRepository<ShipCrew>
 *
 * @method null|ShipCrewInterface find(integer $id)
 */
interface ShipCrewRepositoryInterface extends ObjectRepository
{
    public function prototype(): ShipCrewInterface;

    public function save(ShipCrewInterface $post): void;

    public function delete(ShipCrewInterface $post): void;

    /**
     * @return ShipCrewInterface[]
     */
    public function getByShip(int $shipId): array;

    /**
     * @return ShipCrewInterface[]
     */
    public function getByShipAndSlot(int $shipId, int $slotId): array;

    //VIA LOCATION
    /**
     * @return ShipCrewInterface[]
     */
    public function getByUserAtColonies(int $userId): array;

    /**
     * @return ShipCrewInterface[]
     */
    public function getByUserOnEscapePods(int $userId): array;

    /**
     * @return ShipCrewInterface[]
     */
    public function getByUserAtTradeposts(int $userId): array;

    //AMOUNT
    public function getAmountByUser(UserInterface $user): int;

    public function getAmountByUserAtTradeposts(UserInterface $user): int;

    public function getAmountByUserOnColonies(int $userId): int;

    public function getAmountByUserOnShips(UserInterface $user): int;

    /**
     * @return array<array{user_id: int, race: int, crewc: int}>
     */
    public function getCrewsTop10(): array;

    /**
     * @return array<array{id: int, name: string, sector: string, amount: int}>
     */
    public function getOrphanedSummaryByUserAtTradeposts(int $userId): array;

    public function truncateByShip(int $shipId): void;

    public function truncateByUser(int $userId): void;
}
