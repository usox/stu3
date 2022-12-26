<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\ActivateTractorBeam;

use request;
use Stu\Component\Ship\ShipAlertStateEnum;
use Stu\Component\Ship\System\ShipSystemManagerInterface;
use Stu\Component\Ship\System\ShipSystemTypeEnum;
use Stu\Exception\SanityCheckException;
use Stu\Module\Ship\Lib\InteractionCheckerInterface;
use Stu\Module\Message\Lib\PrivateMessageFolderSpecialEnum;
use Stu\Module\Message\Lib\PrivateMessageSenderInterface;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Ship\Lib\ShipAttackCycleInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\Module\Ship\Lib\ActivatorDeactivatorHelperInterface;
use Stu\Module\Ship\Lib\ShipWrapperFactoryInterface;

final class ActivateTractorBeam implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_ACTIVATE_TRAKTOR';

    private ShipLoaderInterface $shipLoader;

    private PrivateMessageSenderInterface $privateMessageSender;

    private ShipRepositoryInterface $shipRepository;

    private ShipAttackCycleInterface $shipAttackCycle;

    private InteractionCheckerInterface $interactionChecker;

    private ActivatorDeactivatorHelperInterface $helper;

    private ShipSystemManagerInterface $shipSystemManager;

    private ShipWrapperFactoryInterface $shipWrapperFactory;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        PrivateMessageSenderInterface $privateMessageSender,
        ShipRepositoryInterface $shipRepository,
        ShipAttackCycleInterface $shipAttackCycle,
        InteractionCheckerInterface $interactionChecker,
        ActivatorDeactivatorHelperInterface $helper,
        ShipSystemManagerInterface $shipSystemManager,
        ShipWrapperFactoryInterface $shipWrapperFactory
    ) {
        $this->shipLoader = $shipLoader;
        $this->privateMessageSender = $privateMessageSender;
        $this->shipRepository = $shipRepository;
        $this->shipAttackCycle = $shipAttackCycle;
        $this->interactionChecker = $interactionChecker;
        $this->helper = $helper;
        $this->shipSystemManager = $shipSystemManager;
        $this->shipWrapperFactory = $shipWrapperFactory;
    }

    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();

        $shipId = request::indInt('id');
        $targetId = request::getIntFatal('target');

        $shipArray = $this->shipLoader->getWrappersByIdAndUserAndTarget(
            $shipId,
            $userId,
            $targetId
        );

        $wrapper = $shipArray[$shipId];
        $ship = $wrapper->get();

        $targetWrapper = $shipArray[$targetId];
        if ($targetWrapper === null) {
            return;
        }
        $target = $targetWrapper->get();

        $shipName = $ship->getName();

        if (!$this->interactionChecker->checkPosition($ship, $target)) {
            throw new SanityCheckException('InteractionChecker->checkPosition failed');
        }
        if ($target->getUser()->isVacationRequestOldEnough()) {
            $game->addInformation(_('Aktion nicht möglich, der Spieler befindet sich im Urlaubsmodus!'));
            $game->setView(ShowShip::VIEW_IDENTIFIER);
            return;
        }

        $targetName = $target->getName();

        // activate system
        if (!$this->helper->activate(request::indInt('id'), ShipSystemTypeEnum::SYSTEM_TRACTOR_BEAM, $game)) {
            $game->setView(ShowShip::VIEW_IDENTIFIER);
            return;
        }

        if ($target->getRump()->isTrumfield()) {
            $game->addInformation("Das Trümmerfeld kann nicht erfasst werden");
            $this->abort($ship, $game);
            return;
        }
        if ($target->isBase()) {
            $game->addInformation("Die " . $targetName . " kann nicht erfasst werden");
            $this->abort($ship, $game);
            return;
        }
        if ($target->isTractored()) {
            $game->addInformation("Das Schiff wird bereits vom Traktorstrahl der " . $target->getTractoringShip()->getName() . " gehalten");
            $this->abort($ship, $game);
            return;
        }
        if ($target->getFleetId() && $target->getFleetId() == $ship->getFleetId()) {
            $game->addInformation("Die " . $targetName . " befindet sich in der selben Flotte wie die " . $shipName);
            $this->abort($ship, $game);
            return;
        }
        if (($target->getAlertState() == ShipAlertStateEnum::ALERT_YELLOW || $target->getAlertState() == ShipAlertStateEnum::ALERT_RED)
            && $target->getUser()->getId() !== $userId
            && !$target->getUser()->isFriend($userId)
        ) {
            $defender = [$ship->getId() => $ship];

            if ($target->getFleetId()) {
                $attacker = $target->getFleet()->getShips()->toArray();
            } else {
                $attacker = [$target->getId() => $target];
            }

            $this->shipAttackCycle->init(
                $this->shipWrapperFactory->wrapShips($attacker),
                $this->shipWrapperFactory->wrapShips($defender),
                true
            );
            $this->shipAttackCycle->cycle();

            $game->addInformationMergeDown($this->shipAttackCycle->getMessages());

            $this->privateMessageSender->send(
                $userId,
                $target->getUser()->getId(),
                sprintf(
                    "Die %s versucht die %s in Sektor %s mit dem Traktorstrahl zu erfassen. Folgende Aktionen wurden ausgeführt:\n%s",
                    $shipName,
                    $targetName,
                    $ship->getSectorString(),
                    implode(PHP_EOL, $this->shipAttackCycle->getMessages())
                ),
                PrivateMessageFolderSpecialEnum::PM_SPECIAL_SHIP
            );
        }
        if ($ship->getIsDestroyed()) {
            return;
        }
        $game->setView(ShowShip::VIEW_IDENTIFIER);

        //is tractor beam system still healthy?
        if (!$ship->isSystemHealthy(ShipSystemTypeEnum::SYSTEM_TRACTOR_BEAM)) {
            $game->addInformation("Der Traktorstrahl wurde bei dem Angriff zerstört");
            return;
        }
        if ($target->getIsDestroyed()) {
            $game->addInformation("Das Ziel wurde bei dem Angriff zerstört");
            $this->abort($ship, $game);
            return;
        }

        //is nbs system still healthy?
        if (!$ship->isSystemHealthy(ShipSystemTypeEnum::SYSTEM_NBS)) {
            $game->addInformation("Abbruch, die Nahbereichssensoren wurden bei dem Angriff zerstört");
            $this->abort($ship, $game);
            return;
        }


        if ($target->getShieldState()) {
            $game->addInformation("Die " . $targetName . " kann aufgrund der aktiven Schilde nicht erfasst werden");
            $this->abort($ship, $game);
            return;
        }
        $this->shipSystemManager->deactivate($targetWrapper, ShipSystemTypeEnum::SYSTEM_TRACTOR_BEAM, true); //forced active deactivation
        $target->setDockedTo(null);
        $ship->setTractoredShip($target);
        $this->shipRepository->save($ship);
        $this->shipRepository->save($target);

        $this->privateMessageSender->send(
            $userId,
            $target->getUser()->getId(),
            "Die " . $targetName . " wurde in Sektor " . $ship->getSectorString() . " vom Traktorstrahl der " . $shipName . " erfasst",
            PrivateMessageFolderSpecialEnum::PM_SPECIAL_SHIP,
            sprintf(_('ship.php?SHOW_SHIP=1&id=%d'), $target->getId())
        );
        $game->addInformation("Der Traktorstrahl wurde auf die " . $targetName . " gerichtet");
    }

    private function abort($ship, $game): void
    {
        // deactivate system
        $this->helper->deactivate(request::indInt('id'), ShipSystemTypeEnum::SYSTEM_TRACTOR_BEAM, $game);
        $this->shipRepository->save($ship);

        $game->setView(ShowShip::VIEW_IDENTIFIER);
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
