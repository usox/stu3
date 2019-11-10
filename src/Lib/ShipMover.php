<?php

use Stu\Component\Map\MapEnum;
use Stu\Component\Ship\ShipEnum;
use Stu\Component\Ship\ShipStateEnum;
use Stu\Component\Ship\System\Exception\ShipSystemException;
use Stu\Component\Ship\System\ShipSystemManagerInterface;
use Stu\Component\Ship\System\ShipSystemTypeEnum;
use Stu\Lib\DamageWrapper;
use Stu\Module\Communication\Lib\PrivateMessageFolderSpecialEnum;
use Stu\Module\Communication\Lib\PrivateMessageSenderInterface;
use Stu\Module\History\Lib\EntryCreatorInterface;
use Stu\Module\Ship\Lib\Battle\ApplyDamageInterface;
use Stu\Module\Ship\Lib\ShipRemoverInterface;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Repository\MapRepositoryInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\Orm\Repository\StarSystemMapRepositoryInterface;

class ShipMover {

    private $new_x = 0;
    private $new_y = 0;
    private $firstShip = NULL;
    private $fleetMode = 0;
    private $fieldData = NULL;
    private $fieldCount = NULL;
    private $flightFields = 0;

    function __construct(ShipInterface $firstShip) {
        $this->firstShip = $firstShip;
        $this->setDestination();
        $this->determineFleetMode();
        $this->preMove();
    }

    private function getFlightFields() {
        return $this->flightFields;
    }

    function setDestination() {
        $posx = request::getIntFatal('posx');
        $posy = request::getIntFatal('posy');
        if ($this->getFirstShip()->getPosX() != $posx && $this->getFirstShip()->getPosY() != $posy) {
            new InvalidParamException;
        }
        if ($posx < 1) {
            $posx = 1;
        }
        if ($posy < 1) {
            $posy = 1;
        }
        if ($this->getFirstShip()->getSystem() !== null) {
            $sys = $this->getFirstShip()->getSystem();
            if ($posx > $sys->getMaxX()) {
                $posx = $sys->getMaxX();
            }
            if ($posy > $sys->getMaxY()) {
                $posy = $sys->getMaxY();
            }
        } else {
            if ($posx > MapEnum::MAP_MAX_X) {
                $posx = MapEnum::MAP_MAX_X;
            }
            if ($posy > MapEnum::MAP_MAX_Y) {
                $posy = MapEnum::MAP_MAX_Y;
            }
        }
        $this->setDestX($posx);
        $this->setDestY($posy);
    }

    function determineFleetMode() {
        if (!$this->getFirstShip()->getFleetId()) {
            return;
        }
        // check ob das erste schiff auch das flaggschiff ist
        if (!$this->getFirstShip()->isFleetLeader()) {
            return;
        }
        $this->setFleetMode(1);
    }

    function setFleetMode($value) {
        $this->fleetMode = $value;
    }

    function getFirstShip(): ShipInterface {
        return $this->firstShip;
    }

    function isFleetMode() {
        return $this->fleetMode;
    }

    function getDestX() {
        return $this->new_x;
    }

    function getDestY() {
        return $this->new_y;
    }

    function calcFieldCount() {
        if ($this->getFirstShip()->getPosX() == $this->getDestX()) {
            $fields = abs($this->getFirstShip()->getPosY()-$this->getDestY());
        } else {
            $fields = abs($this->getFirstShip()->getPosX()-$this->getDestX());
        }
        if ($fields > $this->getFirstShip()->getEps()) {
            $fields = $this->getFirstShip()->getEps();
        }
        $this->setFieldCount($fields);
    }

    function setFieldCount($value) {
        $this->fieldCount = $value;
    }

    function getFieldCount() {
        if ($this->fieldCount === NULL) {
            $this->calcFieldCount();
        }
        return $this->fieldCount;
    }

    function setDestX($value) {
        $this->new_x = $value;
    }

    function setDestY($value) {
        $this->new_y = $value;
    }

    private $informations = array();

    function addInformation($value) {
        $this->informations[] = $value;
    }

    function addInformationMerge($value) {
        if (!is_array($value)) {
            return;
        }
        $this->informations = array_merge($this->getInformations(),$value);
    }

    function getInformations() {
        return $this->informations;
    }

    function isFirstShip(&$shipId) {
        return $shipId == $this->getFirstShip()->getId();
    }

    private function preMove() {
        $ships[] = &$this->getFirstShip();
        $msg = array();
        if ($this->isFleetMode()) {
            $ships = array_merge(
                $ships,
                array_filter(
                    $this->getFirstShip()->getFleet()->getShips()->toArray(),
                    function (ShipInterface $ship): bool {
                        return $ship->getId() !== $this->getFirstShip()->getId();
                    }
                )
            );
        }
        if ($this->isFleetMode()) {
            if ($this->getFirstShip()->getEps() == 0) {
                $this->addInformation(sprintf(_('Die %s hat nicht genug Energie für den Flug'),$this->getFirstShip()->getName()));
                return;
            }
            if ($this->getFirstShip()->getBuildplan()->getCrew() > 0 && $this->getFirstShip()->getCrewCount() == 0) {
                $this->addInformation(sprintf(_('Es werden %d Crewmitglieder benötigt'),$this->getFirstShip()->getBuildplan()->getCrew()));
                return;
            }
        }
        foreach($ships as $key => $obj) {
            $ret = $this->move($obj);
            if (is_array($ret)) {
                $msg = array_merge($msg,$ret);
            }
        }
        $this->addInformationMerge($msg);
        if ($this->isFleetMode() && $this->getFlightFields() > 0) {
            $this->addInformation(sprintf(_('Die Flotte fliegt in Sektor %d|%d ein'),$this->getDestX(),$this->getDestY()));
        }
    }

    private function move(ShipInterface $ship) {
        // @todo
        global $container;

        $shipRepo = $container->get(ShipRepositoryInterface::class);
        $entryCreator = $container->get(EntryCreatorInterface::class);
        $shipRemover = $container->get(ShipRemoverInterface::class);
        $privateMessageSender = $container->get(PrivateMessageSenderInterface::class);
        $shipSystemManager = $container->get(ShipSystemManagerInterface::class);
        $applyDamage = $container->get(ApplyDamageInterface::class);

        $msg = array();
        if (!$this->isFleetMode()) {
            if ($ship->getSystem() === null && !$ship->isWarpAble()) {
                $this->addInformation(_("Dieses Schiff verfügt über keinen Warpantrieb"));
                return FALSE;
            }
            if ($ship->getEps() < $ship->getRump()->getFlightEcost()) {
                $this->addInformation(sprintf(_('Die %s hat nicht genug Energie für den Flug (%d benötigt)'),$ship->getName(),$ship->getRump()->getFlightEcost()));
                return FALSE;
            }
            if ($ship->getBuildplan()->getCrew() > 0 && $ship->getCrewCount() == 0) {
                $this->addInformation(sprintf(_('Es werden %d Crewmitglieder benötigt'),$ship->getBuildplan()->getCrew()));
                return FALSE;
            }
        }
        $ship->setDockedTo(null);
        if ($ship->getState() == ShipStateEnum::SHIP_STATE_REPAIR) {
            $ship->cancelRepair();
            $this->addInformation(sprintf(_('Die Reparatur der %s wurde abgebrochen'),$ship->getId()));
        }
        if ($ship->getTraktorMode() == 2) {
            $this->addInformation("Die ".$ship->getName()." wird von einem Traktorstrahl gehalten");
            if ($this->isFleetMode()) {
                if ($this->isFirstShip($ship->getId())) {
                    $this->stopMove($ship->getPosX(),$ship->getPosY());
                } else {
                    $ship->leaveFleet();
                }
            } else {
                $this->stopMove($ship->getPosX(),$ship->getPosY());
            }
            return;
        }
        if (!$this->isFleetMode() && !$ship->getWarpState() && $ship->getSystem() === null) {
            try {
                $shipSystemManager->activate($ship, ShipSystemTypeEnum::SYSTEM_WARPDRIVE);
            } catch (ShipSystemException $e) {
                $this->addInformation(sprintf(_("Die %s kann den Warpantrieb nicht aktivieren"), $ship->getName()));
                return false;
            }
        }
        if ($this->getDestX() == $ship->getPosX() && $this->getDestY() == $ship->getPosY()) {
            return;
        }
        if ($this->getDestX() == $ship->getPosX()) {
            $oldy = $ship->getPosY();
            $cury = $oldy;
            if ($this->getDestY() > $oldy) {
                $method = ShipEnum::FLY_DOWN;
            } else {
                $method = ShipEnum::FLY_UP;
            }
        }
        if ($this->getDestY() == $ship->getPosY()) {
            $oldx = $ship->getPosX();
            $curx = $oldx;
            if ($this->getDestX() > $oldx) {
                $method = ShipEnum::FLY_RIGHT;
            } else {
                $method = ShipEnum::FLY_LEFT;
            }
        }

        $i = 1;
        while($i<=$this->getFieldCount()) {
            if ($ship->getSystem() === null && !$ship->getWarpState()) {
                try {
                    $shipSystemManager->activate($ship, ShipSystemTypeEnum::SYSTEM_WARPDRIVE);

                    $msg[] = "Die ".$ship->getName()." aktiviert den Warpantrieb";
                } catch (ShipSystemException $e) {
                    $ship->leaveFleet();

                    $msg[] = sprintf(
                        _('Die %s kann den Warpantrieb nicht aktivieren (%s|%s)'),
                        $ship->getName(),
                        $ship->getPosX(),
                        $ship->getPosY()
                    );
                    break;
                }
            }
            $nextfield = $this->getNextField($method,$ship);
            $flight_ecost = $ship->getRump()->getFlightEcost()+$nextfield->getFieldType()->getEnergyCosts();
            if ($ship->getEps() < $flight_ecost) {
                if ($this->isFleetMode()) {
                    if ($this->isFirstShip($ship->getId())) {
                        $this->stopMove($ship->getPosX(),$ship->getPosY());
                        $this->setFieldCount($i-1);
                        $msg[] = _("Das Flaggschiff hat nicht genügend Energie für den Weiterflug");
                        break;
                    } else {
                        $ship->leaveFleet();
                        $msg[] = "Die ".$ship->getName()." hat die Flotte aufgrund Energiemangels verlassen (".$ship->getPosX()."|".$ship->getPosY().")";
                        break;
                    }
                } else {
                    $this->stopMove($ship->getPosX(),$ship->getPosY());
                    break;
                }
            }
            $i++;
            if (!$nextfield->getFieldType()->getPassable()) {
                if (($this->isFleetMode() && $ship->isFleetLeader()) || !$this->isFleetMode())
                    $msg[] = _("Das nächste Feld kann nicht passiert werden");
                $this->stopMove($ship->getPosX(),$ship->getPosY());
                break;
            }
            if ($ship->isTraktorbeamActive() && $ship->getEps() < $ship->getTraktorShip()->getRump()->getFlightEcost()+1) {
                $msg[] = "Der Traktorstrahl auf die ".$ship->getTraktorShip()->getName()." wurde in Sektor ".$ship->getPosX()."|".$ship->getPosY()." aufgrund Energiemangels deaktiviert";
                $ship->deactivateTraktorBeam();
                $privateMessageSender->send(
                    (int) $ship->getUserId(),
                    (int) $ship->getTraktorShip()->getUserId(),
                    "Der auf die " . $ship->getTraktorShip()->getName() . " gerichtete Traktorstrahl wurde in SeKtor " . $ship->getSectorString() . " deaktiviert",
                    PrivateMessageFolderSpecialEnum::PM_SPECIAL_SHIP
                );
            }
            $this->flightDone = TRUE;
            $this->flightFields++;
            $met = 'fly'.$method;
            $this->$met($ship);
            if (!$this->isFleetMode() && $ship->getFleetId()) {
                $ship->leaveFleet();
                $msg[] = "Die ".$ship->getName()." hat die Flotte verlassen (".$ship->getPosX()."|".$ship->getPosY().")";
            }
            if ($ship->isTraktorbeamActive()) {
                if ($ship->getTraktorShip()->getFleetId()) {
                    $msg[] = sprintf(_('Flottenschiffe können nicht mitgezogen werden - Der auf die %s gerichtete Traktorstrahl wurde deaktiviert'),$ship->getTraktorShip()->getName());
                    $ship->deactivateTraktorBeam();
                } else {
                    $ship->setEps($ship->getEps() - $ship->getTraktorShip()->getRump()->getFlightEcost());
                    $this->$met($ship->getTraktorShip());
                }
            }
            $field = $this->getFieldData($ship->getPosX(),$ship->getPosY());
            if ($flight_ecost > $ship->getEps()) {
                $ship->setEps(0);
                if ($field->getFieldType()->getDamage()) {
                    if ($ship->isTraktorbeamActive()) {
                        $msg[] = "Die ".$ship->getTraktorShip()->getName()." wurde in Sektor ".$ship->getPosX()."|".$ship->getPosY()." beschädigt";
                        $damageMsg = $applyDamage->damage(
                            new DamageWrapper($field->getFieldType()->getDamage()),
                            $ship->getTraktorShip()
                        );
                        $msg = array_merge($msg,$damageMsg);
                    }
                    $msg[] = "Die ".$ship->getName()." wurde in Sektor ".$ship->getPosX()."|".$ship->getPosY()." beschädigt";
                    $damageMsg = $applyDamage->damage(
                        new DamageWrapper($field->getFieldType()->getDamage()),
                        $ship
                    );
                    $msg = array_merge($msg,$damageMsg);

                    if ($ship->getTraktorShip()->getIsDestroyed()) {
                        $entryCreator->addShipEntry(
                            'Die ' . $ship->getTraktorShip()->getName() . ' wurde beim Einflug in Sektor ' . $ship->getTraktorShip()->getSectorString() . ' zerstört'
                        );

                        $shipRemover->destroy($ship->getTraktorShip());
                    }
                }
            } else {
                $ship->setEps($ship->getEps() - $flight_ecost);
            }
            if ($field->getFieldType()->getSpecialDamage() && (($ship->getSystem() !== null && $field->getFieldType()->getSpecialDamageInnerSystem()) || ($ship->getSystem() === null && !$ship->getWarpState() && !$field->getFieldType()->getSpecialDamageInnerSystem()))) {
                if ($ship->isTraktorbeamActive()) {
                    $msg[] = "Die ".$ship->getTraktorShip()->getName()." wurde in Sektor ".$ship->getPosX()."|".$ship->getPosY()." beschädigt";
                    $damageMsg = $applyDamage->damage(
                        new DamageWrapper($field->getFieldType()->getDamage()),
                        $ship->getTraktorShip()
                    );
                    $msg = array_merge($msg,$damageMsg);
                }
                $msg[] = $field->getFieldType()->getName()." in Sektor ".$ship->getPosX()."|".$ship->getPosY();
                $damageMsg = $applyDamage->damage(
                    new DamageWrapper($field->getFieldType()->getSpecialDamage()),
                    $ship
                );
                $msg = array_merge($msg,$damageMsg);

                if ($ship->getIsDestroyed()) {
                    $entryCreator->addShipEntry(
                        'Die ' . $ship->getName() . ' wurde beim Einflug in Sektor ' . $ship->getSectorString() . ' zerstört'
                    );

                    $shipRemover->destroy($ship);
                }
            }
        }

        if ($this->flightDone) {
            if (!$this->isFleetMode()) {
                $this->addInformation("Die ".$ship->getName()." fliegt in Sektor ".$ship->getPosX()."|".$ship->getPosY()." ein");
            }
            if ($ship->isTraktorbeamActive()) {
                $this->addInformation("Die ".$ship->getTraktorShip()->getName()." wurde per Traktorstrahl mitgezogen");
                $shipRepo->save($ship->getTraktorShip());
            }
        }
        $shipRepo->save($ship);
        return $msg;
    }

    private $flightDone = FALSE;

    private function getNextField(&$method, ShipInterface $ship) {
        switch ($method) {
            case ShipEnum::FLY_RIGHT:
                return $this->getFieldData($ship->getPosX()+1,$ship->getPosY());
            case ShipEnum::FLY_LEFT:
                return $this->getFieldData($ship->getPosX()-1,$ship->getPosY());
            case ShipEnum::FLY_UP:
                return $this->getFieldData($ship->getPosX(),$ship->getPosY()-1);
            case ShipEnum::FLY_DOWN:
                return $this->getFieldData($ship->getPosX(),$ship->getPosY()+1);
        }
    }

    function stopMove(&$posx,&$posy) {
        $this->setDestX($posx);
        $this->setDestY($posy);
    }

    function fly4(ShipInterface $ship) {
        $ship->setPosY($ship->getPosY()+1);
        $ship->setFlightDirection(1);
    }

    function fly3(ShipInterface $ship) {
        $ship->setPosY($ship->getPosY()-1);
        $ship->setFlightDirection(2);
    }

    function fly1(ShipInterface $ship) {
        $ship->setPosX($ship->getPosX()+1);
        $ship->setFlightDirection(3);
    }

    function fly2(ShipInterface $ship) {
        $ship->setPosX($ship->getPosX()-1);
        $ship->setFlightDirection(4);
    }

    function getFieldData($x,$y) {
        if ($this->fieldData === NULL) {
            // @todo refactor
            global $container;
            $ship = $this->getFirstShip();
            $sx = (int) $ship->getPosX();
            $sy = (int) $ship->getPosY();
            $destx = (int) $this->getDestX();
            $desty = (int) $this->getDestY();

            if ($sy > $desty) {
                $oy = $sy;
                $sy = $desty;
                $desty = $oy;
            }
            if ($sx > $destx) {
                $ox = $sx;
                $sx = $destx;
                $destx = $ox;
            }
            if ($this->getFirstShip()->getSystem() === null) {
                $result = $container->get(MapRepositoryInterface::class)->getByCoordinateRange(
                    $sx,
                    $destx,
                    $sy,
                    $desty
                );

                foreach ($result as $field) {
                    $this->fieldData[sprintf('%d_%d', $field->getCx(), $field->getCy())] = $field;
                }
            } else {
                $result = $container->get(StarSystemMapRepositoryInterface::class)->getByCoordinateRange(
                    $ship->getSystem(),
                    $sx,
                    $destx,
                    $sy,
                    $desty
                );

                foreach ($result as $field) {
                    $this->fieldData[sprintf('%d_%d', $field->getSx(), $field->getSy())] = $field;
                }
            }

        }
        return $this->fieldData[$x."_".$y];
    }
}
