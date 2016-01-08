<?php

namespace Phramework\Testphase;

use \Phramework\Phramework;
use \Phramework\Validate\ObjectValidator;
use \Phramework\Validate\StringValidator;
use \Phramework\Validate\IntegerValidator;

/**
 * @todo Make $requestHeaders settings
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

        Testphase::setBase('http://localhost:8000/');

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
     * @covers Phramework\Testphase\Testphase::run
     */
    public function testRunSuccess()
    {
        $test = (new Testphase(
            'bookz',
            'GET',
            $this->requestHeaders
        ))
        ->expectStatusCode(404)
        ->expectResponseHeader([
            'Content-Type' => 'application/json;charset=utf-8'
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
            'Content-Type' => 'application/vnd.api+json;charset=utf-8'
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
        $o = $this->object->expectObject(new ObjectValidator());

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
     * @covers Phramework\Testphase\Testphase::run
     * @expectedException Exception
     */
    public function testRunFailure()
    {
        $test = (new Testphase(
            'book',
            Phramework::METHOD_GET,
            $this->requestHeaders
        ))
        ->expectStatusCode(440) //wrong
        ->expectResponseHeader([
            'Content-Type' => 'application/vnd.api+json;charset=utf-8'
        ])
        ->expectJSON()
        ->run();
    }
}
