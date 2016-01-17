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

use \Phramework\Validate\AnyOf;
use \Phramework\Validate\OneOf;
use \Phramework\Validate\AllOf;
use \Phramework\Validate\ObjectValidator;
use \Phramework\Validate\IntegerValidator;
use \Phramework\Validate\UnsignedIntegerValidator;
use \Phramework\Validate\ArrayValidator;
use \Phramework\Validate\EnumValidator;
use \Phramework\Validate\BooleanValidator;
use \Phramework\Validate\StringValidator;
use \Phramework\Validate\URLValidator;

/**
 * Parse tests from file.
 * Α TestParser instance may contain one or more Testphase instances.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 0.0.0
 * @version 1.0.0
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

    /**
     * Parsed test
     * @var Testphase
     */
    protected $testphase = null;

    /**
     * Get parsed test
     * @return Testphase[]
     * @throws Exception When test is not created
     */
    public function getTest()
    {
        if ($this->testphase === null) {
            throw new \Exception('Test is not created');
        }
        return $this->testphase;
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
     * @param String $filename JSON file containing the test
     * @todo Set $validatorRequest header's subtype
     * @throws Phramework\Exceptions\NotFoundException When file is not found.
     * @throws Exception When file contains not valid JSON.
     * @throws Phramework\Exceptions\MissingParametersException When required test properties are not set.
     * @throws Phramework\Exceptions\IncorrectParametersException When test properties are not correct.
     */
    public function __construct($filename)
    {
        $this->filename = $filename;

        if (!file_exists($filename)) {
            throw new \Phramework\Exceptions\NotFoundException(sprintf(
                'File "%s" doesn\'t exist',
                $filename
            ));
        }

        $contents = file_get_contents($filename);

        //Check if contents are a valid jsonfile
        if (!Util::isJSON($contents)) {
            throw new \Exception(sprintf(
                'File "%s" isn\'t a valid JSON file',
                $filename
            ));
        }

        //Decode test file
        $contentsObject = json_decode($contents);

        //Setup validator, to validate and parse the test file

        $validatorRequest = new ObjectValidator(
            [
                'url' => new StringValidator(1, 2048),
                'method' => (new StringValidator())
                    ->setDefault('GET'),
                'headers' => (new ArrayValidator(
                    0,
                    null,
                    new StringValidator()
                ))->setDefault([]),
                'body' => new OneOf([ // Allow objects, strings or array of them
                    new ObjectValidator(),
                    new StringValidator,
                    new ArrayValidator(
                        0,
                        null,
                        new OneOf([
                            new ObjectValidator(),
                            new StringValidator
                        ])
                    )
                ])
            ],
            ['url']
        );

        $validatorResponse = new ObjectValidator(
            [
                'statusCode' => new AnyOf([
                    new UnsignedIntegerValidator(100, 999),
                    new ArrayValidator(
                        1,
                        10,
                        new UnsignedIntegerValidator(100, 999)
                    )
                ]),
                'headers' => (new ObjectValidator())
                    ->setDefault((object)[]),
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
        $this->contentsParsed = $contentsParsed = $validator->parse(
            $contentsObject
        );

        //Fix meta if not defined
        $this->meta = (
            isset($contentsParsed->meta)
            ? $contentsParsed->meta
            : (object)[
                'order' => 0,
                'JSONbody' => true
            ]
        );
    }

    /**
     * @todo clean up
     */
    public function createTest()
    {
        //Recursive search whole object
        $contentsParsed = $this->searchAndReplace($this->contentsParsed);

        $requestBody = null;

        $requestBodies = [];

        if (isset($contentsParsed->request->body)
            && !empty($contentsParsed->request->body)
        ) {
            if (!is_array($contentsParsed->request->body)) {
                //Work with array
                $contentsParsed->request->body = [
                    $contentsParsed->request->body
                ];
            }

            foreach ($contentsParsed->request->body as $body) {
                //Push to bodies
                $requestBodies[] = (
                    is_object($body)
                    ? json_encode($body)
                    : $body
                );
            }
        }

        $testphaseCollection = [];

        //Incase there is no request body, then at least one test must be created
        if (empty($requestBodies)) {
            $requestBodies[] = null;
        }

        foreach ($requestBodies as $requestBody) {
            //Create a Testphase object using parsed rule
            $testphase = (new Testphase(
                $contentsParsed->request->url,
                $contentsParsed->request->method,
                $contentsParsed->request->headers,
                $requestBody
            ))
            ->expectStatusCode($contentsParsed->response->statusCode)
            ->expectResponseHeader($contentsParsed->response->headers);

            $testphase->expectJSON(
                isset($contentsParsed->meta->JSONbody)
                ? $contentsParsed->meta->JSONbody
                : true
            );

            //Add rule objects to validate body
            foreach ($contentsParsed->response->ruleObjects as $key => $ruleObject) {
                if (is_string($ruleObject)) {
                    $ruleObject = json_decode($ruleObject);
                }

                $testphase->expectObject(ObjectValidator::createFromObject($ruleObject));
            }
            $testphaseCollection[] = $testphase;
        }

        $this->testphase = $testphaseCollection;
        //todo
        $this->export = $contentsParsed->response->export;
    }

    /**
     * @todo add special exception, when global is not found test should
     * be ignored with special warning (EG unavailable)
     * @todo add function
     * @todo add array access
     */
    private function searchAndReplace($object)
    {
        $pattern_replace        = Expression::getExpression(
            Expression::EXPRESSION_TYPE_REPLACE
        );

        $pattern_inline_replace = Expression::getExpression(
            Expression::EXPRESSION_TYPE_INLINE_REPLACE
        );

        list($prefix, $suffix) = Expression::getPrefixSuffix(
            Expression::EXPRESSION_TYPE_INLINE_REPLACE
        );

        foreach ($object as $key => &$value) {
            if (is_array($value) || is_object($value)) {
                $value = $this->searchAndReplace($value);
            }

            if (is_string($value)) {
                $matches = [];
                //Complete replace
                if (!!preg_match(
                    $pattern_replace,
                    $value,
                    $matches
                )) {
                    $globalsKey = $matches['value'];

                    //replace
                    $value = Globals::get($globalsKey);

                } elseif (!!preg_match_all(
                    $pattern_inline_replace,
                    $value,
                    $matches
                )) {
                    //Foreach variable replace in string
                    foreach ($matches['value'] as $globalsKey) {
                        
                        $value = str_replace(
                            $prefix . $globalsKey . $suffix,
                            Globals::get($globalsKey),
                            $value
                        );
                    }
                }
            }
        }
        return $object;
    }

/*
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
    **/
}
