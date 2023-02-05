<?php

declare(strict_types=1);

namespace Stu\Module\PlayerProfile\View\Overview;

use Stu\RequestTestCase;
use Stu\RequiredRequestTestCaseTrait;

class OverviewRequestTest extends RequestTestCase
{
    use RequiredRequestTestCaseTrait;

    protected function getRequestClass(): string
    {
        return OverviewRequest::class;
    }

    public function requestVarsDataProvider(): array
    {
        return [
            ['getPlayerId', 'uid', '666', 666],
        ];
    }

    public function requiredRequestVarsDataProvider(): array
    {
        return [
            ['getPlayerId'],
        ];
    }
}
