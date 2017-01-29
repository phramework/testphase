<?php
/*
 * Copyright 2015 - 2016 Xenofon Spafaridis
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Phramework\Testphase;

use \Phramework\Phramework;
use Phramework\Testphase\Report\TestphaseReport;
use Phramework\Testphase\Rule\BodyRule;
use Phramework\Testphase\Rule\HeaderRule;
use Phramework\Testphase\Rule\Rule;
use Phramework\Testphase\Rule\StatusCodeRule;
use Phramework\Util\Util;
use Phramework\Validate\ArrayValidator;
use \Phramework\Validate\ObjectValidator;
use \Phramework\Validate\StringValidator;
use \Phramework\Validate\IntegerValidator;
use Phramework\Validate\UnsignedIntegerValidator;

/**
 * @coversDefaultClass Phramework\Testphase\Testphase
 */
class TestphaseTest extends \PHPUnit_Framework_TestCase
{
    private $requestHeaders = [
        'Authorization: Basic bm9ocG9uZXhAZ21haWwuY29tOjEyMzQ1Njc4eFg=',
        'Content-Type: application/vnd.api+json',
        'Accept: application/vnd.api+json'
    ];

    /**
     * @var Testphase
     */
    private $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     * @todo update base
     */
    protected function setUp()
    {
        $base = 'http://jsonplaceholder.typicode.com/';

        Testphase::setBase($base);

        $this->object = new Testphase(
            'book',
            'GET',
            $this->requestHeaders
        );
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers ::getVersion
     */
    public function testGetVersion()
    {
        $version = Testphase::getVersion();

        $this->assertInternalType('string', $version);
        /*$this->assertRegExp(
            '/^1\.[1-9]*[0-9]?\.[1-9]*[0-9]?(:?\-[a-zA-Z0-9]+)?$/',
            $version,
            'Validates againts 1.x.x versions'
        );*/
    }

    /**
     * @covers ::handleResponse
     */
    public function testRunSuccess()
    {
        $test = (new Testphase(
            'posts/notFound',
            'GET',
            $this->requestHeaders
        ))
            ->expectStatusCode(404)
            ->expectJSON()
            ->run();

        $this->assertSame(
            TestphaseReport::STATUS_SUCCESS,
            $test->getStatus()
        );

        $test = (new Testphase(
            'posts/notFound',
            'POST',
            $this->requestHeaders,
            '{}'
        ))
            ->expectStatusCode(404)
            ->run();

        $this->assertSame(
            TestphaseReport::STATUS_SUCCESS,
            $test->getStatus()
        );

        $test = (new Testphase(
            'posts/notFound',
            'PATCH',
            $this->requestHeaders
        ))
            ->expectStatusCode(404)
            ->run();

        $this->assertSame(
            TestphaseReport::STATUS_SUCCESS,
            $test->getStatus()
        );

        $test = (new Testphase(
            'posts/notFound',
            'PUT',
            $this->requestHeaders
        ))
            ->expectStatusCode(404)
            ->run();

        $this->assertSame(
            TestphaseReport::STATUS_SUCCESS,
            $test->getStatus()
        );

        $test = (new Testphase(
            'posts/notFound',
            'DELETE',
            $this->requestHeaders
        ))
            ->expectStatusCode(404)
            ->run();

        $this->assertSame(
            TestphaseReport::STATUS_SUCCESS,
            $test->getStatus()
        );
    }

    /**
     * @covers ::handleResponse
     */
    public function testRule()
    {
        $test = (new Testphase(
            'posts'
        ))
            ->expectRule(new BodyRule(
                '',
                new ArrayValidator(
                    0,
                    null,
                    new ObjectValidator()
                )
            ))
            ->expectRule(new BodyRule(
                '/0',
                new ObjectValidator()
            ))
            ->expectRule(new BodyRule(
                '/0/id',
                (new IntegerValidator())
                    ->setEnum([1])
            ))
            ->run();

        $this->assertCount(
            3,
            $test->getRuleReport()
        );

        foreach ($test->getRuleReport() as $r) {
            $this->assertTrue($r->getStatus());
        }

        $this->assertSame(
            TestphaseReport::STATUS_SUCCESS,
            $test->getStatus()
        );
    }

    /**
     * @covers ::handleResponse
     */
    public function testResponseCodeFailure()
    {
        $test = (new Testphase(
            'book',
            'GET',
            $this->requestHeaders
        ))
            ->expectRule(
                StatusCodeRule::fromEnum([444]) //to produce failure
            )
            ->run();

        $this->assertCount(
            1,
            $test->getRuleReport()
        );

        $this->assertSame(
            TestphaseReport::STATUS_FAILURE,
            $test->getStatus()
        );
    }

    /**
     * @covers ::setBase
     */
    public function testSetBase()
    {
        $base = 'http://jsonplaceholder.typicode.com/';

        Testphase::setBase($base);

        return $base;
    }

    /**
     * @covers ::getBase
     * @depends testSetBase
     */
    public function testGetBase($base)
    {
        $this->assertSame(
            $base,
            Testphase::getBase()
        );
    }

    /**
     * @covers ::handleResponse
     */
    public function testExpectStatusCode()
    {
        $testphase = (new Testphase(
            'posts/notFound',
            'GET'
        ))->expectStatusCode(404);

        $test = $testphase->run();

        $statusCode = $test->getResponse()->getStatusCode();

        $this->assertInternalType('integer', $statusCode);
        $this->assertSame(404, $statusCode);

        //multiple status codes

        $testphase = (new Testphase(
            'posts/notFound',
            'GET'
        ))->expectStatusCode([400, 404]);

        $test = $testphase->run();

        $statusCode = $test->getResponse()->getStatusCode();

        $this->assertInternalType('integer', $statusCode);
        $this->assertSame(404, $statusCode);
    }

    /**
     * @covers ::handleResponse
     */
    public function testGetResponseStatusCode()
    {
        $test = (new Testphase(
            'posts/1',
            'GET'
        ))
            ->expectRule(
                StatusCodeRule::fromEnum([200]) //to produce failure
            )
            ->run();

        $this->assertCount(
            1,
            $test->getRuleReport()
        );

        $this->assertSame(
            TestphaseReport::STATUS_SUCCESS,
            $test->getStatus()
        );

        $statusCode = $test->getResponse()->getStatusCode();

        $this->assertInternalType('integer', $statusCode);
        $this->assertSame(200, $statusCode);
    }

    /**
     * @covers ::handleResponse
     */
    public function testGetResponseBody()
    {
        $testphase = (new Testphase(
            'posts/notFound',
            'GET'
        ))->expectStatusCode(404);

        $test = $testphase->run();

        $responseBody = $test->getResponse()->getResponse()->getBody()->__toString();

        $this->assertInternalType('string', $responseBody);

        $this->assertTrue(Util::isJSON($responseBody));
    }

    /**
     * @covers ::handleResponse
     */
    public function testHeaderRuleSuccess()
    {
        $test = (new Testphase(
            'posts/notFound',
            'GET'
        ))
            ->expectRule(
                new HeaderRule(
                    'Date/0',
                    new StringValidator(1, 30),
                    'Expect date header to be set'
                )
            )
            ->run();

        $this->assertCount(
            1,
            $test->getRuleReport()
        );

        $this->assertSame(
            TestphaseReport::STATUS_SUCCESS,
            $test->getStatus()
        );

        $responseHeaders = $test->getResponse()->getHeaders();

        $this->assertInternalType('array', $responseHeaders);

        $this->assertArrayHasKey('Date', $responseHeaders);

        $this->assertInternalType('string', $responseHeaders['Date'][0]);
    }

    /**
     * @covers ::handleResponse
     */
    public function testHeaderRuleFailure()
    {
        $test = (new Testphase(
            'posts/notFound',
            'GET'
        ))
            ->expectRule(
                new HeaderRule(
                    'Date/0',
                    (new StringValidator())//will cause failure
                    ->setEnum(['unexpected'])
                )
            )
            ->run();

        $this->assertCount(
            1,
            $test->getRuleReport(),
            'Expect only 1 rule to be executed'
        );

        $this->assertSame(
            TestphaseReport::STATUS_FAILURE,
            $test->getStatus(),
            'Expect failure'
        );
    }

    /**
     * @covers ::handleResponse
     */
    public function testHeaderRuleUndefined()
    {
        $test = (new Testphase(
            'posts/notFound',
            'GET'
        ))
            ->expectRule(
                new HeaderRule(
                    'Unexpected',
                    new StringValidator()
                )
            )
            ->run();

        $this->assertCount(
            1,
            $test->getRuleReport(),
            'Expect only 1 rule to be executed'
        );

        $this->assertSame(
            TestphaseReport::STATUS_FAILURE,
            $test->getStatus(),
            'Expect failure'
        );
    }

    /**
     * @covers ::handleResponse
     */
    public function testTimeoutRuleFailure()
    {
        $test = (new Testphase(
            'posts',
            'GET'
        ))
            ->expectRule(
                new Rule(
                    Rule::ROOT_TIMEOUT,
                    (new UnsignedIntegerValidator())
                        ->setEnum([9999999999])
                )
            )->run();

        $this->assertCount(
            1,
            $test->getRuleReport()
        );

        $this->assertSame(
            TestphaseReport::STATUS_FAILURE,
            $test->getStatus(),
            'Expect failure'
        );
    }
}
