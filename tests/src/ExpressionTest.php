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

/**
 * @coversDefaultClass Phramework\Testphase\Expression
 */
class ExpressionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::getExpression
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
     * @covers ::parse
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
