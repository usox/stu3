<?php

namespace Stu\Module\Config;

use Stu\Module\Config\Model\CacheSettingsInterface;
use Stu\Module\Config\Model\DbSettingsInterface;
use Stu\Module\Config\Model\DebugSettingsInterface;
use Stu\Module\Config\Model\GameSettingsInterface;

interface StuConfigInterface
{
    public function getCacheSettings(): CacheSettingsInterface;

    public function getDbSettings(): DbSettingsInterface;

    public function getDebugSettings(): DebugSettingsInterface;

    public function getGameSettings(): GameSettingsInterface;
}
