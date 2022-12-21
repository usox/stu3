<?php

declare(strict_types=1);

namespace Stu\Orm\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\PlayerSetting\Lib\UserEnum;


/**
 * @Entity(repositoryClass="Stu\Orm\Repository\TradePostRepository")
 * @Table(
 *     name="stu_trade_posts",
 *     indexes={
 *         @Index(name="trade_network_idx", columns={"trade_network"})
 *     }
 * )
 **/
class TradePost implements TradePostInterface
{
    /** 
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /** @Column(type="integer") * */
    private $user_id = 0;

    /** @Column(type="string") */
    private $name = '';

    /** @Column(type="text") */
    private $description = '';

    /** @Column(type="integer") * */
    private $ship_id = 0;

    /** @Column(type="smallint") * */
    private $trade_network = 0;

    /** @Column(type="smallint") * */
    private $level = 0;

    /** @Column(type="integer") * */
    private $transfer_capacity = 0;

    /** @Column(type="integer") * */
    private $storage = 0;

    /**
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @OneToMany(targetEntity="TradeLicenseInfo", mappedBy="tradePost", cascade={"remove"})
     * @OrderBy({"id" = "DESC"})
     */
    private $licenseInfos;

    /**
     * @OneToOne(targetEntity="Ship", inversedBy="tradePost")
     * @JoinColumn(name="ship_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $ship;

    /**
     * @OneToMany(targetEntity="ShipCrew", mappedBy="tradepost")
     */
    private $crewAssignments;

    public function __construct()
    {
        $this->licenseInfos = new ArrayCollection();
        $this->crewAssignments = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): TradePostInterface
    {
        $this->user = $user;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): TradePostInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): TradePostInterface
    {
        $this->description = $description;

        return $this;
    }

    public function getShipId(): int
    {
        return $this->ship_id;
    }

    public function setShipId(ShipInterface $ship): TradePostInterface
    {
        $this->ship_id = $ship;

        return $this;
    }

    public function getTradeNetwork(): int
    {
        return $this->trade_network;
    }

    public function setTradeNetwork(int $tradeNetwork): TradePostInterface
    {
        $this->trade_network = $tradeNetwork;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): TradePostInterface
    {
        $this->level = $level;

        return $this;
    }

    public function getTransferCapacity(): int
    {
        return $this->transfer_capacity;
    }

    public function setTransferCapacity(int $transferCapacity): TradePostInterface
    {
        $this->transfer_capacity = $transferCapacity;

        return $this;
    }

    public function getStorage(): int
    {
        return $this->storage;
    }

    public function setStorage(int $storage): TradePostInterface
    {
        $this->storage = $storage;

        return $this;
    }

    public function getLatestLicenseInfo(): ?TradeLicenseInfoInterface
    {
        if (empty($this->licenseInfos)) {
            return null;
        }
        if (current($this->licenseInfos->getValues()) === false) {
            return null;
        } else {
            return current($this->licenseInfos->getValues());
        }
    }

    public function getShip(): ShipInterface
    {
        return $this->ship;
    }

    public function setShip(ShipInterface $ship): TradePostInterface
    {
        $this->ship = $ship;

        return $this;
    }

    public function getCrewAssignments(): Collection
    {
        return $this->crewAssignments;
    }

    public function getCrewCountOfCurrentUser(): int
    {
        global $container;
        $currentUser = $container->get(GameControllerInterface::class)->getUser();

        $count = 0;

        foreach ($this->crewAssignments as $crewAssignment) {
            if ($crewAssignment->getUser() === $currentUser) {
                $count++;
            }
        }

        return $count;
    }

    public function isNpcTradepost(): bool
    {
        return $this->getUserId() < UserEnum::USER_FIRST_ID;
    }
}
