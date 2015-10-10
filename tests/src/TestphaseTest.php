<?php

namespace Phramework\Testphase;

use \Phramework\Phramework;
use \Phramework\Models\Request;
use \Phramework\Validate\Object;
use \Phramework\Validate\String;
use \Phramework\Validate\Integer;

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
     */
    protected function setUp()
    {
        $this->object = new Testphase(
            'account',
            Phramework::METHOD_GET,
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

    public function validateSuccessProvider()
    {
        //input, expected
        return [
            [1, true]
        ];
    }

    public function validateFailureProvider()
    {
        //input
        return [
            ['100'],
        ];
    }

    /**
     * @dataProvider validateSuccessProvider
     * @covers Phramework\Testphase\Testphase::run
     */
    public function testRunSuccess($input, $expected)
    {
        $test = (new Testphase(
            'account',
            Phramework::METHOD_GET,
            $this->requestHeaders
        ))
        ->expectStatusCode(200)
        ->expectResponseHeader([
            Request::HEADER_CONTENT_TYPE => 'application/vnd.api+json;charset=utf-8'
        ])
        ->expectJSON()
        ->run();
    }

    /**
     * @covers Phramework\Testphase\Testphase::expectStatusCode
     */
    public function testExpectStatusCode()
    {
        $o = $this->object->expectStatusCode(200);

        $this->assertInstanceOf(Testphase::class, $o);
    }

    /**
     * @covers Phramework\Testphase\Testphase::expectResponseHeader
     */
    public function testExpectResponseHeader()
    {
        $o = $this->object->expectResponseHeader([
            Request::HEADER_CONTENT_TYPE => 'application/vnd.api+json;charset=utf-8'
        ]);

        $this->assertInstanceOf(Testphase::class, $o);
    }

    /**
     * @covers Phramework\Testphase\Testphase::expectJSON
     */
    public function testExpectJSON()
    {
        $o = $this->object->expectJSON();

        $this->assertInstanceOf(Testphase::class, $o);
    }

    /**
     * @covers Phramework\Testphase\Testphase::expectObject
     */
    public function testExpectObjectJSON()
    {
        $o = $this->object->expectObject(new Object());

        $this->assertInstanceOf(Testphase::class, $o);
    }

    /**
     * @covers Phramework\Testphase\Testphase::isJSON
     */
    public function testisJSON()
    {
        $this->assertTrue(
            Testphase::isJSON('{"object" : "OK"}')
        );

        $this->assertFalse(
            Testphase::isJSON('{"object" : "OK"}warning')
        );
    }



    /**
     * @dataProvider validateFailureProvider
     * @covers Phramework\Testphase\Testphase::run
     * @expectedException Exception
     */
    public function testRunFailure($input)
    {
        $test = (new Testphase(
            'account',
            Phramework::METHOD_GET,
            $this->requestHeaders
        ))
        ->expectStatusCode(440) //wrong
        ->expectResponseHeader([
            Request::HEADER_CONTENT_TYPE => 'application/vnd.api+json;charset=utf-8'
        ])
        ->expectJSON()
        ->run();
    }
}
