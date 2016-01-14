<?php

namespace Phramework\Testphase;

class ExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function parseProvider()
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
            ],
            [
                'func(0)',
                (object) [
                    'mode'         => Globals::KEY_FUNCTION,
                    'key'          => 'func',
                    'parameters'   => [0]
                ]
            ],
            [
                'func(1)',
                (object) [
                    'mode'         => Globals::KEY_FUNCTION,
                    'key'          => 'func',
                    'parameters'   => [1]
                ]
            ],
            [
                'func(abc)',
                (object) [
                    'mode'         => Globals::KEY_FUNCTION,
                    'key'          => 'func',
                    'parameters'   => ['abc']
                ]
            ],
            [
                'func("abc")',
                (object) [
                    'mode'         => Globals::KEY_FUNCTION,
                    'key'          => 'func',
                    'parameters'   => ['"abc"']
                ]
            ],
            [
                'func(\'abc\')',
                (object) [
                    'mode'         => Globals::KEY_FUNCTION,
                    'key'          => 'func',
                    'parameters'   => ['\'abc\'']
                ]
            ]
        ];
    }

    /**
     * @dataProvider parseProvider
     * @covers Phramework\Testphase\Expression::parse
     */
    public function testParse($input, $expected)
    {
        $return = Expression::parse($input);

        $this->assertInternalType('object', $return);

        $this->assertEquals(
            $expected,
            $return
        );
    }
}
