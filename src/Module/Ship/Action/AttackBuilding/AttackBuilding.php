<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\AttackBuilding;

use request;
use Stu\Component\Building\BuildingEnum;
use Stu\Component\Colony\ColonyFunctionManager;
use Stu\Component\Colony\ColonyFunctionManagerInterface;
use Stu\Lib\Information\InformationWrapper;
use Stu\Module\Colony\Lib\PlanetFieldTypeRetrieverInterface;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Message\Lib\PrivateMessageFolderSpecialEnum;
use Stu\Module\Message\Lib\PrivateMessageSenderInterface;
use Stu\Module\Ship\Lib\Battle\AlertRedHelperInterface;
use Stu\Module\Ship\Lib\Battle\FightLibInterface;
use Stu\Module\Ship\Lib\Message\MessageInterface;
use Stu\Module\Ship\Lib\Battle\Provider\AttackerProviderFactoryInterface;
use Stu\Module\Ship\Lib\Battle\Weapon\EnergyWeaponPhaseInterface;
use Stu\Module\Ship\Lib\Battle\Weapon\ProjectileWeaponPhaseInterface;
use Stu\Module\Ship\Lib\Interaction\InteractionCheckerInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;
use Stu\Orm\Repository\ColonyRepositoryInterface;
use Stu\Orm\Repository\PlanetFieldRepositoryInterface;

final class AttackBuilding implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_ATTACK_BUILDING';

    private ShipLoaderInterface $shipLoader;

    private PlanetFieldRepositoryInterface $planetFieldRepository;

    private ColonyRepositoryInterface $colonyRepository;

    private InteractionCheckerInterface $interactionChecker;

    private FightLibInterface $fightLib;

    private EnergyWeaponPhaseInterface $energyWeaponPhase;

    private ProjectileWeaponPhaseInterface $projectileWeaponPhase;

    private PrivateMessageSenderInterface $privateMessageSender;

    private AlertRedHelperInterface $alertRedHelper;

    private InformationWrapper $informations;

    private PlanetFieldTypeRetrieverInterface $planetFieldTypeRetriever;

    private ColonyFunctionManagerInterface $colonyFunctionManager;

    private AttackerProviderFactoryInterface $attackerProviderFactory;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        PlanetFieldRepositoryInterface $planetFieldRepository,
        ColonyRepositoryInterface $colonyRepository,
        InteractionCheckerInterface $interactionChecker,
        FightLibInterface $fightLib,
        EnergyWeaponPhaseInterface $energyWeaponPhase,
        ProjectileWeaponPhaseInterface $projectileWeaponPhase,
        PrivateMessageSenderInterface $privateMessageSender,
        AlertRedHelperInterface $alertRedHelper,
        PlanetFieldTypeRetrieverInterface $planetFieldTypeRetriever,
        ColonyFunctionManagerInterface $colonyFunctionManager,
        AttackerProviderFactoryInterface $attackerProviderFactory
    ) {
        $this->shipLoader = $shipLoader;
        $this->planetFieldRepository = $planetFieldRepository;
        $this->colonyRepository = $colonyRepository;
        $this->interactionChecker = $interactionChecker;
        $this->fightLib = $fightLib;
        $this->energyWeaponPhase = $energyWeaponPhase;
        $this->projectileWeaponPhase = $projectileWeaponPhase;
        $this->privateMessageSender = $privateMessageSender;
        $this->alertRedHelper = $alertRedHelper;
        $this->planetFieldTypeRetriever = $planetFieldTypeRetriever;
        $this->colonyFunctionManager = $colonyFunctionManager;
        $this->attackerProviderFactory = $attackerProviderFactory;

        $this->informations = new InformationWrapper();
    }

    public function handle(GameControllerInterface $game): void
    {
        $user = $game->getUser();
        $userId = $user->getId();

        $wrapper = $this->shipLoader->getWrapperByIdAndUser(
            request::indInt('id'),
            $userId
        );

        $colonyId = request::getIntFatal('colid');
        $fieldId = request::getIntFatal('field');


        $field = $this->planetFieldRepository->find($fieldId);
        $colony = $this->colonyRepository->find($colonyId);
        if ($field === null || $colony === null) {
            $game->addInformation(_('Feld oder Kolonie nicht vorhanden'));
            return;
        }

        if ($field->getFieldId() >= 80) {
            $game->addInformation(_('Der Untergrund kann nicht attackiert werden'));
            return;
        }

        $ship = $wrapper->get();

        if (!$ship->hasEnoughCrew($game)) {
            return;
        }

        if ($colony->getUser()->isVacationRequestOldEnough()) {
            $game->addInformation(_('Aktion nicht möglich, der Spieler befindet sich im Urlaubsmodus!'));
            return;
        }

        $epsSystem = $wrapper->getEpsSystemData();

        if ($epsSystem === null || $epsSystem->getEps() == 0) {
            $game->addInformation(_('Keine Energie vorhanden'));
            return;
        }
        if ($ship->isDisabled()) {
            $game->addInformation(_('Das Schiff ist kampfunfähig'));
            return;
        }

        if ($colony !== $field->getHost()) {
            return;
        }
        if (!$this->interactionChecker->checkColonyPosition($colony, $ship)) {
            return;
        }

        $isFleetAttack = false;
        $fleetWrapper = $wrapper->getFleetWrapper();
        if ($ship->isFleetLeader() && $fleetWrapper !== null) {
            $attackers = $fleetWrapper->getShipWrappers();
            $isFleetAttack = true;
        } else {
            $attackers = [$ship->getId() => $wrapper];
        }

        foreach ($attackers as $attackship) {
            $this->informations->addInformationWrapper($this->fightLib->ready($attackship));
        }

        // DEFENDING FLEETS
        foreach ($colony->getDefenders() as $fleet) {
            $this->alertRedHelper->performAttackCycle($fleet->getLeadShip(), $ship, $this->informations, true);
        }

        // ORBITAL DEFENSE
        $count = $this->colonyFunctionManager->getBuildingWithFunctionCount(
            $colony,
            BuildingEnum::BUILDING_FUNCTION_ENERGY_PHALANX,
            [ColonyFunctionManager::STATE_ENABLED]
        );
        $defendingPhalanx =  $this->attackerProviderFactory->getEnergyPhalanxAttacker($colony);

        for ($i = 0; $i < $count; $i++) {
            $attackerPool = $this->fightLib->filterInactiveShips($attackers);

            if ($attackerPool === []) {
                break;
            }
            $this->addMessageMerge($this->energyWeaponPhase->fire($defendingPhalanx, $attackerPool));
        }

        $count = $this->colonyFunctionManager->getBuildingWithFunctionCount(
            $colony,
            BuildingEnum::BUILDING_FUNCTION_PARTICLE_PHALANX,
            [ColonyFunctionManager::STATE_ENABLED]
        );
        $defendingPhalanx = $this->attackerProviderFactory->getProjectilePhalanxAttacker($colony);

        for ($i = 0; $i < $count; $i++) {
            $attackerPool = $this->fightLib->filterInactiveShips($attackers);

            if ($attackerPool === []) {
                break;
            }
            $this->addMessageMerge($this->projectileWeaponPhase->fire($defendingPhalanx, $attackerPool));
        }

        // OFFENSE OF ATTACKING SHIPS
        $isOrbitField = $this->planetFieldTypeRetriever->isOrbitField($field);
        $attackerPool = $this->fightLib->filterInactiveShips($attackers);
        $count = $this->colonyFunctionManager->getBuildingWithFunctionCount(
            $colony,
            BuildingEnum::BUILDING_FUNCTION_ANTI_PARTICLE,
            [ColonyFunctionManager::STATE_ENABLED]
        ) * 6;

        foreach ($attackerPool as $attackerWrapper) {
            $shipAttacker = $this->attackerProviderFactory->getShipAttacker($attackerWrapper);

            if ($isOrbitField) {
                $this->informations->addInformationWrapper($this->energyWeaponPhase->fireAtBuilding($shipAttacker, $field, $isOrbitField));

                if ($field->getIntegrity() === 0) {
                    break;
                }
            }
            $this->informations->addInformationWrapper($this->projectileWeaponPhase->fireAtBuilding($shipAttacker, $field, $isOrbitField, $count));

            if ($field->getIntegrity() === 0) {
                break;
            }
        }

        $this->colonyRepository->save($colony);

        $pm = sprintf(
            _("Kampf in Sektor %s, Kolonie %s\n%s"),
            $ship->getSectorString(),
            $colony->getName(),
            $this->informations->getInformationsAsString()
        );
        $this->privateMessageSender->send(
            $userId,
            $colony->getUserId(),
            $pm,
            PrivateMessageFolderSpecialEnum::PM_SPECIAL_COLONY
        );

        if ($ship->isDestroyed()) {
            $game->addInformationWrapper($this->informations);
            return;
        }
        $game->setView(ShowShip::VIEW_IDENTIFIER);

        if ($isFleetAttack) {
            $game->addInformation(_("Angriff durchgeführt"));
            $game->setTemplateVar('FIGHT_RESULTS', $this->informations->getInformations());
        } else {
            $game->addInformationWrapper($this->informations);
        }
    }

    /**
     * @param MessageInterface[] $messages
     */
    private function addMessageMerge(array $messages): void
    {
        foreach ($messages as $message) {
            $this->informations->addInformationArray($message->getMessage());
        }
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
