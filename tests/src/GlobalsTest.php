<?php

namespace Phramework\Testphase;

class GlobalsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Phramework\Testphase\Globals::regex
     */
    public function testRegexp()
    {
        Globals::regex('{{{key}}}');
        Globals::regex('{{{array[0]}}}');
        Globals::regex('{{{array[10]}}}', true);
        Globals::regex('{{{func()}}}');
        Globals::regex('{{{func(param)}}}');
        Globals::regex('{{{func(\'param\')}}}');
        Globals::regex('{{{func("param")}}}');
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
