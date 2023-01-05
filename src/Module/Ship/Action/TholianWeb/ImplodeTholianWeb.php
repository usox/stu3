<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\TholianWeb;

use request;
use Stu\Exception\SanityCheckException;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Logging\LoggerEnum;
use Stu\Module\Logging\LoggerUtilFactoryInterface;
use Stu\Module\Logging\LoggerUtilInterface;
use Stu\Module\Message\Lib\PrivateMessageFolderSpecialEnum;
use Stu\Module\Message\Lib\PrivateMessageSenderInterface;
use Stu\Module\Ship\Lib\Battle\TholianWebWeaponPhaseInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;
use Stu\Module\Ship\Lib\TholianWebUtilInterface;

final class ImplodeTholianWeb implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_IMPLODE_WEB';

    private ShipLoaderInterface $shipLoader;

    private TholianWebUtilInterface $tholianWebUtil;

    private PrivateMessageSenderInterface $privateMessageSender;

    private TholianWebWeaponPhaseInterface $tholianWebWeaponPhase;

    private LoggerUtilInterface $loggerUtil;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        TholianWebUtilInterface $tholianWebUtil,
        PrivateMessageSenderInterface $privateMessageSender,
        TholianWebWeaponPhaseInterface $tholianWebWeaponPhase,
        LoggerUtilFactoryInterface $loggerUtilFactory
    ) {
        $this->shipLoader = $shipLoader;
        $this->tholianWebUtil = $tholianWebUtil;
        $this->privateMessageSender = $privateMessageSender;
        $this->tholianWebWeaponPhase = $tholianWebWeaponPhase;
        $this->loggerUtil = $loggerUtilFactory->getLoggerUtil();
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowShip::VIEW_IDENTIFIER);

        $userId = $game->getUser()->getId();
        $shipId = request::indInt('id');

        if ($userId === 126) {
            // $this->loggerUtil->init('WEB', LoggerEnum::LEVEL_WARNING);
        }

        $wrapper = $this->shipLoader->getWrapperByIdAndUser(
            $shipId,
            $userId
        );

        $emitter = $wrapper->getWebEmitterSystemData();

        $this->loggerUtil->log('1');
        if ($emitter === null || $emitter->ownedWebId === null) {
            $this->loggerUtil->log('2');
            throw new SanityCheckException('emitter = null or no owned web');
        }
        $this->loggerUtil->log('3');

        $ship = $wrapper->get();
        //check if system healthy
        if (!$ship->isWebEmitterHealthy()) {
            throw new SanityCheckException('emitter not healthy');
        }

        $this->loggerUtil->log('5');

        $web = $emitter->getOwnedTholianWeb();

        $this->loggerUtil->log(sprintf('capturedSize: %d', count($web->getCapturedShips())));
        $this->loggerUtil->log('6');

        //damage captured ships
        foreach ($web->getCapturedShips() as $ship) {
            $this->tholianWebUtil->releaseShipFromWeb($wrapper->getShipWrapperFactory()->wrapShip($ship));

            //don't damage trumfields
            if ($ship->isDestroyed()) {
                continue;
            }

            $msg = $this->tholianWebWeaponPhase->damageCapturedShip($wrapper->getShipWrapperFactory()->wrapShip($ship), $game);

            $pm = sprintf(_('Das Energienetz um die %s in Sektor %s ist implodiert') . "\n", $ship->getName(), $ship->getSectorString());
            foreach ($msg as $value) {
                $pm .= $value . "\n";
            }

            //notify target owner
            $this->privateMessageSender->send(
                $userId,
                $ship->getUser()->getId(),
                $pm,
                $ship->isBase() ? PrivateMessageFolderSpecialEnum::PM_SPECIAL_STATION : PrivateMessageFolderSpecialEnum::PM_SPECIAL_SHIP
            );
        }

        $game->addInformation("Das Energienetz ist implodiert");
        $game->addInformationMergeDown($msg);

        $this->loggerUtil->log('10');

        $emitter->setOwnedWebId(null)->update();
    }



    public function performSessionCheck(): bool
    {
        return true;
    }
}
