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
    /**
     *
     * @var object
     */
    protected $export;

    public function getExport()
    {
        return $this->export;
    }

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
    protected $test = null;

    /**
     * Get parsed test
     * @return Testphase
     */
    public function getTest()
    {
        if ($this->test === null) {
            throw new \Exception('Test is not created');
        }
        return $this->test;
    }

    protected $contentsParsed;

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
     * Parse test informations from a json file
     * this method will parse the file and prepare the meta object
     * use createTest to complete creation of test
     * @param String $filename
     * @todo Set $validatorRequest header's subtype
     *
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

        $validatorRequest = new ObjectValidator(
            [
                'url' => new StringValidator(1, 1024),
                'method' => (new EnumValidator(Phramework::$methodWhitelist, true))
                    ->setDefault(Phramework::METHOD_GET),
                'headers' => (new ArrayValidator())
                    ->setDefault([]),
                'body' => (new ObjectValidator())
                    ->setDefault([])
            ],
            ['url']
        );

        $validatorResponse = new ObjectValidator(
            [
                'statusCode' => new UnsignedIntegerValidator(100, 999),
                'headers' => (new ObjectValidator()),
                'ruleObjects' => (new ArrayValidator())
                    ->setDefault([]),
                'export' => (new ObjectValidator())
                    ->setDefault((object)[])
            ],
            ['statusCode']
        );

        //Setup validator for parsed test
        $validator = new ObjectValidator(
            [
                'meta' => (new ObjectValidator([
                    'order' => (new IntegerValidator(-99999999, 99999999))
                        ->setDefault(0),
                    'ignore' => (new BooleanValidator())
                        ->setDefault(false),
                    'description' => new StringValidator(),
                    'JSONbody' => (new BooleanValidator())
                        ->setDefault(true)
                ])),
                'request' => $validatorRequest,
                'response' => $validatorResponse
            ],
            ['request', 'response']
        );

        //Parse test file, using validator's rules
        $this->contentsParsed = $contentsParsed = $validator->parse($contentsObject);

        $this->meta = (
            isset($contentsParsed->meta)
            ? $contentsParsed->meta
            : (object)[
                'order' => 0,
                'JSONbody' => true
            ]
        );
    }

    public function createTest()
    {
        //Recursive search whole object
        $contentsParsed = $this->searchAndReplace($this->contentsParsed);

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
        ->expectResponseHeader($contentsParsed->response->headers);

        $test->expectJSON($contentsParsed->meta->JSONbody);

        //Add rule objects to validate body
        foreach ($contentsParsed->response->ruleObjects as $key => $ruleObject) {
            if (is_string($ruleObject)) {
                $ruleObject = json_decode($ruleObject);
            }

            $test->expectObjectValidator(Object::createFromObjectValidator($ruleObject));
        }

        $this->test = $test;
        $this->export = $contentsParsed->response->export;
    }

    private function searchAndReplace($object)
    {
        foreach ($object as $key => &$value) {
            if (is_array($value) || is_object($value)) {
                $value = $this->searchAndReplace($value);
            }

            if (is_string($value)) {
                $matches = [];
                //Complete replace (key: "$$globalKey$")
                if (!!preg_match(
                    '/^\$\$([a-zA-Z][a-zA-Z0-9\.\-_]{1,})\$$/',
                    $value,
                    $matches
                )) {
                    $globalKey = $matches[1];

                    //replace
                    $value = static::getGlobal($globalKey);
                } elseif (!!preg_match_all(
                    '/\$\$([a-zA-Z][a-zA-Z0-9\.\-_]{1,})/',
                    $value,
                    $matches
                )) {
                    //Foreach variable replace
                    foreach ($matches[1] as $globalKey) {
                        $value = str_replace(
                            '$$' . $globalKey,
                            static::getGlobal($globalKey),
                            $value
                        );
                    }
                }
            }
        }
        return $object;
    }

    /**
     * @todo use type
     */
    public static function getResponseBodyJsonapiResource($ofType = null)
    {
        return '{
            "type": "object",
            "properties": {
                "data" : {
                    "type": "object",
                    "properties" : {
                        "type" : {
                            "type" : "string"
                        },
                        "id" : {
                            "type" : "string"
                        }
                    },
                    "required" : ["type", "id"]
                },
                "links" : {
                    "type": "object",
                    "properties":{
                        "self": {
                            "type": "url"
                        },
                        "related": {
                            "type": "url"
                        }
                    },
                    "required": ["self"]
                }
            },
            "required": ["data", "links"]
        }';
    }

    /**
     * @todo use type
     */
    public static function getResponseBodyJsonapiCollection()
    {
        return '{
            "type": "object",
            "properties": {
                "data" : {
                    "type": "array"
                },
                "links" : {
                    "type": "object",
                    "properties":{
                        "self": {
                            "type": "url"
                        },
                        "related": {
                            "type": "url"
                        }
                    },
                    "required": ["self"]
                }
            },
            "required": ["data", "links"]
        }';
    }

    public static function getResponseBodyJsonapiException()
    {
        return '{
            "type": "object"
        }';
    }

    public static function getResponseBodyJsonapiRelasionshipsSelf()
    {
        return '{
            "type": "object"
        }';
    }

    public static function getResponseBodyJsonapiRelasionshipsRelated()
    {
        return '{
            "type": "object"
        }';
    }
}

TestParser::addGlobal('randInteger', rand(1, 100));
TestParser::addGlobal('randString', \Phramework\Models\Util::readableRandomStringValidator());
TestParser::addGlobal('randHash', sha1(\Phramework\Models\Util::readableRandomStringValidator() . rand()));
TestParser::addGlobal('randBoolean', rand(1, 999) % 2 ? true : false);
