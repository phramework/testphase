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
    /**
     * Expression of plain type
     */
    const EXPRESSION_TYPE_PLAIN          = 'plain';
    /**
     * Expression of type "replace", used to replace the whole key with expression value.
     */
    const EXPRESSION_TYPE_REPLACE        = 'replace';
    /**
     * Expression of type "inline_replace", used to inline replace the key with the expression value.
     */
    const EXPRESSION_TYPE_INLINE_REPLACE = 'inline_replace';

    const PATTERN_KEY = '[a-zA-Z][a-zA-Z0-9\-_]{1,}';
    const PATTERN_FUNCTION_PARAMETER = self::PATTERN_KEY . '|([\'\"]?)' . '[a-zA-Z0-9\-_]{1,}' . '\5';
    const PATTERN_ARRAY_INDEX = '[1-9]*[0-9]';

    /**
     * @param  string $expression
     * @return string[4]
     */
    public static function getPrefixSuffix($expression = Expression::EXPRESSION_TYPE_PLAIN)
    {
        $prefix = '';
        $suffix = '';
        $patternPrefix = '';
        $patternSuffix = '';

        switch ($expression) {
            case Expression::EXPRESSION_TYPE_PLAIN:
                $patternPrefix = '^';
                $patternSuffix = '$';
                break;
            case Expression::EXPRESSION_TYPE_REPLACE:
                $prefix = '{{{';
                $suffix = '}}}';
                $patternPrefix = '^';
                $patternSuffix = '$';
                break;
            case Expression::EXPRESSION_TYPE_INLINE_REPLACE:
                $prefix = '{{';
                $suffix = '}}';
                break;
        }

        return [$prefix, $suffix, $patternPrefix, $patternSuffix];
    }

    /**
     * @param string $expression
     * @return string Returns a regular expession string
     */
    public static function getExpression($expression = Expression::EXPRESSION_TYPE_PLAIN)
    {
        //$keyExpression = '[a-zA-Z][a-zA-Z0-9\-_]{1,}';

        //$functionParametersExpression = '([\'\"]?)' . '[a-zA-Z0-9\-_]{1,}' . '\5';
        //$functionParametersExpression = self::KEY_EXPRESSION . '|([\'\"]?)' . '[a-zA-Z0-9\-_]{1,}' . '\5';
        //$arrayIndexExpression = '[1-9]*[0-9]';

        list(
            $prefix,
            $suffix,
            $patternPrefix,
            $patternSuffix
        ) = self::getPrefixSuffix($expression);

        $expression = sprintf(
            '/%s%s(?P<value>(?P<key>%s)(?:(?P<function>\((?P<parameters>%s)?\))|(?P<array>\[(?P<index>%s)\]))?)%s%s/',
            $patternPrefix,
            preg_quote($prefix),
            self::PATTERN_KEY,
            self::PATTERN_FUNCTION_PARAMETER,
            self::PATTERN_ARRAY_INDEX,
            preg_quote($suffix),
            $patternSuffix
        );

        return $expression;
    }

    /**
     * @param $value
     * @return null|object
     * @example
     * ```php
     * $parsed = Expression::parse('myFunction(10)');
     *
     * print_r($parsed);
     *
     * //Will output
     * //stdClass Object
     * //(
     * //    [key] => myFunction
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
