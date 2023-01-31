<?php

declare(strict_types=1);

namespace Stu\Module\Alliance\Action\SetTopicSticky;

use Stu\RequestTestCase;
use Stu\RequiredRequestTestCaseTrait;

class SetTopicStickyRequestTest extends RequestTestCase
{
    use RequiredRequestTestCaseTrait;

    protected function getRequestClass(): string
    {
        return SetTopicStickyRequest::class;
    }

    public function requestVarsDataProvider(): array
    {
        return [
            ['getTopicId', 'tid', '666', 666],
        ];
    }

    public function requiredRequestVarsDataProvider(): array
    {
        return [
            ['getTopicId'],
        ];
    }
}
