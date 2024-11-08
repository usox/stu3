<?php

declare(strict_types=1);

namespace Stu\Module\NPC\Action;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use request;
use Stu\Component\Ship\Buildplan\BuildplanSignatureCreationInterface;
use Stu\Component\Ship\Crew\ShipCrewCalculatorInterface;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Orm\Repository\BuildplanModuleRepositoryInterface;
use Stu\Orm\Repository\ModuleRepositoryInterface;
use Stu\Orm\Repository\ShipBuildplanRepositoryInterface;
use Stu\Orm\Repository\ShipRumpModuleLevelRepositoryInterface;
use Stu\Orm\Repository\ShipRumpRepositoryInterface;
use Stu\Orm\Repository\UserRepositoryInterface;
use Stu\Module\ShipModule\ModuleSpecialAbilityEnum;
use Stu\Module\NPC\View\ShowBuildplanCreator\ShowBuildplanCreator;
use Stu\Orm\Repository\NPCLogRepositoryInterface;


final class CreateBuildplan implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_CREATE_BUILDPLAN';

    private EntityManagerInterface $entityManager;
    private ShipRumpRepositoryInterface $shipRumpRepository;
    private ShipRumpModuleLevelRepositoryInterface $shipRumpModuleLevelRepository;
    private ModuleRepositoryInterface $moduleRepository;
    private ShipBuildplanRepositoryInterface $buildplanRepository;
    private BuildplanModuleRepositoryInterface $buildplanModuleRepository;
    private UserRepositoryInterface $userRepository;
    private ShipCrewCalculatorInterface $shipCrewCalculator;
    private BuildplanSignatureCreationInterface $buildplanSignatureCreation;
    private NPCLogRepositoryInterface $npcLogRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ShipRumpRepositoryInterface $shipRumpRepository,
        ShipRumpModuleLevelRepositoryInterface $shipRumpModuleLevelRepository,
        ModuleRepositoryInterface $moduleRepository,
        ShipBuildplanRepositoryInterface $buildplanRepository,
        BuildplanModuleRepositoryInterface $buildplanModuleRepository,
        UserRepositoryInterface $userRepository,
        ShipCrewCalculatorInterface $shipCrewCalculator,
        BuildplanSignatureCreationInterface $buildplanSignatureCreation,
        NPCLogRepositoryInterface $npcLogRepository
    ) {
        $this->entityManager = $entityManager;
        $this->shipRumpRepository = $shipRumpRepository;
        $this->shipRumpModuleLevelRepository = $shipRumpModuleLevelRepository;
        $this->moduleRepository = $moduleRepository;
        $this->buildplanRepository = $buildplanRepository;
        $this->buildplanModuleRepository = $buildplanModuleRepository;
        $this->userRepository = $userRepository;
        $this->shipCrewCalculator = $shipCrewCalculator;
        $this->buildplanSignatureCreation = $buildplanSignatureCreation;
        $this->npcLogRepository = $npcLogRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowBuildplanCreator::VIEW_IDENTIFIER);
        $userId = request::postIntFatal('userId');
        $rumpId = request::postIntFatal('rumpId');
        $moduleList = request::postArray('mod');
        $moduleSpecialList = request::postArray('special_mod');

        if (!$game->isAdmin() && !$game->isNpc()) {
            $game->addInformation(_('[b][color=#ff2626]Aktion nicht möglich, Spieler ist kein Admin/NPC![/color][/b]'));
            return;
        }

        $rump = $this->shipRumpRepository->find($rumpId);
        if ($rump === null) {
            throw new RuntimeException(sprintf('rumpId %d does not exist!', $rumpId));
        }

        $mod_level = $this->shipRumpModuleLevelRepository->getByShipRump($rump->getId());

        if (count($moduleList) < $mod_level->getMandatoryModulesCount()) {
            $game->addInformation('Nicht alle benötigten Module wurden ausgewählt');
            return;
        }

        $user = $this->userRepository->find($userId);
        if ($user === null) {
            throw new RuntimeException(sprintf('userId %d does not exist', $userId));
        }

        $signature = $this->buildplanSignatureCreation->createSignatureByModuleIds(
            array_merge($moduleList, $moduleSpecialList),
            0
        );

        $plan = $this->buildplanRepository->getByUserShipRumpAndSignature($userId, $rump->getId(), $signature);

        if ($plan === null) {
            $planname = sprintf(
                'Bauplan %s %s',
                $rump->getName(),
                date('d.m.Y H:i')
            );

            $plan = $this->buildplanRepository->prototype();
            $plan->setUser($user);
            $plan->setRump($rump);
            $plan->setName($planname);
            $plan->setSignature($signature);
            $plan->setBuildtime(0);

            $this->buildplanRepository->save($plan);
            $this->entityManager->flush();

            $modules = [];

            foreach ($moduleList as $moduleId) {
                $module = $this->moduleRepository->find($moduleId);
                if ($module === null) {
                    throw new RuntimeException(sprintf('moduleId %d does not exist', $moduleId));
                }

                $mod = $this->buildplanModuleRepository->prototype();
                $mod->setModuleType($module->getType());
                $mod->setBuildplan($plan);
                $mod->setModule($module);

                $modules[$moduleId] = $module;

                $this->buildplanModuleRepository->save($mod);
            }

            foreach ($moduleSpecialList as $moduleId) {
                $module = $this->moduleRepository->find($moduleId);
                if ($module === null) {
                    throw new RuntimeException(sprintf('moduleId %d does not exist', $moduleId));
                }

                $mod = $this->buildplanModuleRepository->prototype();
                $mod->setModuleType($module->getType());
                $mod->setBuildplan($plan);
                $mod->setModule($module);
                $mod->setModuleSpecial(ModuleSpecialAbilityEnum::getHash($module->getSpecials()));

                $modules[$moduleId] = $module;

                $this->buildplanModuleRepository->save($mod);
            }

            $crewInput = request::postInt('crew_input');

            if ($crewInput > 0) {
                $plan->setCrew($crewInput);
            } else {
                $plan->setCrew($this->shipCrewCalculator->getCrewUsage($modules, $rump, $user));
            }

            $this->buildplanRepository->save($plan);

            $this->entityManager->flush();

            $moduleNames = [];
            foreach ($modules as $module) {
                $moduleNames[] = $module->getName();
            }

            $reason = request::postString('reason');

            if ($reason === '') {
                $game->addInformation("Grund fehlt");
                return;
            }

            $logText = sprintf(
                '%s hat für Spieler %s (%s) einen Bauplan erstellt. Rumpf: %s, Module: %s, Crew: %d, Grund: %s',
                $game->getUser()->getName(),
                $user->getName(),
                $user->getId(),
                $rump->getName(),
                implode(', ', $moduleNames),
                $plan->getCrew(),
                $reason
            );

            $this->createLogEntry($logText, $game->getUser()->getId());

            $game->addInformation('Bauplan wurde erstellt');
        } else {
            $game->addInformation('Bauplan existiert bereits');
        }
    }
    private function createLogEntry(string $text, int $userId): void
    {
        $entry = $this->npcLogRepository->prototype();
        $entry->setText($text);
        $entry->setSourceUserId($userId);
        $entry->setDate(time());

        $this->npcLogRepository->save($entry);
    }
    public function performSessionCheck(): bool
    {
        return true;
    }
}