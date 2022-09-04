<?php

namespace Stu\Orm\Entity;

interface TradeLicenceCreationInterface
{

    public function getId(): int;

    public function setTradepost(TradePostInterface $tradepost): TradeLicenceCreationInterface;

    public function getTradePostId(): int;

    public function setTradePostId(int $posts_id): TradeLicenceCreationInterface;

    public function getGoodsId(): int;

    public function setGoodsId(int $goods_id): TradeLicenceCreationInterface;

    public function getAmount(): int;

    public function setAmount(int $amount): TradeLicenceCreationInterface;

    public function getDate(): int;

    public function setDate(int $date): TradeLicenceCreationInterface;

    public function getDays(): int;

    public function setDays(int $days): TradeLicenceCreationInterface;
}
