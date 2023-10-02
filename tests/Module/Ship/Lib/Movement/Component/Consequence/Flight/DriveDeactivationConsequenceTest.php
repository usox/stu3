<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Lib\Movement\Component\Consequence\Flight;

use Mockery;
use Mockery\MockInterface;
use Stu\Component\Ship\System\ShipSystemManagerInterface;
use Stu\Component\Ship\System\ShipSystemTypeEnum;
use Stu\Module\Ship\Lib\Message\MessageCollectionInterface;
use Stu\Module\Ship\Lib\Message\MessageInterface;
use Stu\Module\Ship\Lib\Movement\Component\Consequence\FlightConsequenceInterface;
use Stu\Module\Ship\Lib\Movement\Route\FlightRouteInterface;
use Stu\Module\Ship\Lib\ShipWrapperInterface;
use Stu\Orm\Entity\ShipInterface;
use Stu\StuTestCase;

class DriveDeactivationConsequenceTest extends StuTestCase
{
    /** @var MockInterface&ShipSystemManagerInterface */
    private MockInterface $shipSystemManager;

    private FlightConsequenceInterface $subject;

    /** @var MockInterface&ShipInterface */
    private MockInterface $ship;

    /** @var MockInterface&ShipWrapperInterface */
    private MockInterface $wrapper;

    /** @var MockInterface&FlightRouteInterface */
    private MockInterface $flightRoute;

    protected function setUp(): void
    {
        $this->shipSystemManager = $this->mock(ShipSystemManagerInterface::class);

        $this->ship = $this->mock(ShipInterface::class);
        $this->wrapper = $this->mock(ShipWrapperInterface::class);
        $this->flightRoute = $this->mock(FlightRouteInterface::class);

        $this->wrapper->shouldReceive('get')
            ->zeroOrMoreTimes()
            ->andReturn($this->ship);

        $this->subject = new DriveDeactivationConsequence(
            $this->shipSystemManager
        );
    }

    public function testTriggerExpectNothingWhenShipDestroyed(): void
    {
        $messages = $this->mock(MessageCollectionInterface::class);

        $this->ship->shouldReceive('isDestroyed')
            ->withNoArgs()
            ->once()
            ->andReturn(true);

        $this->subject->trigger(
            $this->wrapper,
            $this->flightRoute,
            $messages
        );
    }

    public function testTriggerExpectNothingWhenShipTractored(): void
    {
        $messages = $this->mock(MessageCollectionInterface::class);

        $this->ship->shouldReceive('isDestroyed')
            ->withNoArgs()
            ->once()
            ->andReturn(false);
        $this->ship->shouldReceive('isTractored')
            ->withNoArgs()
            ->once()
            ->andReturn(true);

        $this->subject->trigger(
            $this->wrapper,
            $this->flightRoute,
            $messages
        );
    }

    public static function provideTriggerData()
    {
        return [
            [true, false, false, ShipSystemTypeEnum::SYSTEM_WARPDRIVE],
            [true, false, true, ShipSystemTypeEnum::SYSTEM_WARPDRIVE],
            [false, true, false, ShipSystemTypeEnum::SYSTEM_IMPULSEDRIVE],
            [false, true, true, ShipSystemTypeEnum::SYSTEM_IMPULSEDRIVE],
        ];
    }

    /**
     * @dataProvider provideTriggerData
     */
    public function testTrigger(
        bool $isImpulsNeeded,
        bool $isWarpdriveNeeded,
        bool $hasShipSystem,
        int $expectedSystemId
    ): void {
        $messages = $this->mock(MessageCollectionInterface::class);

        $this->ship->shouldReceive('isDestroyed')
            ->withNoArgs()
            ->once()
            ->andReturn(false);
        $this->ship->shouldReceive('isTractored')
            ->withNoArgs()
            ->once()
            ->andReturn(false);
        $this->ship->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->andReturn(123);
        $this->ship->shouldReceive('getName')
            ->withNoArgs()
            ->andReturn('SHIP');
        $this->ship->shouldReceive('hasShipSystem')
            ->with($expectedSystemId)
            ->andReturn($hasShipSystem);

        $this->flightRoute->shouldReceive('isImpulseDriveNeeded')
            ->withNoArgs()
            ->once()
            ->andReturn($isImpulsNeeded);
        $this->flightRoute->shouldReceive('isWarpDriveNeeded')
            ->withNoArgs()
            ->once()
            ->andReturn($isWarpdriveNeeded);

        if ($hasShipSystem) {
            $this->shipSystemManager->shouldReceive('deactivate')
                ->with($this->wrapper, $expectedSystemId, true)
                ->once();
        }

        if ($hasShipSystem) {
            $message = null;
            $messages->shouldReceive('add')
                ->with(Mockery::on(function (MessageInterface $m) use (&$message) {

                    $message = $m;
                    return true;
                }));
        }

        $this->subject->trigger(
            $this->wrapper,
            $this->flightRoute,
            $messages
        );

        if ($hasShipSystem) {
            $this->assertEquals(
                [sprintf(
                    'Die SHIP deaktiviert %s %s',
                    $expectedSystemId === ShipSystemTypeEnum::SYSTEM_TRANSWARP_COIL ? 'die' : 'den',
                    ShipSystemTypeEnum::getDescription($expectedSystemId)
                )],
                $message->getMessage()
            );
        }
    }
}
