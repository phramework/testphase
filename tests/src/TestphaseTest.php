<?php
/**
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
use Phramework\Testphase\Report\StatusReport;
use Phramework\Testphase\Rule\BodyRule;
use Phramework\Util\Util;
use Phramework\Validate\ArrayValidator;
use \Phramework\Validate\ObjectValidator;
use \Phramework\Validate\StringValidator;
use \Phramework\Validate\IntegerValidator;

/**
 * @todo Make $requestHeaders settings
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
     * @covers ::run
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
            StatusReport::STATUS_SUCCESS,
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
            StatusReport::STATUS_SUCCESS,
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
            StatusReport::STATUS_SUCCESS,
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
            StatusReport::STATUS_SUCCESS,
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
            StatusReport::STATUS_SUCCESS,
            $test->getStatus()
        );
    }

    /**
     * @covers ::run
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
            StatusReport::STATUS_SUCCESS,
            $test->getStatus()
        );
    }

    /**
     * @covers ::expectHeader
     */
    public function testExpectResponseHeader()
    {
        $this->object->expectHeader([
            'Content-Type' => 'application/vnd.api+json;charset=utf-8'
        ]);

        $o = $this->object->expectHeader((object)[
            'Content-Type' => 'application/vnd.api+json;charset=utf-8'
        ]);

        $this->assertInstanceOf(Testphase::class, $o);
    }

    /**
     * @covers ::expectHeader
     * @expectedException Exception
     */
    public function testExpectResponseHeaderFailure1()
    {
        $this->object->expectHeader(
            'application/vnd.api+json;charset=utf-8'
        );
    }

    /**
     * @covers ::expectJSON
     */
    public function testExpectJSON()
    {
        return;
        $o = $this->object->expectJSON();

        $this->assertInstanceOf(Testphase::class, $o);
    }

    /**
     * @covers ::expectObject
     */
    public function testExpectObjectJSON()
    {
        return;
        $o = $this->object->expectObject(new ObjectValidator());

        $this->assertInstanceOf(Testphase::class, $o);
    }

    /**
     * @covers ::run
     */
    public function testRunFailure()
    {
        $test = (new Testphase(
            'book',
            'GET',
            $this->requestHeaders
        ))
        ->expectStatusCode(440) //wrong
        ->expectHeader([
            'Content-Type' => 'application/vnd.api+json;charset=utf-8'
        ])
        ->expectJSON()
        ->run();

        $this->assertSame(
            StatusReport::STATUS_SUCCESS,
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
     * @covers ::getResponse
     */
    public function testExpectStatusCode()
    {
        $testphase = (new Testphase(
            'posts/notFound',
            'GET'
        ))->expectStatusCode(404);

        $testphase->run();

        $statusCode = $testphase->getResponse()->getStatusCode();

        $this->assertInternalType('integer', $statusCode);
        $this->assertSame(404, $statusCode);

        //multiple status codes

        $testphase = (new Testphase(
            'posts/notFound',
            'GET'
        ))->expectStatusCode([400, 404]);

        $testphase->run();

        $statusCode = $testphase->getResponse()->getStatusCode();

        $this->assertInternalType('integer', $statusCode);
        $this->assertSame(404, $statusCode);
    }

    /**
     * @covers ::getResponse
     */
    public function testGetResponseStatusCode()
    {
        $testphase = (new Testphase(
            'posts/notFound',
            'GET'
        ))->expectStatusCode(404);

        $testphase->run();

        $statusCode = $testphase->getResponse()->getStatusCode();

        $this->assertInternalType('integer', $statusCode);
        $this->assertSame(404, $statusCode);
    }

    /**
     * @covers ::getResponse
     */
    public function testGetResponseBody()
    {
        $testphase = (new Testphase(
            'posts/notFound',
            'GET'
        ))->expectStatusCode(404);

        $testphase->run();

        $responseBody = $testphase->getResponse()->getResponse()->getBody()->__toString();

        $this->assertInternalType('string', $responseBody);

        $this->assertTrue(Util::isJSON($responseBody));
    }

    /**
     * @covers ::getResponse
     */
    public function testGetResponseHeaders()
    {
        $testphase = (new Testphase(
            'posts/notFound',
            'GET'
        ))->expectStatusCode(404);

        $testphase->run();

        $responseHeaders = $testphase->getResponse()->getHeaders();

        $this->assertInternalType('array', $responseHeaders);

        $this->assertArrayHasKey('Content-Type', $responseHeaders);

        $this->assertStringStartsWith(
            'application/json',
            $responseHeaders['Content-Type'][0]
        );
    }
}
