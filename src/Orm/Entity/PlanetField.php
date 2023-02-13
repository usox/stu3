<?php

declare(strict_types=1);

namespace Stu\Orm\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Stu\Module\Colony\Lib\PlanetFieldTypeRetrieverInterface;
use Stu\Module\Tal\StatusBarColorEnum;
use Stu\Module\Tal\TalStatusBar;
use Stu\Orm\Repository\ColonyTerraformingRepositoryInterface;

/**
 * @Entity(repositoryClass="Stu\Orm\Repository\PlanetFieldRepository")
 * @Table(
 *     name="stu_colonies_fielddata",
 *     indexes={
 *         @Index(name="colony_field_idx", columns={"colonies_id","field_id"}),
 *         @Index(name="colony_building_active_idx", columns={"colonies_id", "buildings_id", "aktiv"}),
 *         @Index(name="active_idx", columns={"aktiv"})
 *     }
 * )
 **/
class PlanetField implements PlanetFieldInterface
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     *
     * @var int
     */
    private $id;

    /**
     * @Column(type="integer")
     *
     * @var int
     */
    private $colonies_id = 0;

    /**
     * @Column(type="smallint")
     *
     * @var int
     */
    private $field_id = 0;

    /**
     * @Column(type="integer")
     *
     * @var int
     */
    private $type_id = 0;

    /**
     * @Column(type="integer", nullable=true)
     *
     * @var int|null
     */
    private $buildings_id;

    /**
     * @Column(type="integer", nullable=true)
     *
     * @var int|null
     */
    private $terraforming_id;

    /**
     * @Column(type="smallint")
     *
     * @var int
     */
    private $integrity = 0;

    /**
     * @Column(type="integer")
     *
     * @var int
     */
    private $aktiv = 0;

    /**
     * @Column(type="boolean")
     *
     * @var bool
     */
    private $activate_after_build = true;

    /**
     * @var null|BuildingInterface
     *
     * @ManyToOne(targetEntity="Building")
     * @JoinColumn(name="buildings_id", referencedColumnName="id")
     */
    private $building;

    /**
     * @var null|TerraformingInterface
     *
     * @ManyToOne(targetEntity="Terraforming")
     * @JoinColumn(name="terraforming_id", referencedColumnName="id")
     */
    private $terraforming;

    /**
     * @var ColonyInterface
     *
     * @ManyToOne(targetEntity="Colony")
     * @JoinColumn(name="colonies_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $colony;

    /** @var bool */
    private $buildmode = false;

    /** @var null|ColonyTerraformingInterface */
    private $terraformingState;

    public function getId(): int
    {
        return $this->id;
    }

    public function getColonyId(): int
    {
        return $this->colonies_id;
    }

    public function getFieldId(): int
    {
        return $this->field_id;
    }

    public function setFieldId(int $fieldId): PlanetFieldInterface
    {
        $this->field_id = $fieldId;
        return $this;
    }

    public function getFieldType(): int
    {
        return $this->type_id;
    }

    public function setFieldType(int $planetFieldTypeId): PlanetFieldInterface
    {
        $this->type_id = $planetFieldTypeId;
        return $this;
    }

    public function getBuildingId(): ?int
    {
        return $this->buildings_id;
    }

    public function getTerraformingId(): ?int
    {
        return $this->terraforming_id;
    }

    public function getIntegrity(): int
    {
        return $this->integrity;
    }

    public function setIntegrity(int $integrity): PlanetFieldInterface
    {
        $this->integrity = $integrity;
        return $this;
    }

    public function getActive(): int
    {
        return $this->aktiv;
    }

    public function setActive(int $aktiv): PlanetFieldInterface
    {
        $this->aktiv = $aktiv;
        return $this;
    }

    public function getActivateAfterBuild(): bool
    {
        return $this->activate_after_build;
    }

    public function setActivateAfterBuild(bool $activateAfterBuild): PlanetFieldInterface
    {
        $this->activate_after_build = $activateAfterBuild;
        return $this;
    }

    public function setBuildMode(bool $value): void
    {
        $this->buildmode = $value;
    }

    public function getFieldTypeName(): string
    {
        // @todo remove
        global $container;

        return $container->get(PlanetFieldTypeRetrieverInterface::class)->getDescription($this->getFieldType());
    }

    public function getBuildtime(): int
    {
        return $this->getActive();
    }

    public function isActive(): bool
    {
        return $this->getActive() === 1;
    }

    public function isActivateable(): bool
    {
        if ($this->hasBuilding() === false) {
            return false;
        }
        if ($this->isUnderConstruction()) {
            return false;
        }
        return $this->getBuilding()->isActivateable();
    }

    public function hasHighDamage(): bool
    {
        if (!$this->isDamaged()) {
            return false;
        }
        if (round((100 / $this->getBuilding()->getIntegrity()) * $this->getIntegrity()) < 50) {
            return true;
        }
        return false;
    }

    public function isUnderConstruction(): bool
    {
        return $this->getActive() > 1;
    }

    public function hasBuilding(): bool
    {
        return $this->getBuilding() !== null;
    }

    public function getCssClass(): string
    {
        if ($this->buildmode === true) {
            return 'cfb';
        }
        if ($this->isActive()) {
            if ($this->isDamaged()) {
                return 'cfld';
            }
            return 'cfa';
        }
        if ($this->hasHighDamage()) {
            return 'cfhd';
        }
        if ($this->hasBuilding() && $this->isActivateable()) {
            return 'cfd';
        }

        return 'cfu';
    }

    public function getBuildingState(): string
    {
        if ($this->isUnderConstruction()) {
            return 'b';
        }
        return 'a';
    }

    public function getBuilding(): ?BuildingInterface
    {
        return $this->building;
    }

    public function setBuilding(?BuildingInterface $building): PlanetFieldInterface
    {
        $this->building = $building;

        return $this;
    }

    public function isDamaged(): bool
    {
        if (!$this->hasBuilding()) {
            return false;
        }
        if ($this->isUnderConstruction()) {
            return false;
        }
        return $this->getIntegrity() != $this->getBuilding()->getIntegrity();
    }

    public function clearBuilding(): void
    {
        $this->setBuilding(null);
        $this->setIntegrity(0);
        $this->setActive(0);
    }

    public function getColony(): ColonyInterface
    {
        return $this->colony;
    }

    public function setColony(ColonyInterface $colony): PlanetFieldInterface
    {
        $this->colony = $colony;
        return $this;
    }

    public function getTerraforming(): ?TerraformingInterface
    {
        return $this->terraforming;
    }

    public function setTerraforming(?TerraformingInterface $terraforming): PlanetFieldInterface
    {
        $this->terraforming = $terraforming;

        return $this;
    }

    public function getDayNightPrefix(): string
    {
        $twilightZone = $this->getColony()->getTwilightZone();

        if ($twilightZone >= 0) {
            return $this->getFieldId() % $this->getColony()->getSurfaceWidth() >= $twilightZone ? 'n' : 't';
        }

        return $this->getFieldId() % $this->getColony()->getSurfaceWidth() < -$twilightZone ? 'n' : 't';
    }

    public function getTerraformingState(): ?ColonyTerraformingInterface
    {
        if ($this->terraformingState === null) {
            // @todo refactor
            global $container;

            $this->terraformingState = $container->get(ColonyTerraformingRepositoryInterface::class)->getByColonyAndField(
                (int) $this->getColonyId(),
                (int) $this->getId()
            );
        }
        return $this->terraformingState;
    }

    public function getTitleString(): string
    {
        if (!$this->hasBuilding()) {
            if ($this->getTerraformingId() !== null) {
                return sprintf(
                    "%s läuft bis %s",
                    $this->getTerraforming()->getDescription(),
                    date('d.m.Y H:i', $this->getTerraformingState()->getFinishDate())
                );
            }
            return $this->getFieldTypeName();
        }
        if ($this->isUnderConstruction()) {
            return sprintf(
                _('In Bau: %s auf %s - Fertigstellung: %s'),
                $this->getBuilding()->getName(),
                $this->getFieldTypeName(),
                date('d.m.Y H:i', $this->getBuildtime())
            );
        }
        if (!$this->isActivateable()) {
            return $this->getBuilding()->getName() . " auf " . $this->getFieldTypeName();
        }
        if ($this->isActive()) {
            if ($this->isDamaged()) {
                return $this->getBuilding()->getName() . " (aktiviert, beschädigt) auf " . $this->getFieldTypeName();
            }
            return $this->getBuilding()->getName() . " (aktiviert) auf " . $this->getFieldTypeName();
        }
        if ($this->hasHighDamage()) {
            return $this->getBuilding()->getName() . " (stark beschädigt) auf " . $this->getFieldTypeName();
        }
        if ($this->isActivateable()) {
            return $this->getBuilding()->getName() . " (deaktiviert) auf " . $this->getFieldTypeName();
        }
        return $this->getBuilding()->getName() . " auf " . $this->getFieldTypeName();
    }

    public function getBuildProgress(): int
    {
        $start = $this->getBuildtime() - $this->getBuilding()->getBuildTime();
        return time() - $start;
    }

    public function getOverlayWidth(): int
    {
        $buildtime = $this->getBuilding()->getBuildtime();
        $perc = max(0, @round((100 / $buildtime) * min($this->getBuildProgress(), $buildtime)));
        return (int) round((40 / 100) * $perc);
    }

    public function getPictureType(): string
    {
        return $this->getBuildingId() . "/" . $this->getBuilding()->getBuildingType() . $this->getBuildingState();
    }

    public function isColonizeAble(): bool
    {
        return in_array($this->getFieldType(), $this->getColony()->getColonyClass()->getColonizeableFields());
    }

    /**
     * @todo temporary, remove it.
     */
    public function getConstructionStatusBar(): string
    {
        return (new TalStatusBar())
            ->setColor(StatusBarColorEnum::STATUSBAR_GREEN)
            ->setLabel(_('Fortschritt'))
            ->setMaxValue($this->getBuilding()->getBuildtime())
            ->setValue($this->getBuildProgress())
            ->render();
    }

    /**
     * @todo temporary, remove it.
     */
    public function getTerraformingStatusBar(): string
    {
        return (new TalStatusBar())
            ->setColor(StatusBarColorEnum::STATUSBAR_GREEN)
            ->setLabel(_('Fortschritt'))
            ->setMaxValue($this->getTerraforming()->getDuration())
            ->setValue($this->getTerraformingState()->getProgress())
            ->render();
    }
}
