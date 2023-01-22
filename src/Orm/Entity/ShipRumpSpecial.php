<?php

declare(strict_types=1);

namespace Stu\Orm\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity(repositoryClass="Stu\Orm\Repository\ShipRumpSpecialRepository")
 * @Table(
 *     name="stu_rumps_specials",
 *     indexes={
 *         @Index(name="rump_special_ship_rump_idx", columns={"rumps_id"})
 *     }
 * )
 **/
class ShipRumpSpecial implements ShipRumpSpecialInterface
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /** @Column(type="integer") * */
    private $rumps_id = 0;

    /** @Column(type="integer") * */
    private $special = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function getShipRumpId(): int
    {
        return $this->rumps_id;
    }

    public function setShipRumpId(int $shipRumpId): ShipRumpSpecialInterface
    {
        $this->rumps_id = $shipRumpId;

        return $this;
    }

    public function getSpecialId(): int
    {
        return $this->special;
    }

    public function setSpecialId(int $specialId): ShipRumpSpecialInterface
    {
        $this->special = $specialId;

        return $this;
    }
}
