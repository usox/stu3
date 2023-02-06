<?php

declare(strict_types=1);

namespace Stu\Module\Index\Action\Register;

use Stu\RequestTestCase;
use Stu\RequiredRequestTestCaseTrait;

class RegisterRequestTest extends RequestTestCase
{
    use RequiredRequestTestCaseTrait;

    protected function getRequestClass(): string
    {
        return RegisterRequest::class;
    }

    public function requestVarsDataProvider(): array
    {
        return [
            ['getLoginName', 'loginname', 666, '666'],
            ['getLoginName', 'loginname', null, ''],
            ['getMobileNumber',' mobile', ' ', ''],
            ['getMobileNumber',' mobile', null, ''],
            ['getFactionId', 'factionid', '666', 666],
            ['getToken', 'token', 123, '123'],
            ['getToken', 'token', null, ''],
            ['getEmailAddress', 'email', 666, '666'],
            ['getEmailAddress', 'email', null, ''],
        ];
    }

    public function requiredRequestVarsDataProvider(): array
    {
        return [
            ['getFactionId'],
        ];
    }
}
