<?php

declare(strict_types=1);

namespace Stu\Orm\Entity;

use Stu\Module\Commodity\CommodityTypeEnum;

/**
 * @Entity(repositoryClass="Stu\Orm\Repository\CommodityRepository")
 * @Table(name="stu_goods",indexes={
 * })
 **/
class Commodity implements CommodityInterface
{
    /** 
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /** @Column(type="string") * */
    private $name = '';

    /** @Column(type="smallint") * */
    private $sort = 0;

    /** @Column(type="boolean") * */
    private $view = true;

    /** @Column(type="smallint") * */
    private $type = CommodityTypeEnum::GOOD_TYPE_STANDARD;

    /** @Column(type="boolean") * */
    private $npc_good = false;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): CommodityInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): CommodityInterface
    {
        $this->sort = $sort;

        return $this;
    }

    public function getView(): bool
    {
        return $this->view;
    }

    public function setView(bool $view): CommodityInterface
    {
        $this->view = $view;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $typeId): CommodityInterface
    {
        $this->type = $typeId;

        return $this;
    }

   public function getNPCgood(): bool
    {
        return $this->npc_good;
    }

    public function setNPCgood(bool $npc_good): CommodityInterface
    {
        $this->npc_good = $npc_good;

        return $this;
    }

    public function isTradeable(): bool
    {
        return $this->getType() === CommodityTypeEnum::GOOD_TYPE_STANDARD && $this->getNPCgood() === false;
    }

    public function isBeamable(): bool
    {
        return $this->getType() === CommodityTypeEnum::GOOD_TYPE_STANDARD && $this->getView() === true;
    }

    public function isSaveable(): bool
    {
        return $this->getType() === CommodityTypeEnum::GOOD_TYPE_STANDARD;
    }

    public function isShuttle(): bool
    {
        return in_array(intdiv($this->getId(), 10) * 10, CommodityTypeEnum::BASE_IDS_SHUTTLE);
    }

    public function isWorkbee(): bool
    {
        return intdiv($this->getId(), 10) * 10 === CommodityTypeEnum::BASE_ID_WORKBEE;
    }

    /**
     * @deprecated
     */
    public function isIllegal($network): bool
    {
        // @todo remove
        return false;
    }

    public function getTransferCount(): int
    {
        // @todo Anzahl Waren pro Energieeinheit
        // Möglicherweise einstellbar nach Warentyp
        return 1;
    }
}
