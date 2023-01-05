<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\TholianWeb;

use request;
use Stu\Component\Ship\ShipStateEnum;
use Stu\Exception\SanityCheckException;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\StuTime;
use Stu\Module\Logging\LoggerEnum;
use Stu\Module\Logging\LoggerUtilFactoryInterface;
use Stu\Module\Logging\LoggerUtilInterface;
use Stu\Module\Message\Lib\PrivateMessageFolderSpecialEnum;
use Stu\Module\Message\Lib\PrivateMessageSenderInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\Lib\ShipStateChangerInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;
use Stu\Module\Ship\Lib\TholianWebUtilInterface;
use Stu\Orm\Repository\ShipSystemRepositoryInterface;
use Stu\Orm\Repository\TholianWebRepositoryInterface;

final class SupportTholianWeb implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_SUPPORT_WEB';

    private ShipLoaderInterface $shipLoader;

    private TholianWebUtilInterface $tholianWebUtil;

    private TholianWebRepositoryInterface $tholianWebRepository;

    private ShipSystemRepositoryInterface $shipSystemRepository;

    private ShipStateChangerInterface $shipStateChanger;

    private StuTime $stuTime;

    private PrivateMessageSenderInterface $privateMessageSender;

    private LoggerUtilInterface $loggerUtil;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        TholianWebUtilInterface $tholianWebUtil,
        TholianWebRepositoryInterface $tholianWebRepository,
        ShipSystemRepositoryInterface $shipSystemRepository,
        ShipStateChangerInterface $shipStateChanger,
        StuTime $stuTime,
        PrivateMessageSenderInterface $privateMessageSender,
        LoggerUtilFactoryInterface $loggerUtilFactory
    ) {
        $this->shipLoader = $shipLoader;
        $this->tholianWebUtil = $tholianWebUtil;
        $this->tholianWebRepository = $tholianWebRepository;
        $this->shipSystemRepository = $shipSystemRepository;
        $this->shipStateChanger = $shipStateChanger;
        $this->stuTime = $stuTime;
        $this->privateMessageSender = $privateMessageSender;
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

        $currentSpinnerSystems = $this->shipSystemRepository->getWebConstructingShipSystems($web->getId());

        $emitter->setWebUnderConstructionId($web->getId())->update();
        $this->shipStateChanger->changeShipState($wrapper, ShipStateEnum::SHIP_STATE_WEB_SPINNING);

        $this->tholianWebUtil->updateWebFinishTime($web, 1);
        $finishTimeString = $this->stuTime->transformToStuDate($web->getFinishedTime());


        //notify other web spinners
        foreach ($currentSpinnerSystems as $shipSystem) {
            $this->privateMessageSender->send(
                $userId,
                $shipSystem->getShip()->getUser()->getId(),
                sprintf(
                    'Die %s unterstützt den Netzaufbau in Sektor %s, Fertigstellung: %s',
                    $ship->getName(),
                    $ship->getSectorString(),
                    $finishTimeString
                ),
                PrivateMessageFolderSpecialEnum::PM_SPECIAL_SHIP
            );
        }

        $game->addInformationf(
            "Der Aufbau des Energienetz wird unterstützt, Fertigstellung: %s",
            $finishTimeString
        );
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
