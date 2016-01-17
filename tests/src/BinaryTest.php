<?php

namespace Phramework\Testphase;

class BinaryTest extends \PHPUnit_Framework_TestCase
{
    protected $binary;

    public function setUp()
    {
        $argv = [
            __FILE__,
            '-d',
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests'
        ];

        $this->binary = new Binary($argv);
    }

    /**
     * @covers Phramework\Testphase\Binary::__construct
     */
    public function testConstruct()
    {
        $argv = [
            __FILE__,
            '-d',
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests'
        ];

        $binary = new Binary($argv);
    }

    /**
     * @covers Phramework\Testphase\Binary::invoke
     */
    public function testInvoke()
    {
        $this->binary->invoke();
    }
}
