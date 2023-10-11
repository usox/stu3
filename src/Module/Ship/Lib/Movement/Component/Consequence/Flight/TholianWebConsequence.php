<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Lib\Movement\Component\Consequence\Flight;

use Stu\Component\Ship\ShipStateEnum;
use Stu\Module\Ship\Lib\Message\Message;
use Stu\Module\Ship\Lib\Message\MessageCollectionInterface;
use Stu\Module\Ship\Lib\Movement\Component\Consequence\AbstractFlightConsequence;
use Stu\Module\Ship\Lib\Movement\Route\FlightRouteInterface;
use Stu\Module\Ship\Lib\ShipWrapperInterface;
use Stu\Module\Ship\Lib\Interaction\TholianWebUtilInterface;

class TholianWebConsequence extends AbstractFlightConsequence
{
    private TholianWebUtilInterface $tholianWebUtil;

    public function __construct(TholianWebUtilInterface $tholianWebUtil)
    {
        $this->tholianWebUtil = $tholianWebUtil;
    }

    protected function triggerSpecific(
        ShipWrapperInterface $wrapper,
        FlightRouteInterface $flightRoute,
        MessageCollectionInterface $messages
    ): void {

        $ship = $wrapper->get();

        $message = new Message(null, $ship->getUser()->getId());
        $messages->add($message);

        //web spinning
        if ($ship->getState() === ShipStateEnum::SHIP_STATE_WEB_SPINNING) {
            $this->tholianWebUtil->releaseWebHelper($wrapper);

            $message->add(sprintf('Die %s hat die Unterstützung des Energienetzes abgebrochen', $ship->getName()));
        }

        // release from unfinished web
        $holdingWeb = $ship->getHoldingWeb();
        if ($holdingWeb !== null && !$holdingWeb->isFinished()) {
            $this->tholianWebUtil->releaseShipFromWeb($wrapper);

            $message->add(sprintf('Die %s ist einem unfertigen Energienetz entkommen', $ship->getName()));
        }
    }
}
