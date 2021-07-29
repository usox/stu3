<?php

declare(strict_types=1);

namespace Stu\Orm\Entity;

/**
 * @Entity(repositoryClass="Stu\Orm\Repository\ConstructionProgressRepository")
 * @Table(
 *     name="stu_construction_progress",
 *     indexes={
 *         @Index(name="construction_progress_ship_idx", columns={"ship_id"})
 *     }
 * )
 **/
class ConstructionProgress implements ConstructionProgressInterface
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /** @Column(type="integer") * */
    private $ship_id = 0;

    /** @Column(type="integer") */
    private $remaining_ticks = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setShipId(int $shipId): ConstructionProgressInterface
    {
        $this->ship_id = $shipId;

        return $this;
    }

    public function getRemainingTicks(): int
    {
        return $this->remaining_ticks;
    }

    public function setRemainingTicks(int $remainingTicks): ConstructionProgressInterface
    {
        $this->remaining_ticks = $remainingTicks;

        return $this;
    }
}
