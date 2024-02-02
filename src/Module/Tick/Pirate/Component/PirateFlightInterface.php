<?php

namespace Stu\Module\Tick\Pirate\Component;

use Stu\Module\Ship\Lib\Movement\Route\FlightRouteInterface;
use Stu\Module\Ship\Lib\ShipWrapperInterface;

interface PirateFlightInterface
{
    public function movePirate(
        ShipWrapperInterface $wrapper,
        FlightRouteInterface $flightRoute
    ): void;
}
