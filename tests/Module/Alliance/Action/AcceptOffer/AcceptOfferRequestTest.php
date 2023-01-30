<?php

declare(strict_types=1);

namespace Stu\Module\Alliance\Action\AcceptOffer;

use Stu\RequestTestCase;
use Stu\RequiredRequestTestCaseTrait;

/**
 * @extends RequestTestCase<AcceptOfferRequest>
 */
class AcceptOfferRequestTest extends RequestTestCase
{
    use RequiredRequestTestCaseTrait;

    protected function getRequestClass(): string
    {
        return AcceptOfferRequest::class;
    }

    public function requestVarsDataProvider(): array
    {
        return [
            ['getRelationId', 'al', '666', 666],
        ];
    }

    public function requiredRequestVarsDataProvider(): array
    {
        return [
            ['getRelationId'],
        ];
    }
}
