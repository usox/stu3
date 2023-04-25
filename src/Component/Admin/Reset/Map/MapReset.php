<?php

declare(strict_types=1);

namespace Stu\Component\Admin\Reset\Map;

use Doctrine\ORM\EntityManagerInterface;
use Stu\Orm\Repository\AstroEntryRepositoryInterface;
use Stu\Orm\Repository\ColonyScanRepositoryInterface;
use Stu\Orm\Repository\FlightSignatureRepositoryInterface;
use Stu\Orm\Repository\TachyonScanRepositoryInterface;
use Stu\Orm\Repository\UserLayerRepositoryInterface;
use Stu\Orm\Repository\UserMapRepositoryInterface;

final class MapReset implements MapResetInterface
{
    private FlightSignatureRepositoryInterface $flightSignatureRepository;

    private UserMapRepositoryInterface $userMapRepository;

    private AstroEntryRepositoryInterface $astroEntryRepository;

    private ColonyScanRepositoryInterface $colonyScanRepository;

    private TachyonScanRepositoryInterface $tachyonScanRepository;

    private UserLayerRepositoryInterface $userLayerRepository;

    private EntityManagerInterface $entityManager;

    public function __construct(
        FlightSignatureRepositoryInterface $flightSignatureRepository,
        UserMapRepositoryInterface $userMapRepository,
        AstroEntryRepositoryInterface $astroEntryRepository,
        ColonyScanRepositoryInterface $colonyScanRepository,
        TachyonScanRepositoryInterface $tachyonScanRepository,
        UserLayerRepositoryInterface $userLayerRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->flightSignatureRepository = $flightSignatureRepository;
        $this->userMapRepository = $userMapRepository;
        $this->astroEntryRepository = $astroEntryRepository;
        $this->colonyScanRepository = $colonyScanRepository;
        $this->tachyonScanRepository = $tachyonScanRepository;
        $this->userLayerRepository = $userLayerRepository;
        $this->entityManager = $entityManager;
    }

    public function deleteAllFlightSignatures(): void
    {
        echo "  - delete all flight signatures\n";

        $this->flightSignatureRepository->truncateAllSignatures();

        $this->entityManager->flush();
    }

    public function deleteAllUserMaps(): void
    {
        echo "  - delete all user maps\n";

        $this->userMapRepository->truncateAllUserMaps();

        $this->entityManager->flush();
    }

    public function deleteAllAstroEntries(): void
    {
        echo "  - delete all astro entries\n";

        $this->astroEntryRepository->truncateAllAstroEntries();

        $this->entityManager->flush();
    }

    public function deleteAllColonyScans(): void
    {
        echo "  - delete all colony scans\n";

        $this->colonyScanRepository->truncateAllColonyScans();

        $this->entityManager->flush();
    }

    public function deleteAllTachyonScans(): void
    {
        echo "  - delete all tachyon scans\n";

        $this->tachyonScanRepository->deleteOldScans(-1);

        $this->entityManager->flush();
    }

    public function deleteAllUserLayers(): void
    {
        echo "  - delete all user layers\n";

        $this->userLayerRepository->truncateAllUserLayer();

        $this->entityManager->flush();
    }
}
