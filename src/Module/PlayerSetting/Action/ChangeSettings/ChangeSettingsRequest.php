<?php

declare(strict_types=1);

namespace Stu\Module\PlayerSetting\Action\ChangeSettings;

use Stu\Lib\Request\CustomControllerHelperTrait;

final class ChangeSettingsRequest implements ChangeSettingsRequestInterface
{
    use CustomControllerHelperTrait;

    public function getEmailNotification(): int
    {
        return $this->queryParameter('email_not')->int()->defaultsTo(0);
    }

    public function getSaveLogin(): int
    {
        return $this->queryParameter('save_login')->int()->defaultsTo(0);
    }

    public function getStorageNotification(): int
    {
        return $this->queryParameter('storage_not')->int()->defaultsTo(0);
    }

    public function getShowOnlineState(): int
    {
        return $this->queryParameter('show_online')->int()->defaultsTo(0);
    }
}