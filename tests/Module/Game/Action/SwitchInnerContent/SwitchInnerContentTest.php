<?php

declare(strict_types=1);

namespace Stu\Module\Game\Action\SwitchInnerContent;

use Mockery\MockInterface;
use request;
use Stu\Component\Game\ModuleViewEnum;
use Stu\Exception\InvalidParamException;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Game\View\ShowInnerContent\ShowInnerContent;
use Stu\StuTestCase;
use ValueError;

class SwitchInnerContentTest extends StuTestCase
{
    /** @var MockInterface&GameControllerInterface  */
    private MockInterface $game;

    private ActionControllerInterface $subject;

    protected function setUp(): void
    {
        $this->game = $this->mock(GameControllerInterface::class);

        $this->subject = new SwitchInnerContent();
    }

    public function testHandleExpectExceptionWhenNoViewRequestparam(): void
    {
        static::expectExceptionMessage('request parameter "view" does not exist');
        static::expectException(InvalidParamException::class);

        $this->subject->handle($this->game);
    }

    public function testHandleExpectExceptionWhenViewUnknown(): void
    {
        static::expectExceptionMessage('"foobar" is not a valid backing value for enum Stu\Component\Game\ModuleViewEnum');
        static::expectException(ValueError::class);

        request::setMockVars(['view' => 'foobar']);

        $this->subject->handle($this->game);
    }

    public function testHandleExpectCorrectViewAndContext(): void
    {
        request::setMockVars(['view' => ModuleViewEnum::MAINDESK->value]);

        $this->game->shouldReceive('setView')
            ->with(ShowInnerContent::VIEW_IDENTIFIER, ['VIEW' => ModuleViewEnum::MAINDESK])
            ->once();

        $this->subject->handle($this->game);
    }
}
