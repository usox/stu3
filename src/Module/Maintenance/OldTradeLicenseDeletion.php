<?php

namespace Stu\Module\Maintenance;

use Stu\Component\Game\GameEnum;
use Stu\Module\Message\Lib\PrivateMessageFolderSpecialEnum;
use Stu\Module\Message\Lib\PrivateMessageSenderInterface;
use Stu\Orm\Repository\TradeLicenseInfoRepositoryInterface;
use Stu\Orm\Repository\TradeLicenseRepositoryInterface;

final class OldTradeLicenseDeletion implements MaintenanceHandlerInterface
{
    private const INFORM_ABOUT_ALMOST_EXPIRED_IN_DAYS = 7;

    private TradeLicenseRepositoryInterface $tradeLicenseRepository;

    private TradeLicenseInfoRepositoryInterface $tradeLicenseInfoRepository;

    private PrivateMessageSenderInterface $privateMessageSender;

    public function __construct(
        TradeLicenseRepositoryInterface $tradeLicenseRepository,
        TradeLicenseInfoRepositoryInterface $tradeLicenseInfoRepository,
        PrivateMessageSenderInterface $privateMessageSender
    ) {
        $this->tradeLicenseRepository = $tradeLicenseRepository;
        $this->tradeLicenseInfoRepository = $tradeLicenseInfoRepository;
        $this->privateMessageSender = $privateMessageSender;
    }

    public function handle(): void
    {
        $deletedLicenses = $this->deleteExpiredLicenses();
        $this->informAboutAlmostExpiredLicenses($deletedLicenses);
    }

    private function informAboutAlmostExpiredLicenses(array $deletedLicenses): void
    {
        $almostExpiredLicenses = $this->tradeLicenseRepository->getLicensesExpiredInLessThan(self::INFORM_ABOUT_ALMOST_EXPIRED_IN_DAYS);

        foreach ($almostExpiredLicenses as $license) {

            //skip just deleted licenses
            if (array_key_exists($license->getId(), $deletedLicenses)) {
                continue;
            }

            // send message to user
            $this->privateMessageSender->send(
                GameEnum::USER_NOONE,
                $license->getUser()->getId(),
                sprintf(
                    "Deine Lizenz am Handelsposten %s läuft in weniger als %d Tagen ab.",
                    $license->getTradePost()->getName(),
                    $license->getExpired() - time()
                ),
                PrivateMessageFolderSpecialEnum::PM_SPECIAL_SYSTEM
            );
        }
    }

    private function deleteExpiredLicenses(): array
    {
        $licensesToDelete = $this->tradeLicenseRepository->getExpiredLicenses();

        foreach ($licensesToDelete as $license) {

            $latestLicenseInfo = $this->tradeLicenseInfoRepository->getLatestLicenseInfo($license->getTradePostId());

            // send message to user
            $this->privateMessageSender->send(
                GameEnum::USER_NOONE,
                $license->getUser()->getId(),
                sprintf(
                    "Deine Lizenz am Handelsposten %s ist abgelaufen.\nEine neue Lizenz kostet dort aktuell %d %s.",
                    $license->getTradePost()->getName(),
                    $latestLicenseInfo->getAmount(),
                    $latestLicenseInfo->getCommodity()->getName()
                ),
                PrivateMessageFolderSpecialEnum::PM_SPECIAL_SYSTEM
            );

            $this->tradeLicenseRepository->delete($license);
        }

        return $licensesToDelete;
    }
}
