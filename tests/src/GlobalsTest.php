<?php

namespace Phramework\Testphase;

class GlobalsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Phramework\Testphase\Globals::get
     *
     */
    public function testGet()
    {
    }

    /**
     * @covers Phramework\Testphase\Globals::set
     *
     */
    public function testSetArray()
    {
        $array = [1, 3, 5, 7, 11, 13];

        Globals::set('array', $array);

        $this->assertTrue(Globals::exists('array'));

        return $array;
    }

    /**
     * @depends testSetArray
     * @covers Phramework\Testphase\Globals::get
     */
    public function testGetArray($array)
    {
        //Get array
        $return = Globals::get('array');
        $this->assertInternalType('array', $return);

        $this->assertEquals($array, $return);

        //Get element
        $return = Globals::get('array[0]');
        $this->assertInternalType('integer', $return);

        $this->assertSame($array[0], $return);

        //Get element
        $return = Globals::get('array[3]');
        $this->assertInternalType('integer', $return);

        $this->assertSame($array[3], $return);
    }

    /**
     * @covers Phramework\Testphase\Globals::get
     */
    public function testGetRandString()
    {
        $return = Globals::get('rand-string');

        $this->assertInternalType(
            'callable',
            $return,
            'Expect rand-string() to return a function'
        );

        $return = Globals::get('rand-string(6)');

        $this->assertInternalType('string', $return);
        $this->assertSame(
            6,
            strlen($return),
            'Expect same length as given length parameter'
        );

        $this->assertSame(
            5,
            strlen(Globals::get('rand-string(5)')),
            'Expect same length as given length parameter'
        );

        $this->assertNotEquals(
            Globals::get('rand-string()'),
            Globals::get('rand-string()'),
            'Expect different value for each call to random'
        );
    }

    /**
     * @covers Phramework\Testphase\Globals::get
     *
     */
    public function testGetRandInteger()
    {
        $return = Globals::get('rand-integer');

        $this->assertInternalType(
            'callable',
            $return,
            'Expect rand-integer to return a function'
        );

        $return = Globals::get('rand-integer()');
        $this->assertInternalType(
            'integer',
            $return,
            'Expect rand-integer() to return an integer'
        );

        $this->assertSame(0, Globals::get('rand-integer(0)'));

        $this->assertNotEquals(
            Globals::get('rand-integer()'),
            Globals::get('rand-integer()'),
            'Expect different value for each call to random'
        );
    }
}
