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
 * Global variables
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @version 1.0.0
 * @since 1.0.0
 */
class Globals
{
    const KEY_VARIABLE = 'variable';
    const KEY_FUNCTION = 'function';
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
            }
            return rand(0, $max);
        };

        static::$globals->{'rand-string'}  = function ($length = 8) {
            return Util::readableRandomString($length);
        };

        static::$globals->{'rand-hash'}    = sha1(rand() . mt_rand() . rand());
        static::$globals->{'rand-boolean'} = rand(1, 999) % 2 ? true : false;
        static::$globals->{'array'}        = [1, 3, 5, 7, 10];
    }

    /**
     * @param string $key
     * @throws Exception
     */
    public static function exists($key)
    {
        if (!static::$globals) {
            static::initializeGlobals();
        }

        return property_exists(static::$globals, $key);
    }

    /**
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    public static function get($key = null, $operators = null)
    {
        if ($key !== null) {
            $parsed = Expression::parse($key);

            if ($parsed === null) {
                throw new \Exception('Invalid key ' . $key);
            }

            if (!static::exists($parsed->key)) {
                throw new \Exception(sprintf(
                    'Key "%s" not found in TestParser globals',
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
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        if (!static::$globals) {
            static::initializeGlobals();
        }

        static::$globals->{$key} = $value;
    }
}
