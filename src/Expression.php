<?php
/**
 * Copyright 2015 Xenofon Spafaridis
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

use \Phramework\Testphase\Testphase;
use \Phramework\Testphase\TestParser;
use \Phramework\Testphase\Util;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @version 1.0.0
 * @since 1.0.0
 */
class Expression
{
    const EXPRESSION_PLAIN          = 'plain';
    const EXPRESSION_REPLACE        = 'replace';
    const EXPRESSION_INLINE_REPLACE = 'inline_replace';

    /**
     * [getExpression description]
     * @param   $expression [description]
     * @return string Returns a regular expession string
     */
    public static function getExpression($expression = Expression::EXPRESSION_PLAIN)
    {
        $keyExpression = '[a-zA-Z][a-zA-Z0-9\-_]{1,}';

        //$functionParametersExpression = '([\'\"]?)' . '[a-zA-Z0-9\-_]{1,}' . '\5';
        $functionParametersExpression = $keyExpression . '|([\'\"]?)' . '[a-zA-Z0-9\-_]{1,}' . '\5';
        $arrayIndexExpression = '[1-9]*[0-9]';
        $prefix = '';
        $suffix = '';

        switch ($expression) {
            case Expression::EXPRESSION_REPLACE:
                $prefix = '\{\{\{';
                $suffix = '\}\}\}';
                break;
            case Expression::EXPRESSION_INLINE_REPLACE:
                $prefix = '{\{';
                $suffix = '\}\}';
                break;
        }

        $expression = sprintf(
            '/^%s(?P<value>(?P<key>%s)(?:(?P<function>\((?P<parameters>%s)?\))|(?P<array>\[(?P<index>%s)\]))?)%s$/',
            $prefix,
            $keyExpression,
            $functionParametersExpression,
            $arrayIndexExpression,
            $suffix
        );

        return $expression;
    }

    /**
     * @param $value
     * @return null|object
     * @example
     * ```php
     * $parsed = Expression::parse('Myfunction(10)');
     *
     * print_r($parsed);
     *
     * //Will output
     * //stdClass Object
     * //(
     * //    [key] => rand-string
     * //    [mode] => function
     * //    [parameters] => [6]
     * //)
     * ```
     */
    public static function parse($value)
    {
        $expression = Expression::getExpression();

        $return = preg_match(
            $expression,
            $value,
            $matches
        );

        if (!$return) {
            return null;
        }

        $parsed = new \stdClass();

        $parsed->key = $matches['key'];
        $parsed->mode = Globals::KEY_VARIABLE;

        if (isset($matches['function']) && !empty($matches['function'])) {
            $parsed->mode = Globals::KEY_FUNCTION;

            if (key_exists('parameters', $matches)  && strlen((string)$matches['parameters'])) {
                //Handles only one parameter
                $parsed->parameters = [$matches['parameters']];
            }
        } elseif (isset($matches['array'])  && !empty($matches['array'])) {
            $parsed->mode = Globals::KEY_ARRAY;
            //should exists
            $parsed->index = $matches['index'];
        }

        return $parsed;
    }
}
