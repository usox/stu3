<?php

declare(strict_types=1);

namespace Stu\Lib\Map\VisualPanel\Layer\DataProvider\Subspace;

use Crunz\Exception\NotImplementedException;
use Stu\Lib\Map\VisualPanel\PanelBoundaries;
use Stu\Orm\Repository\MapRepositoryInterface;
use Stu\Orm\Repository\StarSystemMapRepositoryInterface;

final class ShipSubspaceDataProvider extends AbstractSubspaceDataProvider
{
    private int $shipId;

    public function __construct( #
        int $shipId,
        MapRepositoryInterface $mapRepository,
        StarSystemMapRepositoryInterface $starSystemMapRepository
    ) {
        parent::__construct($mapRepository, $starSystemMapRepository);
        $this->shipId = $shipId;
    }

    protected function provideDataForMap(PanelBoundaries $boundaries): array
    {
        return $this->mapRepository->getShipSubspaceLayerData($boundaries, $this->shipId, $this->createResultSetMapping());
    }

    protected function provideDataForSystemMap(PanelBoundaries $boundaries): array
    {
        throw new NotImplementedException('this is not possible');
    }
}
