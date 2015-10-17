<?php
/**
 * Copyright 2015 Spafaridis Xenofon
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

use \Phramework\Phramework;
use \Phramework\Validate\Object;
use \Phramework\Validate\Integer;
use \Phramework\Validate\UnsignedInteger;
use \Phramework\Validate\ArrayValidator;
use \Phramework\Validate\Enum;
use \Phramework\Validate\Boolean;
use \Phramework\Validate\String;
use \Phramework\Validate\URL;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenophon Spafaridis <nohponex@gmail.com>
 */
class TestParser
{

    protected static $global;

    public static function addGlobal($key, $value)
    {
        if (!self::$global) {
            self::$global = new \stdClass();
        }

        self::$global->{$key} = $value;
        return static::class;
    }

    public static function checkGlobalSet($key)
    {
        if (!property_exists(static::$global, $key)) {
            throw new \Exception(sprintf(
                'Key "%s" not found in TestParser globals',
                $key
            ));
        }
        return static::class;
    }

    public static function getGlobal($key = null)
    {
        if ($key) {
            static::checkGlobalSet($key);
            return static::$global->{$key};
        }

        return static::$global;
    }

    /**
     * Parsed test
     * @var Testphase
     */
    protected $test;

    /**
     * Get parsed test
     * @return Testphase
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * Parsed test's meta object
     * @var object
     */
    protected $meta;

    /**
     * Get parsed test's meta object
     * @return object
     */
    public function getMeta()
    {
        return $this->meta;
    }

    protected $filename;

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param String $filename
     * @todo Set $validatorRequest header's subtype
     */
    public function __construct($filename)
    {
        $this->filename = $filename;

        if (!file_exists($filename)) {
            throw new \Exception(sprintf(
                'File %s doesn\'t exist',
                $filename
            ));
        }

        $contents = file_get_contents($filename);

        //Check if contents are a valid jsonfile
        if (!Testphase::isJSON($contents)) {
            throw new \Exception(sprintf(
                'File %s isn\'t a valid JSON file',
                $filename
            ));
        }

        //Decode test file
        $contentsObject = json_decode($contents);

        //Setup validator, to validate and parse the test file

        $validatorRequest = new Object(
            [
                'url' => new String(1, 1024),
                'method' => (new Enum(Phramework::$methodWhitelist, true))
                    ->setDefault(Phramework::METHOD_GET),
                'headers' => (new ArrayValidator())
                    ->setDefault([]),
                'body' => (new Object())
                    ->setDefault([])
            ],
            ['url']
        );

        $validatorResponse = new Object(
            [
                'statusCode' => new UnsignedInteger(100, 999),
                'headers' => (new Object()),
                'ruleObjects' => (new ArrayValidator())
                    ->setDefault([])
            ],
            ['statusCode']
        );

        //Setup validator for parsed test
        $validator = new Object(
            [
                'meta' => (new Object([
                    'order' => (new Integer(-99999999, 99999999))
                        ->setDefault(0),
                    'ignore' => (new Boolean())
                        ->setDefault(false),
                    'description' => new String()
                ])),
                'request' => $validatorRequest,
                'response' => $validatorResponse
            ],
            ['request', 'response']
        );

        //Parse test file, using validator's rules
        $contentsParsed = $validator->parse($contentsObject);

        //Recursive search whole object
        $contentsParsed = $this->searchAndReplace($contentsParsed);

        //Create a Testphase object using parsed rule
        $test = (new Testphase(
            $contentsParsed->request->url,
            $contentsParsed->request->method,
            $contentsParsed->request->headers,
            (
                isset($contentsParsed->request->body)
                ? json_encode($contentsParsed->request->body)
                : null
            ),
            true //json
        ))
        ->expectStatusCode($contentsParsed->response->statusCode)
        ->expectResponseHeader($contentsParsed->response->headers)
        ->expectJSON();

        //Add rule objects to validate body
        foreach ($contentsParsed->response->ruleObjects as $key => $ruleObject) {
            $test->expectObject(Object::createFromObject($ruleObject));
        }

        $this->meta = (
            isset($contentsParsed->meta)
            ? $contentsParsed->meta
            : (object)['order' => 0]
        );

        $this->test = $test;
    }

    private function searchAndReplace($object)
    {
        foreach ($object as $key => &$value) {
            if (is_array($value) || is_object($value)) {
                $value = $this->searchAndReplace($value);
            }

            if (is_string($value)) {
                $matches = [];
                //Complete replace (key: "$globalKey$")
                if (!!preg_match('/^\$([a-zA-Z][a-zA-Z0-9\.\-_]{1,})\$$/', $value, $matches)) {
                    $globalKey = $matches[1];

                    //replace
                    $value = static::getGlobal($globalKey);
                } elseif (!!preg_match_all('/\$([a-zA-Z][a-zA-Z0-9\.\-_]{1,})/', $value, $matches)) {

                    //Foreach variable replace
                    foreach($matches[1] as $globalKey) {
                        $value = str_replace(
                            '$' . $globalKey,
                            static::getGlobal($globalKey),
                            $value
                        );
                    }
                }
            }
        }
        return $object;
    }
}

TestParser::addGlobal('randInteger', rand(1, 100000));
TestParser::addGlobal('randString', \Phramework\Models\Util::readableRandomString());
TestParser::addGlobal('randHash', sha1(\Phramework\Models\Util::readableRandomString() . rand()));
TestParser::addGlobal('randBoolean', rand(1, 100000) % 2 ? true : false);
