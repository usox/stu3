<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\TholianWeb;

use request;
use Stu\Component\Ship\ShipStateEnum;
use Stu\Exception\SanityCheckException;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Logging\LoggerEnum;
use Stu\Module\Logging\LoggerUtilFactoryInterface;
use Stu\Module\Logging\LoggerUtilInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\Lib\ShipStateChangerInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;
use Stu\Module\Ship\Lib\TholianWebUtilInterface;
use Stu\Orm\Repository\TholianWebRepositoryInterface;

final class SupportTholianWeb implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_SUPPORT_WEB';

    private ShipLoaderInterface $shipLoader;

    private TholianWebUtilInterface $tholianWebUtil;

    private TholianWebRepositoryInterface $tholianWebRepository;

    private ShipStateChangerInterface $shipStateChanger;

    private LoggerUtilInterface $loggerUtil;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        TholianWebUtilInterface $tholianWebUtil,
        TholianWebRepositoryInterface $tholianWebRepository,
        ShipStateChangerInterface $shipStateChanger,
        LoggerUtilFactoryInterface $loggerUtilFactory
    ) {
        $this->shipLoader = $shipLoader;
        $this->tholianWebUtil = $tholianWebUtil;
        $this->tholianWebRepository = $tholianWebRepository;
        $this->shipStateChanger = $shipStateChanger;
        $this->loggerUtil = $loggerUtilFactory->getLoggerUtil();
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowShip::VIEW_IDENTIFIER);

        $userId = $game->getUser()->getId();
        $shipId = request::indInt('id');

        if ($userId === 126) {
            //$this->loggerUtil->init('WEB', LoggerEnum::LEVEL_WARNING);
        }

        $wrapper = $this->shipLoader->getWrapperByIdAndUser(
            $shipId,
            $userId
        );

        $emitter = $wrapper->getWebEmitterSystemData();

        $this->loggerUtil->log('1');
        if ($emitter === null || $emitter->webUnderConstructionId !== null) {
            $this->loggerUtil->log('2');
            throw new SanityCheckException('emitter = null or already constructing');
        }
        $this->loggerUtil->log('3');

        $ship = $wrapper->get();
        //check if system healthy
        if (!$ship->isWebEmitterHealthy()) {
            throw new SanityCheckException('emitter not healthy');
        }

        $web = $this->tholianWebRepository->getWebAtLocation($ship);
        if ($web === null || $web->isFinished()) {
            throw new SanityCheckException('no web at location or already finished');
        }

        $emitter->setWebUnderConstructionId($web->getId())->update();
        $this->shipStateChanger->changeShipState($wrapper, ShipStateEnum::SHIP_STATE_WEB_SPINNING);

        $this->tholianWebUtil->updateWebFinishTime($web);

        $game->addInformationf(
            "Der Aufbau des Energienetz wird unterstützt, Fertigstellung: %s",
            $this->stuTime->transformToStuDate($web->getFinishedTime())
        );
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
