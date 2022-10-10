<?php

declare(strict_types=1);

namespace Stu\Orm\Entity;

/**
 * @Entity(repositoryClass="Stu\Orm\Repository\TradeLicenseInfoRepository")
 * @Table(
 *     name="stu_trade_license_info",
 *     indexes={
 *         @Index(name="trade_license_info_post_idx", columns={"posts_id"})
 *     }
 * )
 **/
class TradeLicenseInfo implements TradeLicenseInfoInterface
{
    /** 
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /** @Column(type="integer") * */
    private $posts_id = 0;

    /** @Column(type="integer") * */
    private $goods_id = 0;

    /** @Column(type="integer") * */
    private $amount = 0;

    /** @Column(type="integer") * */
    private $days = 0;

    /** @Column(type="integer") * */
    private $date = 0;

    /**
     * @ManyToOne(targetEntity="TradePost")
     * @JoinColumn(name="posts_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $tradePost;

    public function getId(): int
    {
        return $this->id;
    }

    public function setTradepost(TradePostInterface $tradepost): TradeLicenseInfoInterface
    {
        $this->tradePost = $tradepost;

        return $this;
    }

    public function getTradePostId(): int
    {
        return $this->posts_id;
    }

    public function setTradePostId(int $posts_id): TradeLicenseInfoInterface
    {
        $this->posts_id = $posts_id;

        return $this;
    }

    public function getCommodityId(): int
    {
        return $this->goods_id;
    }

    public function setCommodityId(int $commodityId): TradeLicenseInfoInterface
    {
        $this->goods_id = $commodityId;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): TradeLicenseInfoInterface
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function setDate(int $date): TradeLicenseInfoInterface
    {
        $this->date = $date;

        return $this;
    }

    public function getDays(): int
    {
        return $this->days;
    }

    public function setDays(int $days): TradeLicenseInfoInterface
    {
        $this->days = $days;

        return $this;
    }
}
