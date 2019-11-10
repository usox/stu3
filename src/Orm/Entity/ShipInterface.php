<?php

namespace Stu\Orm\Entity;

use Doctrine\Common\Collections\Collection;
use Stu\Lib\DamageWrapper;

interface ShipInterface
{
    public function getId(): int;

    /**
     * @deprecated
     */
    public function getUserId(): int;

    public function getFleetId(): ?int;

    public function setFleetId(?int $fleetId): ShipInterface;

    public function getSystemsId(): ?int;

    public function getCx(): int;

    public function setCx(int $cx): ShipInterface;

    public function getCy(): int;

    public function setCy(int $cy): ShipInterface;

    public function getSx(): int;

    public function setSx(int $sx): ShipInterface;

    public function getSy(): int;

    public function setSy(int $sy): ShipInterface;

    public function getFlightDirection(): int;

    public function setFlightDirection(int $direction): ShipInterface;

    public function getName(): string;

    public function setName(string $name): ShipInterface;

    public function getAlertState(): int;

    public function setAlertState(int $alertState): ShipInterface;

    public function getWarpState(): bool;

    public function setWarpState(bool $warpState): ShipInterface;

    public function getWarpcoreLoad(): int;

    public function setWarpcoreLoad(int $warpcoreLoad): ShipInterface;

    public function getCloakState(): bool;

    public function setCloakState(bool $cloakState): ShipInterface;

    public function isCloakable(): bool;

    public function setCloakable(bool $cloakable): ShipInterface;

    public function getEps(): int;

    public function setEps(int $eps): ShipInterface;

    public function getMaxEps(): int;

    public function setMaxEps(int $maxEps): ShipInterface;

    public function getEBatt(): int;

    public function setEBatt(int $batt): ShipInterface;

    public function getMaxEBatt(): int;

    public function setMaxEBatt(int $maxBatt): ShipInterface;

    public function getHuell(): int;

    public function setHuell(int $hull): ShipInterface;

    public function getMaxHuell(): int;

    public function setMaxHuell(int $maxHull): ShipInterface;

    public function getShield(): int;

    public function setShield(int $schilde): ShipInterface;

    public function getMaxShield(): int;

    public function setMaxShield(int $maxShields): ShipInterface;

    public function getShieldState(): bool;

    public function setShieldState(bool $shieldState): ShipInterface;

    public function getTraktorShipId(): int;

    public function setTraktorShipId(int $traktorShipId): ShipInterface;

    public function getTraktormode(): int;

    public function setTraktormode(int $traktormode): ShipInterface;

    public function getNbs(): bool;

    public function setNbs(bool $nbs): ShipInterface;

    public function getLss(): bool;

    public function setLss(bool $lss): ShipInterface;

    public function getPhaser(): bool;

    public function setPhaser(bool $energyWeaponState): ShipInterface;

    public function getTorpedos(): bool;

    public function setTorpedos(bool $projectileWeaponState): ShipInterface;

    public function getFormerRumpId(): int;

    public function setFormerRumpId(int $formerShipRumpId): ShipInterface;

    public function getTorpedoCount(): int;

    public function setTorpedoCount(int $torpedoAmount): ShipInterface;

    public function getTradePostId(): int;

    public function setTradePostId(int $tradePostId): ShipInterface;

    public function getEBattWaitingTime(): int;

    public function setEBattWaitingTime(int $batteryCooldown): ShipInterface;

    public function isBase(): bool;

    public function setIsBase(bool $isBase): ShipInterface;

    public function getDatabaseId(): int;

    public function setDatabaseId(int $databaseEntryId): ShipInterface;

    public function getIsDestroyed(): bool;

    public function setIsDestroyed(bool $isDestroyed): ShipInterface;

    public function getDisabled(): bool;

    public function setDisabled(bool $disabled): ShipInterface;

    public function getCanBeDisabled(): bool;

    public function setCanBeDisabled(bool $canBeDisabled): ShipInterface;

    public function getHitChance(): int;

    public function setHitChance(int $hitChance): ShipInterface;

    public function getEvadeChance(): int;

    public function setEvadeChance(int $evadeChance): ShipInterface;

    public function getReactorOutput(): int;

    public function setReactorOutput(int $reactorOutput): ShipInterface;

    public function getBaseDamage(): int;

    public function setBaseDamage(int $baseDamage): ShipInterface;

    public function getSensorRange(): int;

    public function setSensorRange(int $sensorRange): ShipInterface;

    public function getShieldRegenerationTimer(): int;

    public function setShieldRegenerationTimer(int $shieldRegenerationTimer): ShipInterface;

    public function getState(): int;

    public function setState(int $state): ShipInterface;

    /**
     * @return ShipCrewInterface[]
     */
    public function getCrewlist(): Collection;

    public function getPosX(): int;

    public function getPosY(): int;

    public function getCrewCount(): int;

    public function leaveFleet(): void;

    public function getFleet(): ?FleetInterface;

    public function setFleet(?FleetInterface $fleet): ShipInterface;

    public function isFleetLeader(): bool;

    public function getUser(): UserInterface;

    public function setUser(UserInterface $user): ShipInterface;

    public function setPosX(int $value): void;

    public function setPosY($value): void;

    public function getSystem(): ?StarSystemInterface;

    public function setSystem(?StarSystemInterface $starSystem): ShipInterface;

    public function getWarpcoreCapacity(): int;

    public function getReactorCapacity(): int;

    public function getEffectiveEpsProduction(): int;

    public function getWarpcoreUsage(): int;

    public function isEBattUseable(): bool;

    public function isWarpAble(): bool;

    public function isTraktorbeamActive(): bool;

    public function traktorBeamFromShip(): bool;

    public function traktorBeamToShip(): bool;

    public function getTraktorShip(): ?ShipInterface;

    public function unsetTraktor(): void;

    public function deactivateTraktorBeam(): void;

    public function isOverSystem(): ?StarSystemInterface;

    public function isWarpPossible(): bool;

    public function getTorpedo(): ?TorpedoTypeInterface;

    public function setTorpedo(?TorpedoTypeInterface $torpedoType): ShipInterface;

    /**
     * @return ShipStorageInterface[] Indexed by commodityId
     */
    public function getStorage(): Collection;

    public function getStorageSum(): int;

    public function getMaxStorage(): int;

    public function getSectorString(): string;

    public function getBuildplan(): ?ShipBuildplanInterface;

    public function setBuildplan(?ShipBuildplanInterface $shipBuildplan): ShipInterface;

    public function getEpsUsage(): int;

    public function lowerEpsUsage($value): void;

    /**
     * @return ShipSystemInterface[]
     */
    public function getSystems(): Collection;

    public function hasShipSystem($system): bool;

    public function getShipSystem($system): ShipSystemInterface;

    /**
     * @return ShipSystemInterface[]
     */
    public function getActiveSystems(): array;

    public function displayNbsActions(): bool;

    public function traktorbeamNotPossible(): bool;

    public function isInterceptAble(): bool;

    public function getMapCX(): int;

    public function getMapCY(): int;

    public function getCrewBySlot($slot): array;

    public function dockedOnTradePost(): bool;

    public function getDockPrivileges(): Collection;

    public function hasFreeDockingSlots(): bool;

    public function getFreeDockingSlotCount(): int;

    public function getDockedShipCount(): int;

    /**
     * @return StarSystemMapInterface|MapInterface
     */
    public function getCurrentMapField();

    public function getShieldRegenerationRate(): int;

    public function canIntercept(): bool;

    public function canLandOnCurrentColony(): bool;

    public function canBeAttacked(): bool;

    public function canAttack(): bool;

    public function hasEscapePods(): bool;

    public function canBeRepaired(): bool;

    public function cancelRepair(): void;

    public function getRepairRate(): int;

    public function canInteractWith($target, bool $colony = false): bool;

    public function hasActiveWeapons(): bool;

    public function getRump(): ShipRumpInterface;

    public function setRump(ShipRumpInterface $shipRump): ShipInterface;

    public function hasPhaser(): bool;

    public function hasTorpedo(): bool;

    public function hasWarpcore(): bool;

    public function getMaxTorpedos(): int;

    public function getDockedShips(): Collection;

    public function getDockedTo(): ?ShipInterface;

    public function setDockedTo(?ShipInterface $dockedTo): ShipInterface;
}
