<?php

declare(strict_types=1);

namespace Stu\Module\Admin\Action\Ticks\Colony;

use Doctrine\ORM\EntityManagerInterface;
use Noodlehaus\ConfigInterface;
use Stu\Module\Admin\View\Ticks\ShowTicks;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Tick\Colony\ColonyTickInterface;
use Stu\Module\Tick\Colony\ColonyTickManagerInterface;
use Stu\Module\Tick\Lock\LockEnum;
use Stu\Orm\Repository\ColonyRepositoryInterface;
use Stu\Orm\Repository\CommodityRepositoryInterface;

final class ManualColonyTick implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_COLONY_TICK';

    public const DEFAULT_GROUP_COUNT = 1;

    private ManualColonyTickRequestInterface $request;

    private ColonyTickManagerInterface $colonyTickManager;

    private ColonyTickInterface $colonyTick;

    private ColonyRepositoryInterface $colonyRepository;

    private CommodityRepositoryInterface $commodityRepository;

    private ConfigInterface $config;

    private EntityManagerInterface $entityManager;

    public function __construct(
        ManualColonyTickRequestInterface $request,
        ColonyTickManagerInterface $colonyTickManager,
        ColonyTickInterface $colonyTick,
        ColonyRepositoryInterface $colonyRepository,
        CommodityRepositoryInterface $commodityRepository,
        ConfigInterface $config,
        EntityManagerInterface $entityManager
    ) {
        $this->request = $request;
        $this->colonyTickManager = $colonyTickManager;
        $this->colonyTick = $colonyTick;
        $this->colonyRepository = $colonyRepository;
        $this->commodityRepository = $commodityRepository;
        $this->config = $config;
        $this->entityManager = $entityManager;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowTicks::VIEW_IDENTIFIER);

        // only Admins can trigger ticks
        if (!$game->isAdmin()) {
            $game->addInformation(_('[b][color=FF2626]Aktion nicht möglich, Spieler ist kein Admin![/color][/b]'));
            return;
        }

        $colonyId = $this->request->getColonyId();

        //check if single or all colonies
        if ($colonyId === null) {
            $this->executeTickForMultipleColonies($game);
        } else {
            $this->executeTickForSingleColony($colonyId, $game);
        }
    }

    private function executeTickForMultipleColonies(GameControllerInterface $game): void
    {
        $groupId = $this->request->getGroupId();

        if ($groupId !== null) {
            $groupCount = $this->getGroupCount();
            $this->colonyTickManager->work($groupId, $groupCount);

            $game->addInformationf("Der Kolonie-Tick für die Kolonie-Gruppe %d/%d wurde durchgeführt!", $groupId, $groupCount);
        } else {
            $this->colonyTickManager->work(1, self::DEFAULT_GROUP_COUNT);
            $game->addInformation("Der Kolonie-Tick für alle Kolonien wurde durchgeführt!");
        }
    }

    private function getGroupCount(): int
    {
        return (int)$this->config->get(
            LockEnum::getLockGroupConfigPath(LockEnum::LOCK_TYPE_COLONY_GROUP),
            self::DEFAULT_GROUP_COUNT
        );
    }

    private function executeTickForSingleColony(int $colonyId, GameControllerInterface $game): void
    {
        $commodityArray = $this->commodityRepository->getAll();

        $colony = $this->colonyRepository->find($colonyId);

        if ($colony === null) {
            $game->addInformationf("Keine Kolonie mit der ID %d vorhanden!", $colonyId);
            return;
        }

        $this->colonyTick->work($colony, $commodityArray);
        $this->entityManager->flush();

        $game->addInformationf("Der Kolonie-Tick für die Kolonie mit der ID %d wurde durchgeführt!", $colonyId);
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
