<?php

namespace Phramework\Testphase;

class GlobalsTest extends \PHPUnit_Framework_TestCase
{
    public function regexpProvider()
    {
        return [
            [
                'key',
                (object) [
                    'mode'  => Globals::KEY_VARIABLE,
                    'key'   => 'key',
                ]
            ],
            [
                'array[0]',
                (object) [
                    'mode'  => Globals::KEY_ARRAY,
                    'key'   => 'array',
                    'index' => 0
                ]
            ],
            [
                'func()',
                (object) [
                    'mode'  => Globals::KEY_FUNCTION,
                    'key'   => 'func'
                ]
            ]
        ];
    }
    /**
     * @dataProvider regexpProvider
     * @covers Phramework\Testphase\Globals::regex
     */
    public function testRegexp($input, $expected)
    {
        $return = Globals::regex($input);

        $this->assertInternalType('object', $return);


        $this->assertEquals(
            $expected,
            $return
        );

        //Globals::regex('{{{array[10]}}}', true);
        //Globals::regex('{{{func()}}}');
        //Globals::regex('{{{func(param)}}}');
        //Globals::regex('{{{func(\'param\')}}}');
        //Globals::regex('{{{func("param")}}}');
    }

    /**
     * @covers Phramework\Testphase\Globals::get
     *
     */
    public function testGet()
    {
        return;
        $value = Globals::get('rand-string()');
        var_dump($value);
        $value = Globals::get('rand-string()');
        var_dump($value);

        $value = Globals::get('array');
        var_dump($value);

        $value = Globals::get('array[1]');
        var_dump($value);

        $value = Globals::get('rand-integer()');
        var_dump($value);

        $value = Globals::get('rand-integer(2)');
        var_dump($value);

        $value = Globals::get('rand-integer(1)');
        var_dump($value);
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
        $return = Globals::get('rand-string(6)');
        
        $this->assertInternalType('string', $return);
        $this->assertSame(6, strlen($return));

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
        $this->assertSame(0, Globals::get('rand-integer(0)'));

        $this->assertNotEquals(
            Globals::get('rand-integer()'),
            Globals::get('rand-integer()'),
            'Expect different value for each call to random'
        );
    }
}
