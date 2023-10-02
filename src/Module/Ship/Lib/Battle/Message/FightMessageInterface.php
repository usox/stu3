<?php

namespace Stu\Module\Ship\Lib\Battle\Message;

interface FightMessageInterface
{
    public function getSenderId(): int;

    public function getRecipientId(): ?int;

    /**
     * @return array<string>
     */
    public function getMessage(): array;

    public function add(?string $msg): void;

    /**
     * @param array<string> $msg
     */
    public function addMessageMerge(array $msg): void;

    public function isEmpty(): bool;
}
