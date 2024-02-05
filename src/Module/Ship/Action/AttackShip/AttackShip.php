<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\AttackShip;

use request;
use Stu\Component\Ship\Nbs\NbsUtilityInterface;
use Stu\Exception\SanityCheckException;
use Stu\Lib\Information\InformationWrapper;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Ship\Lib\Battle\FightLibInterface;
use Stu\Module\Ship\Lib\Battle\ShipAttackCoreInterface;
use Stu\Module\Ship\Lib\Interaction\InteractionCheckerInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;
use Stu\Orm\Entity\ShipInterface;

//TODO unit tests and request class
final class AttackShip implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_ATTACK_SHIP';

    private ShipLoaderInterface $shipLoader;

    private InteractionCheckerInterface $interactionChecker;

    private NbsUtilityInterface $nbsUtility;

    private FightLibInterface $fightLib;

    private ShipAttackCoreInterface $shipAttackCore;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        InteractionCheckerInterface $interactionChecker,
        NbsUtilityInterface $nbsUtility,
        FightLibInterface $fightLib,
        ShipAttackCoreInterface $shipAttackCore
    ) {
        $this->shipLoader = $shipLoader;
        $this->interactionChecker = $interactionChecker;
        $this->nbsUtility = $nbsUtility;
        $this->fightLib = $fightLib;
        $this->shipAttackCore = $shipAttackCore;
    }

    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();

        $shipId = request::indInt('id');
        $targetId = request::getIntFatal('target');

        $wrappers = $this->shipLoader->getWrappersBySourceAndUserAndTarget(
            $shipId,
            $userId,
            $targetId
        );

        $wrapper = $wrappers->getSource();
        $ship = $wrapper->get();

        $targetWrapper = $wrappers->getTarget();
        if ($targetWrapper === null) {
            return;
        }
        $target = $targetWrapper->get();

        if ($target->getUser()->isVacationRequestOldEnough()) {
            $game->addInformation(_('Aktion nicht möglich, der Spieler befindet sich im Urlaubsmodus!'));
            return;
        }

        if (!$ship->hasEnoughCrew($game)) {
            return;
        }
        if (!$this->interactionChecker->checkPosition($target, $ship)) {
            throw new SanityCheckException('InteractionChecker->checkPosition failed', self::ACTION_IDENTIFIER);
        }

        if ($this->isTargetDestroyed($target)) {
            $game->setView(ShowShip::VIEW_IDENTIFIER);
            $game->addInformation(_('Das Ziel ist bereits zerstört'));
            return;
        }

        if (!$this->fightLib->canAttackTarget($ship, $target)) {
            throw new SanityCheckException('Target cant be attacked', self::ACTION_IDENTIFIER);
        }

        if ($target->getCloakState() && !$this->nbsUtility->isTachyonActive($ship)) {
            throw new SanityCheckException('Attacked cloaked ship without active tachyon', self::ACTION_IDENTIFIER);
        }

        $epsSystemData = $wrapper->getEpsSystemData();
        if ($epsSystemData === null || $epsSystemData->getEps() === 0) {
            $game->addInformation(_('Keine Energie vorhanden'));
            return;
        }

        if ($ship->isDisabled()) {
            $game->addInformation(_('Das Schiff ist kampfunfähig'));
            return;
        }
        if ($ship->getDockedTo() !== null) {
            $ship->setDockedTo(null);
        }

        $isFleetFight = false;
        $informations = new InformationWrapper();

        $this->shipAttackCore->foo($wrapper, $targetWrapper, $isFleetFight, $informations);

        if ($ship->isDestroyed()) {
            $game->addInformationWrapper($informations);
            return;
        }
        $game->setView(ShowShip::VIEW_IDENTIFIER);

        if ($isFleetFight) {
            $game->addInformation(_("Angriff durchgeführt"));
            $game->setTemplateVar('FIGHT_RESULTS', $informations->getInformations());
        } else {
            $game->addInformationWrapper($informations);
        }
    }

    private function isTargetDestroyed(ShipInterface $ship): bool
    {
        return $ship->isDestroyed();
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
