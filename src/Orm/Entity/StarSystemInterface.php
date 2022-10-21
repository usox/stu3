<?php

namespace Stu\Orm\Entity;

interface StarSystemInterface
{
    public function getId(): int;

    public function getCx(): int;

    public function setCx(int $cx): StarSystemInterface;

    public function getCy(): int;

    public function setCy(int $cy): StarSystemInterface;

    public function getType(): int;

    public function setType(int $type): StarSystemInterface;

    public function getName(): string;

    public function setName(string $name): StarSystemInterface;

    public function getMaxX(): int;

    public function setMaxX(int $maxX): StarSystemInterface;

    public function getMaxY(): int;

    public function setMaxY(int $maxY): StarSystemInterface;

    public function getBonusFieldAmount(): int;

    public function setBonusFieldAmount(int $bonusFieldAmount): StarSystemInterface;

    public function getSystemType(): StarSystemTypeInterface;

    public function getDatabaseEntry(): ?DatabaseEntryInterface;

    public function setDatabaseEntry(?DatabaseEntryInterface $databaseEntry): StarSystemInterface;

    public function getMapField(): MapInterface;

    public function getBase(): ?ShipInterface;

    /**
     * @return StarSystemMapInterface[]
     */
    public function getFields(): array;

    public function isWormhole(): bool;
}
