<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Lib;

use Stu\Component\Ship\UpdateLocation\Handler\UpdateLocationHandlerInterface;
use Stu\Orm\Entity\ShipInterface;

final class UpdateLocationConsequences implements UpdateLocationConsequencesInterface
{
    private array $preMoveHandler;
    private array $postMoveHandler;

    /**
     *  @param UpdateLocationHandlerInterface[] $preMoveHandler
     *  @param UpdateLocationHandlerInterface[] $postMoveHandler
     */
    public function __construct(array $preMoveHandler, array $postMoveHandler)
    {
        $this->preMoveHandler = $preMoveHandler;
        $this->postMoveHandler = $postMoveHandler;
    }

    public function updateLocationWithConsequences(
        ShipInterface $ship,
        ?ShipInterface $tractoringShip,
        $nextField,
        array &$msgToPlayer = null
    ): void {
        // preMove handler
        $this->walkHandler($this->preMoveHandler, $ship, $tractoringShip, $msgToPlayer);

        // update location
        if ($ship->getSystem() === null) {
            $ship->updateLocation($nextField, null);
        } else {
            $ship->updateLocation(null, $nextField);
        }

        // postMove handler
        $this->walkHandler($this->postMoveHandler, $ship, $tractoringShip, $msgToPlayer);
    }

    private function walkHandler(array $handler, ShipInterface $ship, ?ShipInterface $tractoringShip, array &$msgToPlayer): void
    {
        array_walk(
            $handler,
            function (UpdateLocationHandlerInterface $handler) use ($ship, $tractoringShip, $msgToPlayer): void {
                $handler->clearMessages();
                $handler->handle($ship, $tractoringShip);
                $this->scheduleMsgToOwnerOrPlayer($ship, $tractoringShip, $handler->getInternalMsg(), $msgToPlayer);
            }
        );
    }

    private function scheduleMsgToOwnerOrPlayer(ShipInterface $ship, ?ShipInterface $tractoringShip, array $msgToSchedule, ?array &$msgToPlayer = null): void
    {
        $scheduleToOwnerOfPassiveShip = $tractoringShip !== null && $tractoringShip->getUser() !== $ship->getUser();

        if ($scheduleToOwnerOfPassiveShip) {
            $this->informOwnerOfTractoredShip($msgToSchedule);
        } else {
            if ($msgToPlayer !== null) {
                $msgToPlayer = array_merge($msgToPlayer, $msgToSchedule);
            }
        }
    }

    private function informOwnerOfTractoredShip(array $msg): void
    {
        //TODO privateMessageSender
    }

    // $ship->setState(ShipStateEnum::SHIP_STATE_NONE);

    //TODO cancel colony block / defend, wenn passiv
    //CancelColonyBlockOrDefendInterface
    //$game->addInformationMergeDown($this->cancelColonyBlockOrDefend->work($ship, true));

    //TODO wenn Traktor in Flotte, dann freilassen

    //TODO notruf
    //TODO notruf canceln wenn bewegen

    //TODO andockschleuse
    //TODO andockschleuse deaktivieren, wenn aktiv
    //  $ship->setDockedTo(null);
    //TODO andockschleuse schrotten, wenn passiv


}
