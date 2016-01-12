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
    /**
     * @var object
     */
    protected static $globals = null;

    public static function regex($value, $debug = false)
    {
        $key = '[a-zA-Z][a-zA-Z0-9\.\-_]{1,}';
        $parameter = '([\'\"])?' . $key . '\4?';
        //function parameter literal can be anything
        //param should be key
        $prefix = '{\{\{';
        $suffix = '\}\}\}';
        $exp = sprintf(
            '/^\%s(?P<key>%s)(?:(?P<function>\((?P<parameter>%s)?\))|(?P<array>\[(?P<index>[1-9]*[0-9])\]))?%s$/',
            $prefix,
            $key,
            $parameter,
            $suffix
        );

        $return = preg_match(
            $exp,
            $value,
            $matches
        );

        if (!$return) {
            return null;
        }



        $object = new \stdClass();

        $object->key = $matches['key'];
        $object->mode = 'variable';

        if (isset($matches['function']) && !empty($matches['function'])) {
            $object->mode = 'function';
            if (key_exists('parameter', $matches)  && !empty($matches['parameter'])) {
                $object->parameter = $matches['parameter'];
            }
        } elseif (isset($matches['array'])  && !empty($matches['array'])) {
            $object->mode = 'array';
            //should exists
            $object->index = $matches['index'];
        }

        if ($debug) {
            echo $exp . PHP_EOL;

            print_r([
                $value,
                $matches
            ]);

            var_dump($object);
        }

        return $object;
    }

    /**
     * @todo improve
     */
    protected static function initializeGlobals()
    {
        //initialize globals
        static::$globals = new \stdClass();

        static::$globals->{'rand.integer'} = rand(1, 100);
        static::$globals->{'rand.string'}  = [Util::class, 'readableRandomString'];
        static::$globals->{'rand.hash'}    = sha1(rand() . rand() . rand());
        static::$globals->{'rand.boolean'} = rand(1, 999) % 2 ? true : false;
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

        if (!property_exists(static::$globals, $key)) {
            throw new \Exception(sprintf(
                'Key "%s" not found in TestParser globals',
                $key
            ));
        }
    }

    /**
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    public static function get($key = null, $operators = null)
    {
        if ($key !== null) {

            $regex = self::regex($key);

            if ($regex === null) {
                throw new \Exception('Invalid key ' . $key);
            }

            static::exists($regex->key);

            $global = static::$globals->{$regex->key};

            switch ($regex->mode) {
                case 'function':
                    $parameters = [];

                    if (property_exists($regex, 'parameters')) {
                        $parameters[$regex->parameters];
                    }

                    return call_user_func_array(
                        $global,
                        $parameters
                    );
                    //break;
                case 'array':
                    return $global[$regex->index];
                    //break;
                case 'variable':
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
