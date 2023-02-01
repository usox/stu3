<?php

declare(strict_types=1);

namespace Stu\Module\Control\Render\Fragments;

use Mockery\MockInterface;
use Stu\Module\Tal\StatusBarColorEnum;
use Stu\Module\Tal\TalComponentFactoryInterface;
use Stu\Module\Tal\TalPageInterface;
use Stu\Module\Tal\TalStatusBarInterface;
use Stu\Orm\Entity\ResearchedInterface;
use Stu\Orm\Entity\UserInterface;
use Stu\Orm\Repository\ResearchedRepositoryInterface;
use Stu\StuTestCase;

class ResearchFragmentTest extends StuTestCase
{
    private MockInterface $researchedRepository;

    private MockInterface $talComponentFactory;

    private ResearchFragment $subject;

    protected function setUp(): void
    {
        $this->researchedRepository = $this->mock(ResearchedRepositoryInterface::class);
        $this->talComponentFactory = $this->mock(TalComponentFactoryInterface::class);

        $this->subject = new ResearchFragment(
            $this->researchedRepository,
            $this->talComponentFactory
        );
    }

    public function testRenderRendersWithoutCurrentResearch(): void
    {
        $user = $this->mock(UserInterface::class);
        $talPage = $this->mock(TalPageInterface::class);

        $this->researchedRepository->shouldReceive('getCurrentResearch')
            ->with($user)
            ->once()
            ->andReturnNull();

        $talPage->shouldReceive('setVar')
            ->with('CURRENT_RESEARCH', null)
            ->once();
        $talPage->shouldReceive('setVar')
            ->with('CURRENT_RESEARCH_STATUS', '')
            ->once();

        $this->subject->render($user, $talPage);
    }

    public function testRenderRendersWithResearch(): void
    {
        $user = $this->mock(UserInterface::class);
        $talPage = $this->mock(TalPageInterface::class);
        $researchReference = $this->mock(ResearchedInterface::class);
        $talStatusBar = $this->mock(TalStatusBarInterface::class);

        $points = 666;
        $alreadyResearchedPoints = 42;

        $this->researchedRepository->shouldReceive('getCurrentResearch')
            ->with($user)
            ->once()
            ->andReturn($researchReference);

        $researchReference->shouldReceive('getResearch->getPoints')
            ->withNoArgs()
            ->once()
            ->andReturn($points);
        $researchReference->shouldReceive('getActive')
            ->withNoArgs()
            ->once()
            ->andReturn($alreadyResearchedPoints);

        $this->talComponentFactory->shouldReceive('createTalStatusBar')
            ->withNoArgs()
            ->once()
            ->andReturn($talStatusBar);

        $talStatusBar->shouldReceive('setColor')
            ->with(StatusBarColorEnum::STATUSBAR_BLUE)
            ->once()
            ->andReturnSelf();
        $talStatusBar->shouldReceive('setLabel')
            ->with('Forschung')
            ->once()
            ->andReturnSelf();
        $talStatusBar->shouldReceive('setMaxValue')
            ->with($points)
            ->once()
            ->andReturnSelf();
        $talStatusBar->shouldReceive('setValue')
            ->with($points - $alreadyResearchedPoints)
            ->once()
            ->andReturnSelf();
        $talStatusBar->shouldReceive('setSizeModifier')
            ->with(2)
            ->once()
            ->andReturnSelf();

        $talPage->shouldReceive('setVar')
            ->with('CURRENT_RESEARCH', $researchReference)
            ->once();
        $talPage->shouldReceive('setVar')
            ->with('CURRENT_RESEARCH_STATUS', $talStatusBar)
            ->once();

        $this->subject->render($user, $talPage);
    }
}
