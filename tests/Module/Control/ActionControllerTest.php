<?php

declare(strict_types=1);

namespace Stu\Module\Control;

use Mockery\MockInterface;
use Stu\StuTestCase;

abstract class ActionControllerTest extends StuTestCase
{
    /** @var MockInterface&GameControllerInterface */
    protected MockInterface $game;

    protected function setUp(): void
    {
        $this->game = $this->mock(GameControllerInterface::class);
    }
}
