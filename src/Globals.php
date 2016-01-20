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

use \Phramework\Testphase\Testphase;
use \Phramework\Testphase\TestParser;
use \Phramework\Testphase\Util;

/**
 * Global variables
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @version 1.0.0
 * @since 1.0.0
 */
class Globals
{
    /**
     * Key of type variable
     */
    const KEY_VARIABLE = 'variable';
    /**
     * Key of type function
     */
    const KEY_FUNCTION = 'function';
    /**
     * Key of type array
     */
    const KEY_ARRAY    = 'array';

    /**
     * @var object
     */
    protected static $globals = null;

    /**
     * @todo keep a documentation record of globals
     */
    protected static function initializeGlobals()
    {
        //initialize globals
        static::$globals = new \stdClass();

        static::$globals->{'rand-integer'} = function ($max = null) {
            if ($max === null) {
                $max = getrandmax();
            } else {
                $max = intval($max);
            }

            return rand(0, $max);
        };

        static::$globals->{'rand-string'} = [Util::class, 'readableRandomString'];

        static::$globals->{'rand-hash'} =  function () {
            return sha1(rand() . mt_rand() . rand());
        };

        static::$globals->{'rand-boolean'} = function () {
            return (boolean)rand(0, 1);
        };

        static::$globals->{'timestamp'} = function () {
            return time();
        };

        static::$globals->{'microtime'} = function ($get_as_float = false) {
            return microtime((bool)$get_as_float);
        };
    }

    /**
     * Check if global variable exists
     * @param string $key
     * @throws \Exception
     * @return boolean
     */
    public static function exists($key)
    {
        if (static::$globals === null) {
            static::initializeGlobals();
        }

        return property_exists(static::$globals, $key);
    }

    /**
     * Get global key's value.
     * An expression to access array elements or a function can be given.
     * @param string $key Expression key, can have parenthesis or square brackets operator.
     * @return mixed|callable|array If you access a function without the
     * parenthesis operator or an array without square brackets operator
     * then this method will return the callable or the whole array respectively.
     * @example
     * ```php
     * Globals::get('myVarible');
     *
     * Globals::get('rand-boolean()');
     *
     * Globals::get('rand-integer(10)'); //A random integer from 0 to 10
     *
     * Globals::get('myArray[1]'); //Get second array element
     * ```
     * @throws \Exception When expression key is invalid.
     * @throws \Phramework\Exceptions\NotFoundException When key is not found.
     */
    public static function get($key = null, $operators = null)
    {
        if (static::$globals === null) {
            static::initializeGlobals();
        }

        if ($key !== null) {
            $parsed = Expression::parse($key);

            if ($parsed === null) {
                throw new \Exception(sprintf(
                    'Invalid key "%s"',
                    $key
                ));
            }

            if (!static::exists($parsed->key)) {
                throw new \Phramework\Exceptions\NotFoundException(sprintf(
                    'Key "%s" not found in globals',
                    $parsed->key
                ));
            }

            $global = static::$globals->{$parsed->key};

            switch ($parsed->mode) {
                case Globals::KEY_FUNCTION:
                    $functionParameters = [];

                    if (property_exists($parsed, 'parameters')) {
                        $functionParameters = $parsed->parameters;
                    }

                    return call_user_func_array(
                        $global,
                        $functionParameters
                    );
                    //break;
                case Globals::KEY_ARRAY:
                    return $global[$parsed->index];
                    //break;
                case Globals::KEY_VARIABLE:
                default:
                    return $global;
            }
        }

        return static::$globals;
    }

    /**
     * Will overwrite value with same key
     * @param string $key Key
     * @param mixed $value
     * @example
     * ```php
     * Globals::set('myVariable', 5);
     * Globals::set(
     *     'dots',
     *     function ($length = 4) {
     *         return str_repeat('.', $length);
     *     }
     * );
     *
     * Globals::get('dots()'); //Will return a string of 4 dots
     * Globals::get('dots(10)'); //Will return a string of 10 dots
     * ```
     * @throws \Exception When key is invalid, *see Expression::PATTERN_KEY*
     */
    public static function set($key, $value)
    {
        if (static::$globals === null) {
            static::initializeGlobals();
        }

        if (!preg_match('/^' . Expression::PATTERN_KEY . '$/', $key)) {
            throw new \Exception('Invalid key');
        }

        static::$globals->{$key} = $value;
    }

    /**
     * Return keys and values of global variables as string
     * @return string
     */
    public static function toString()
    {
        $return = [];

        foreach (static::$globals as $key => $value) {
            $type = gettype($value);

            if (is_callable($value)) {
                $valueString = 'callable';
                $type = 'callable';
            } elseif (is_array($value)) {
                $valueString = implode(', ', $value);
            } else {
                $valueString = (string)$value;
            }

            $return[] = sprintf(
                '"%s": (%s) %s',
                $key,
                $type,
                $valueString
            );
        }

        return implode(PHP_EOL, $return);
    }
}
