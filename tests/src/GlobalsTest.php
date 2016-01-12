<?php

namespace Phramework\Testphase;

class GlobalsTest extends \PHPUnit_Framework_TestCase
{
    public function regexpProvider()
    {
        return [
            [
                '{{{key}}}',
                (object) [
                    'mode'  => Globals::KEY_VARIABLE,
                    'key'   => 'key',
                ]
            ],
            [
                '{{{array[0]}}}',
                (object) [
                    'mode'  => Globals::KEY_ARRAY,
                    'key'   => 'array',
                    'index' => 0
                ]
            ],
            [
                '{{{func()}}}',
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
        $value = Globals::get('{{{rand.string()}}}');
        var_dump($value);
        $value = Globals::get('{{{rand.string()}}}');
        var_dump($value);

        $value = Globals::get('{{{array}}}');
        var_dump($value);

        $value = Globals::get('{{{array[1]}}}');
        var_dump($value);
    }
}
