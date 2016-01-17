<?php

namespace Phramework\Testphase;

class ExpressionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Phramework\Testphase\Expression::getExpression
     */
    public function testGetExpression()
    {
        $types = [
            Expression::EXPRESSION_TYPE_PLAIN,
            Expression::EXPRESSION_TYPE_REPLACE,
            Expression::EXPRESSION_TYPE_INLINE_REPLACE
        ];

        foreach ($types as $type) {
            $expression = Expression::getExpression($type);

            $this->assertInternalType('string', $expression);

            //Check if expression is correct (no errors or exceptions fired)
            preg_match($expression, 'my-key');
        }
    }

    /**
     * @covers Phramework\Testphase\Expression::parse
     * @dataProvider parseProvider
     */
    public function testParse($input, $expected)
    {
        $return = Expression::parse($input);

        if ($expected !== null) {
            $this->assertInternalType('object', $return);
        }

        $this->assertEquals(
            $expected,
            $return
        );
    }

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
            ],
            [
                'invalid{}',
                null
            ]
        ];
    }
}
