<?php

namespace Stu\Orm\Repository;

use Doctrine\Persistence\ObjectRepository;
use Stu\Orm\Entity\AllianceInterface;
use Stu\Orm\Entity\User;
use Stu\Orm\Entity\UserInterface;

/**
 * @extends ObjectRepository<User>
 *
 * @method null|UserInterface find(integer $id)
 * @method UserInterface[] findAll()
 */
interface UserRepositoryInterface extends ObjectRepository
{
    public function prototype(): UserInterface;

    public function save(UserInterface $post): void;

    public function delete(UserInterface $post): void;

    public function getByResetToken(string $resetToken): ?UserInterface;

    /**
     * @param list<int> $ignoreIds
     *
     * @return iterable<UserInterface>
     */
    public function getDeleteable(
        int $idleTimeThreshold,
        int $idleTimeVacationThreshold,
        array $ignoreIds
    ): iterable;

    /**
     * @return iterable<UserInterface>
     */
    public function getIdleRegistrations(
        int $idleTimeThreshold
    ): iterable;

    public function getByEmail(string $email): ?UserInterface;

    public function getByMobile(string $mobile, string $mobileHash): ?UserInterface;

    public function getByLogin(string $loginName): ?UserInterface;

    /**
     * Returns all members of the given alliance
     *
     * @return iterable<UserInterface>
     */
    public function getByAlliance(AllianceInterface $alliance): iterable;

    /**
     * @return list<UserInterface>
     */
    public function getList(
        string $sortField,
        string $sortOrder,
        ?int $limit,
        int $offset
    ): array;

    /**
     * @return iterable<UserInterface>
     */
    public function getFriendsByUserAndAlliance(UserInterface $user, ?AllianceInterface $alliance): iterable;

    /**
     * @return iterable<UserInterface>
     */
    public function getOrderedByLastaction(int $limit, int $ignoreUserId, int $lastActionThreshold): iterable;

    public function getActiveAmount(): int;

    public function getInactiveAmount(int $days): int;

    public function getVacationAmount(): int;

    public function getActiveAmountRecentlyOnline(int $threshold): int;

    /**
     * @return iterable<UserInterface>
     */
    public function getNpcList(): iterable;

    /**
     * @return iterable<UserInterface>
     */
    public function getNonNpcList(): iterable;

    /**
     * Returns the game's default fallback user item
     */
    public function getFallbackUser(): UserInterface;
}
