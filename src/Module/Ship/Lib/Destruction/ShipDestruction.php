<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Lib\Destruction;

use Stu\Lib\Information\InformationInterface;
use Stu\Module\Ship\Lib\Destruction\Handler\ShipDestructionHandlerInterface;
use Stu\Module\Ship\Lib\ShipWrapperInterface;

final class ShipDestruction implements ShipDestructionInterface
{

    /**
     * @param array<ShipDestructionHandlerInterface> $destructionHandlers
     */
    public function __construct(
        private array $destructionHandlers
    ) {
    }

    public function destroy(
        ?ShipDestroyerInterface $destroyer,
        ShipWrapperInterface $destroyedShipWrapper,
        ShipDestructionCauseEnum $cause,
        InformationInterface $informations
    ): void {

        array_walk(
            $this->destructionHandlers,
            function (ShipDestructionHandlerInterface $handler) use (
                $destroyer,
                $destroyedShipWrapper,
                $cause,
                $informations
            ): void {
                $handler->handleShipDestruction(
                    $destroyer,
                    $destroyedShipWrapper,
                    $cause,
                    $informations
                );
            }
        );
    }
}
