<?php

declare(strict_types=1);

namespace Stu\Module\Tick\Lock;

use Mockery\MockInterface;
use Noodlehaus\ConfigInterface;
use org\bovigo\vfs\vfsStream;
use Stu\StuTestCase;

class LockManagerTest extends StuTestCase
{
    /** @var MockInterface&ConfigInterface */
    private ConfigInterface $config;

    private LockManagerInterface $lockManager;

    protected function setUp(): void
    {
        vfsStream::setup('tmpDir');
        $this->config = $this->mock(ConfigInterface::class);

        $this->lockManager = new LockManager(
            $this->config
        );
    }

    public function testFunctionality(): void
    {
        $lockType = LockEnum::LOCK_TYPE_COLONY_GROUP;

        $this->config->shouldReceive('get')
            ->with('game.colony.tick_worker', 1)
            ->andReturn("3");

        $this->config->shouldReceive('get')
            ->with('game.temp_dir')
            ->andReturn(vfsStream::url('tmpDir'));

        $this->assertFalse($this->lockManager->isLocked(42, $lockType));

        $this->lockManager->setLock(1, $lockType);
        $this->assertTrue($this->lockManager->isLocked(42, $lockType));
        $this->assertFalse($this->lockManager->isLocked(41, $lockType));
        $this->assertFalse($this->lockManager->isLocked(40, $lockType));
        $this->assertTrue($this->lockManager->isLocked(39, $lockType));
        $this->assertFalse($this->lockManager->isLocked(38, $lockType));
        $this->assertFalse($this->lockManager->isLocked(37, $lockType));

        $this->lockManager->clearLock(1, $lockType);
        $this->assertFalse($this->lockManager->isLocked(42, $lockType));
    }
}
