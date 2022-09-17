<?php

namespace Stu\Module\Tick;

use Stu\Component\Game\TimeConstants;
use Stu\Module\Logging\LoggerEnum;
use Stu\Module\Logging\LoggerUtilFactoryInterface;
use Stu\Module\Logging\LoggerUtilInterface;
use Stu\Module\Tick\Colony\ColonyTickManager;
use Stu\Orm\Entity\GameTurnInterface;
use Stu\Orm\Repository\GameTurnRepositoryInterface;
use Stu\Orm\Repository\GameTurnStatsRepositoryInterface;
use Stu\Orm\Repository\KnPostRepositoryInterface;
use Stu\Orm\Repository\UserLockRepositoryInterface;
use Stu\Orm\Repository\UserRepositoryInterface;

final class TickManager implements TickManagerInterface
{
    public const PROCESS_COUNT = 1;

    private GameTurnRepositoryInterface $gameTurnRepository;

    private UserLockRepositoryInterface $userLockRepository;

    private GameTurnStatsRepositoryInterface $gameTurnStatsRepository;

    private UserRepositoryInterface $userRepository;

    private KnPostRepositoryInterface $knPostRepository;

    private LoggerUtilInterface $loggerUtil;

    public function __construct(
        GameTurnRepositoryInterface $gameTurnRepository,
        UserLockRepositoryInterface $userLockRepository,
        GameTurnStatsRepositoryInterface $gameTurnStatsRepository,
        UserRepositoryInterface $userRepository,
        KnPostRepositoryInterface $knPostRepository,
        LoggerUtilFactoryInterface $loggerUtilFactory
    ) {
        $this->gameTurnRepository = $gameTurnRepository;
        $this->userLockRepository = $userLockRepository;
        $this->gameTurnStatsRepository = $gameTurnStatsRepository;
        $this->userRepository = $userRepository;
        $this->knPostRepository = $knPostRepository;
        $this->loggerUtil = $loggerUtilFactory->getLoggerUtil();
    }

    public function work(): void
    {
        $turn = $this->gameTurnRepository->getCurrent();
        $this->endTurn($turn);
        $this->mainLoop();
        $this->reduceUserLocks();
        $newTurn = $this->startTurn($turn);
        $this->createGameTurnStats($newTurn);
    }

    private function endTurn(GameTurnInterface $turn): void
    {
        $turn->setEnd(time());

        $this->gameTurnRepository->save($turn);
    }

    private function startTurn(GameTurnInterface $turn): GameTurnInterface
    {
        $obj = $this->gameTurnRepository->prototype();
        $obj->setStart(time());
        $obj->setEnd(0);
        $obj->setTurn($turn->getTurn() + 1);

        $this->gameTurnRepository->save($obj);

        return $obj;
    }

    private function mainLoop(): void
    {
        while (true) {
            if ($this->hitLockFiles() === false) {
                break;
            }
            sleep(1);
        }
    }

    private function reduceUserLocks(): void
    {
        $locks = $this->userLockRepository->getActive();

        foreach ($locks as $lock) {
            $remainingTicks = $lock->getRemainingTicks();

            if ($remainingTicks === 1) {
                $userId = $lock->getUser()->getId();

                $lock->setUser(null);
                $lock->setUserId(null);
                $lock->setFormerUserId($userId);
                $lock->setRemainingTicks(0);
            } else {
                $lock->setRemainingTicks($remainingTicks - 1);
            }

            $this->userLockRepository->save($lock);
        }
    }

    private function createGameTurnStats(GameTurnInterface $turn): void
    {
        $stats = $this->gameTurnStatsRepository->prototype();

        $this->loggerUtil->log('setting stats values');

        $stats->setTurn($turn);
        $stats->setUserCount($this->userRepository->getActiveAmount());
        $stats->setLogins24h($this->userRepository->getActiveAmountRecentlyOnline(time() - TimeConstants::ONE_DAY_IN_SECONDS));
        $stats->setVacationCount($this->userRepository->getVacationAmount());
        $stats->setShipCount($this->gameTurnStatsRepository->getShipCount());
        $stats->setKnCount($this->knPostRepository->getAmount());
        $stats->setFlightSig24h($this->gameTurnStatsRepository->getFlightSigs24h());

        $this->gameTurnStatsRepository->save($stats);
        $this->loggerUtil->log('saved stats');
    }

    private function hitLockFiles(): bool
    {
        for ($i = 1; $i <= self::PROCESS_COUNT; $i++) {
            if (@file_exists(ColonyTickManager::LOCKFILE_DIR . 'col' . $i . '.lock')) {
                return true;
            }
        }
        return false;
    }
}
