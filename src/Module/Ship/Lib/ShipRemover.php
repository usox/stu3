<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Lib;

use Stu\Component\Game\GameEnum;
use Stu\Component\Ship\Repair\CancelRepairInterface;
use Stu\Component\Ship\ShipRumpEnum;
use Stu\Component\Ship\ShipStateEnum;
use Stu\Component\Ship\Storage\ShipStorageManagerInterface;
use Stu\Component\Ship\System\ShipSystemManagerInterface;
use Stu\Component\Ship\System\ShipSystemTypeEnum;
use Stu\Module\Logging\LoggerEnum;
use Stu\Module\Logging\LoggerUtilFactoryInterface;
use Stu\Module\Logging\LoggerUtilInterface;
use Stu\Module\Message\Lib\PrivateMessageFolderSpecialEnum;
use Stu\Module\Message\Lib\PrivateMessageSenderInterface;
use Stu\Module\Ship\Lib\ShipLeaverInterface;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Entity\TradePostInterface;
use Stu\Orm\Repository\FleetRepositoryInterface;
use Stu\Orm\Repository\ShipCrewRepositoryInterface;
use Stu\Orm\Repository\ShipRepositoryInterface;
use Stu\Orm\Repository\ShipRumpRepositoryInterface;
use Stu\Orm\Repository\ShipSystemRepositoryInterface;
use Stu\Orm\Repository\StorageRepositoryInterface;
use Stu\Orm\Repository\TradePostRepositoryInterface;
use Stu\Orm\Repository\UserRepositoryInterface;

final class ShipRemover implements ShipRemoverInterface
{
    private ShipSystemRepositoryInterface $shipSystemRepository;

    private StorageRepositoryInterface $storageRepository;

    private ShipStorageManagerInterface $shipStorageManager;

    private ShipCrewRepositoryInterface $shipCrewRepository;

    private FleetRepositoryInterface $fleetRepository;

    private ShipRepositoryInterface $shipRepository;

    private UserRepositoryInterface $userRepository;

    private ShipRumpRepositoryInterface $shipRumpRepository;

    private ShipSystemManagerInterface $shipSystemManager;

    private ShipLeaverInterface $shipLeaver;

    private CancelColonyBlockOrDefendInterface $cancelColonyBlockOrDefend;

    private AstroEntryLibInterface $astroEntryLib;

    private ShipTorpedoManagerInterface $shipTorpedoManager;

    private TradePostRepositoryInterface $tradePostRepository;

    private CancelRepairInterface $cancelRepair;

    private ShipWrapperFactoryInterface $shipWrapperFactory;

    private PrivateMessageSenderInterface $privateMessageSender;

    private LoggerUtilInterface $loggerUtil;

    public function __construct(
        ShipSystemRepositoryInterface $shipSystemRepository,
        StorageRepositoryInterface $storageRepository,
        ShipStorageManagerInterface $shipStorageManager,
        ShipCrewRepositoryInterface $shipCrewRepository,
        FleetRepositoryInterface $fleetRepository,
        ShipRepositoryInterface $shipRepository,
        UserRepositoryInterface $userRepository,
        ShipRumpRepositoryInterface $shipRumpRepository,
        ShipSystemManagerInterface $shipSystemManager,
        ShipLeaverInterface $shipLeaver,
        CancelColonyBlockOrDefendInterface $cancelColonyBlockOrDefend,
        AstroEntryLibInterface $astroEntryLib,
        ShipTorpedoManagerInterface $shipTorpedoManager,
        TradePostRepositoryInterface $tradePostRepository,
        CancelRepairInterface $cancelRepair,
        ShipWrapperFactoryInterface $shipWrapperFactory,
        PrivateMessageSenderInterface $privateMessageSender,
        LoggerUtilFactoryInterface $loggerUtilFactory
    ) {
        $this->shipSystemRepository = $shipSystemRepository;
        $this->storageRepository = $storageRepository;
        $this->shipStorageManager = $shipStorageManager;
        $this->shipCrewRepository = $shipCrewRepository;
        $this->fleetRepository = $fleetRepository;
        $this->shipRepository = $shipRepository;
        $this->userRepository = $userRepository;
        $this->shipRumpRepository = $shipRumpRepository;
        $this->shipSystemManager = $shipSystemManager;
        $this->shipLeaver = $shipLeaver;
        $this->cancelColonyBlockOrDefend = $cancelColonyBlockOrDefend;
        $this->astroEntryLib = $astroEntryLib;
        $this->shipTorpedoManager = $shipTorpedoManager;
        $this->tradePostRepository = $tradePostRepository;
        $this->cancelRepair = $cancelRepair;
        $this->shipWrapperFactory = $shipWrapperFactory;
        $this->privateMessageSender = $privateMessageSender;
        $this->loggerUtil = $loggerUtilFactory->getLoggerUtil();
    }

    public function destroy(ShipWrapperInterface $wrapper): ?string
    {
        $msg = null;

        $ship = $wrapper->get();
        $this->shipSystemManager->deactivateAll($wrapper);

        $fleet = $ship->getFleet();

        if ($ship->isFleetLeader()) {
            $this->changeFleetLeader($ship);
        } else if ($fleet !== null) {
            $fleet->getShips()->removeElement($ship);

            $ship->setFleet(null);
            $ship->setIsFleetLeader(false);
            $ship->setFleetId(null);
        }

        if ($ship->getState() === ShipStateEnum::SHIP_STATE_SYSTEM_MAPPING) {
            $this->astroEntryLib->cancelAstroFinalizing($ship);
        }

        //leave ship if there is crew
        if ($ship->getCrewCount() > 0) {
            $msg = $this->shipLeaver->evacuate($wrapper);
        }

        /**
         * this is buggy :(
         * throws ORMInvalidArgumentException
         * 
         if ($ship->getRump()->isEscapePods())
         {
             $this->remove($ship);
             return $msg;
            }
         */

        $this->leaveSomeIntactModules($ship);

        $ship->setFormerRumpId($ship->getRump()->getId());
        $ship->setRump($this->shipRumpRepository->find(ShipRumpEnum::SHIP_CATEGORY_TRUMFIELD));
        $ship->setHuell((int) ceil($ship->getMaxHuell() / 20));
        $ship->setUser($this->userRepository->find(GameEnum::USER_NOONE));
        $ship->setBuildplan(null);
        $ship->setIsBase(false);
        $ship->setShield(0);
        $ship->setAlertStateGreen();
        $ship->setInfluenceArea(null);
        $ship->setDockedTo(null);
        $oldName = $ship->getName();
        $ship->setName(_('Trümmer'));
        $ship->setIsDestroyed(true);
        $this->cancelRepair->cancelRepair($ship);

        // delete ship systems
        $this->shipSystemRepository->truncateByShip((int) $ship->getId());
        $ship->getSystems()->clear();

        // delete torpedo storage
        $this->shipTorpedoManager->removeTorpedo($wrapper);

        //delete trade post stuff
        if ($ship->getTradePost() !== null) {
            $this->destroyTradepost($ship->getTradePost());
            $ship->setTradePost(null);
        }

        // change storage owner
        $this->orphanizeStorage($ship);

        $this->shipRepository->save($ship);

        // undock docked ships
        foreach ($ship->getDockedShips() as $dockedShip) {
            $dockedShip->setDockedTo(null);
            $this->shipRepository->save($dockedShip);
        }

        // clear tractor status
        if ($ship->isTractored()) {
            $tractoringShipWrapper = $wrapper->getTractoringShipWrapper();
            $tractoringShip = $tractoringShipWrapper->get();
            $this->shipSystemManager->deactivate($tractoringShipWrapper, ShipSystemTypeEnum::SYSTEM_TRACTOR_BEAM, true);

            $href = sprintf(_('ship.php?SHOW_SHIP=1&id=%d'), $tractoringShip->getId());

            $this->privateMessageSender->send(
                GameEnum::USER_NOONE,
                $tractoringShip->getUser()->getId(),
                sprintf('Die im Traktorstrahl der %s befindliche %s wurde zerstört', $tractoringShip->getName(), $oldName),
                $tractoringShip->isBase() ? PrivateMessageFolderSpecialEnum::PM_SPECIAL_STATION : PrivateMessageFolderSpecialEnum::PM_SPECIAL_SHIP,
                $href
            );
        }

        // reset tracker devices
        $this->resetTrackerDevices($ship->getId());

        return $msg;
    }

    private function resetTrackerDevices(int $shipId): void
    {
        foreach ($this->shipSystemRepository->getTrackingShipSystems($shipId) as $system) {
            $wrapper = $this->shipWrapperFactory->wrapShip($system->getShip());

            $this->shipSystemManager->deactivate($wrapper, ShipSystemTypeEnum::SYSTEM_TRACKER, true);
        }
    }

    private function leaveSomeIntactModules(ShipInterface $ship): void
    {
        if ($ship->isShuttle()) {
            return;
        }

        $intactModules = [];

        foreach ($ship->getSystems() as $system) {
            if (
                $system->getModule() !== null
                && $system->getStatus() == 100
            ) {
                $module = $system->getModule();

                if (!array_key_exists($module->getId(), $intactModules)) {
                    $intactModules[$module->getId()] = $module;
                }
            }
        }

        //leave 50% of all intact modules
        $leaveCount = (int) ceil(count($intactModules) / 2);
        for ($i = 1; $i <= $leaveCount; $i++) {
            $module = $intactModules[array_rand($intactModules)];
            unset($intactModules[$module->getId()]);

            $this->shipStorageManager->upperStorage(
                $ship,
                $module->getCommodity(),
                1
            );
        }
    }

    private function orphanizeStorage(ShipInterface $ship): void
    {
        foreach ($ship->getStorage() as $storage) {
            $storage->setUser($this->userRepository->find(GameEnum::USER_NOONE));
            $this->storageRepository->save($storage);
        }
    }

    private function destroyTradepost(TradePostInterface $tradePost)
    {
        //salvage offers and storage
        $storages = $this->storageRepository->getByTradePost($tradePost->getId());
        foreach ($storages as $storage) {

            //only 50% off all storages
            if (rand(0, 1) === 0) {
                $this->storageRepository->delete($storage);
                continue;
            }

            //only 0 to 50% of the specific amount
            $amount = (int)ceil($storage->getAmount() / 100 * rand(0, 50));

            if ($amount === 0) {
                $this->storageRepository->delete($storage);
                continue;
            }

            //add to trumfield storage
            $this->shipStorageManager->upperStorage(
                $tradePost->getShip(),
                $storage->getCommodity(),
                $amount
            );

            $this->storageRepository->delete($storage);
        }

        //remove tradepost and cascading stuff
        $this->tradePostRepository->delete($tradePost);
    }

    public function remove(ShipInterface $ship, ?bool $truncateCrew = false): void
    {
        if ($ship->isFleetLeader() && $ship->getFleet() !== null) {
            $this->changeFleetLeader($ship);
        }

        if ($ship->getState() === ShipStateEnum::SHIP_STATE_SYSTEM_MAPPING) {
            $this->astroEntryLib->cancelAstroFinalizing($ship);
        }

        $wrapper = $this->shipWrapperFactory->wrapShip($ship);

        //both sides have to be cleared, foreign key violation
        if ($ship->isTractoring()) {
            $this->shipSystemManager->deactivate($wrapper, ShipSystemTypeEnum::SYSTEM_TRACTOR_BEAM, true);
        } else if ($ship->isTractored()) {
            $this->shipSystemManager->deactivate($wrapper->getTractoringShipWrapper(), ShipSystemTypeEnum::SYSTEM_TRACTOR_BEAM, true);
        }

        foreach ($ship->getStorage() as $item) {
            $this->storageRepository->delete($item);
        }

        foreach ($ship->getDockedShips() as $dockedShip) {
            $dockedShip->setDockedTo(null);
            $this->shipRepository->save($dockedShip);
        }

        // delete torpedo storage
        $this->shipTorpedoManager->removeTorpedo($wrapper);

        if ($truncateCrew) {
            $this->shipCrewRepository->truncateByShip($ship->getId());
        }

        // reset tracker devices
        $this->resetTrackerDevices($ship->getId());

        $this->shipRepository->delete($ship);
    }

    private function changeFleetLeader(ShipInterface $oldLeader): void
    {
        $ship = current(
            array_filter(
                $oldLeader->getFleet()->getShips()->toArray(),
                function (ShipInterface $ship) use ($oldLeader): bool {
                    return $ship !== $oldLeader;
                }
            )
        );

        if (!$ship) {
            $this->cancelColonyBlockOrDefend->work($oldLeader);
        }

        $fleet = $oldLeader->getFleet();

        $oldLeader->setFleet(null);
        $oldLeader->setIsFleetLeader(false);
        $fleet->getShips()->removeElement($oldLeader);

        $this->shipRepository->save($oldLeader);

        if (!$ship) {
            $this->fleetRepository->delete($fleet);

            return;
        }
        $fleet->setLeadShip($ship);
        $ship->setIsFleetLeader(true);

        $this->shipRepository->save($ship);
        $this->fleetRepository->save($fleet);
    }
}
