<?php

namespace Stu\Orm\Entity;

interface TorpedoHullInterface
{
    public function getId(): int;

    public function getModuleId(): int;

    public function setModuleId(int $moduleId): TorpedoHullInterface;

    public function getTorpedoType(): int;

    public function setTorpedoType(int $torpedoType): TorpedoHullInterface;

    public function getModificator(): int;

    public function setModificator(int $Modificator): TorpedoHullInterface;

    public function getTorpedo(): ?TorpedoTypeInterface;

    public function getModule(): ?ModuleInterface;

    public function calculateGradientColor(): string;

    /**
     * @param array<string> $color
     */
    public function hexToRgb(string $color): array;
    /**
     * @param array<mixed> $rgb1
     * @param array<mixed> $rgb2
     * @param array<mixed> $percent
     */
    public function calculateGradientRgb(array $rgb1, array $rgb2, float $percent): array;
    /**
     * @param array<mixed> $rgb
     */
    public function rgbToHex(array $rgb): string;
}