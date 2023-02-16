<?php

declare(strict_types=1);

namespace Stu\Component\Crew;

use Stu\Component\Ship\ShipRumpEnum;
use Stu\Orm\Entity\UserInterface;
use Stu\Orm\Repository\CrewRepositoryInterface;
use Stu\Orm\Repository\CrewTrainingRepositoryInterface;
use Stu\Orm\Repository\ShipCrewRepositoryInterface;

/**
 * Provides methods to retrieve the crew counts
 */
final class CrewCountRetriever implements CrewCountRetrieverInterface
{
    private CrewRepositoryInterface $crewRepository;

    private ShipCrewRepositoryInterface $shipCrewRepository;

    private CrewTrainingRepositoryInterface $crewTrainingRepository;

    public function __construct(
        CrewRepositoryInterface $crewRepository,
        ShipCrewRepositoryInterface $shipCrewRepository,
        CrewTrainingRepositoryInterface $crewTrainingRepository
    ) {
        $this->crewRepository = $crewRepository;
        $this->shipCrewRepository = $shipCrewRepository;
        $this->crewTrainingRepository = $crewTrainingRepository;
    }

    public function getDebrisAndTradePostsCount(UserInterface $user): int
    {
        $count = $this->crewRepository
            ->getAmountByUserAndShipRumpCategory(
                $user,
                ShipRumpEnum::SHIP_CATEGORY_ESCAPE_PODS
            );

        return $count + $this->shipCrewRepository->getAmountByUserAtTradeposts($user);
    }

    public function getAssignedToShipsCount(UserInterface $user): int
    {
        return $this->shipCrewRepository->getAmountByUserOnShips($user);
    }

    public function getInTrainingCount(UserInterface $user): int
    {
        return $this->crewTrainingRepository->getCountByUser($user);
    }

    public function getRemainingCount(UserInterface $user): int
    {
        return max(
            0,
            $user->getGlobalCrewLimit() - $this->getAssignedCount($user) - $this->getInTrainingCount($user)
        );
    }

    public function getAssignedCount(UserInterface $user): int
    {
        return $this->shipCrewRepository->getAmountByUser($user);
    }

    public function getTrainableCount(UserInterface $user): int
    {
        return (int) ceil($user->getGlobalCrewLimit() / 10);
    }
}
