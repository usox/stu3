<?php

namespace Stu\Module\Config\Model;

interface GameSettingsInterface
{
    public function getTempDir(): string;

    public function getColonySettings(): ColonySettingsInterface;
}
