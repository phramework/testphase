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

use \Phramework\Validate\AnyOf;
use Phramework\Validate\BaseValidator;
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
     * @var Testphase[]
     */
    protected $testphaseCollection = null;

    /**
     * Get parsed test
     * @return Testphase[]
     * @throws \Exception When test is not created
     */
    public function getTest()
    {
        if ($this->testphaseCollection === null) {
            throw new \Exception('Test is not created');
        }
        return $this->testphaseCollection;
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
     * Parse test information from a json file
     * this method will parse the file and prepare the  object
     * use createTest to complete creation of test
     * @param String $filename JSON file containing the test
     * @todo Set $validatorRequest header's subtype
     * @throws \Phramework\Exceptions\NotFoundException When file is not found.
     * @throws \Exception When file contains not valid JSON.
     * @throws \Phramework\Exceptions\MissingParametersException When required test properties are not set.
     * @throws \Phramework\Exceptions\IncorrectParametersException When test properties are not correct.
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

        //Check if contents are a valid JSON file
        if (!Util::isJSON($contents)) {
            throw new \Exception(sprintf(
                'File "%s" isn\'t a valid JSON file',
                $filename
            ));
        }

        //Decode test file
        $contentsObject = json_decode($contents);

        //Setup validator, to validate and parse the test file

        $requestValidator = new ObjectValidator(
            [
                'url' => new StringValidator(1, 2048),
                'method' => (new StringValidator())
                    ->setDefault('GET'),
                'iterators' => (new ObjectValidator(
                    [],
                    [],
                    true //Todo use Expression::PATTERN_KEY
                ))->setDefault((object)[]),
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
            ['url'],
            false
        );

        $responseValidator = new ObjectValidator(
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
                    ->setDefault((object) []),
                'timeout' => (new UnsignedIntegerValidator())
                    ->setDefault(null),
                'rules' => (new ObjectValidator())
                    ->setDefault((object) []),
                'ruleObjects' => (new ArrayValidator())
                    ->setDefault([]),
                'export' => (new ObjectValidator(
                    [],
                    [],
                    true //todo Validate key's pattern (using additionalProperties)
                ))->setDefault((object)[])
            ],
            ['statusCode'],
            false
        );

        //Setup validator for parsed test
        $validator = new ObjectValidator(
            [
                'meta' => (new ObjectValidator(
                    [
                        'order' => (new IntegerValidator(-99999999, 99999999))
                            ->setDefault(0),
                        'ignore' => (new BooleanValidator())
                            ->setDefault(false),
                        'description' => (new StringValidator())
                            ->setDefault(null),
                        'JSONbody' => (new BooleanValidator())
                            ->setDefault(true),
                        'incomplete' => (new OneOf([
                            new BooleanValidator(),
                            new StringValidator(1, 4096)
                        ]))->setDefault(false),
                    ],
                    [],
                    false
                ))->setDefault((object) [
                    'order' => 0,
                    'description' => null,
                    'JSONbody' => true,
                    'incomplete' => false,
                    'ignore' => false
                ]),
                'request' => $requestValidator,
                'response' => $responseValidator
            ],
            ['request', 'response'],
            false
        );

        //Parse test file, using validator's rules
        $this->contentsParsed = $contentsParsed = $validator->parse(
            $contentsObject
        );

        //Set testparser meta
        $this->meta = $contentsParsed->meta;
    }

    /**
     * @todo clean up
     */
    public function createTest()
    {
        $testphaseCollection = [];

        if (!empty((array)$this->contentsParsed->request->iterators)) {
            //searchAndReplace in request->iterators first
            $iterators = $this->searchAndReplace(
                $this->contentsParsed->request->iterators
            );

            //replace back to contentsParsed
            $this->contentsParsed->request->iterators = $iterators;

            //Get combinations of iterator values
            $combinations = Util::cartesian((array) $iterators);
        } else {
            //Add a single test with no combinations
            $combinations = [[]];
        }

        foreach ($combinations as $combination) {
            //Set combination
            foreach ($combination as $combinationKey => $combinationValue) {
                Globals::set($combinationKey, $combinationValue);
            }

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

            //In case there is no request body, then at least one test must be created
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

                //Add rules
                foreach ($contentsParsed->response->rules as $pointer => $ruleValue) {
                    if (is_string($ruleValue) && Util::isJSON($ruleValue)) {
                        $ruleValue = BaseValidator::createFromJSON($ruleValue);
                    } elseif (is_object($ruleValue)) {
                        $ruleValue = BaseValidator::createFromObject($ruleValue);
                    } elseif (is_subclass_of(
                        $ruleValue,
                        \Phramework\Validate\BaseValidator::class
                    )) {
                        //do nothing
                    } else {
                        //do nothing
                    }

                    //Push rule
                    $testphase->expectRule($pointer, $ruleValue);
                }

                //Add rule objects to validate body
                foreach ($contentsParsed->response->ruleObjects as $key => $ruleObject) {
                    if (is_string($ruleObject)) {
                        $testphase->expectObject(ObjectValidator::createFromJSON($ruleObject));
                    } elseif (is_subclass_of(
                        $ruleObject,
                        \Phramework\Validate\BaseValidator::class
                    )) {
                        $testphase->expectObject($ruleObject);
                    } else {
                        $testphase->expectObject(ObjectValidator::createFromObject($ruleObject));
                    }
                }

                //push test
                $testphaseCollection[] = $testphase;

                //todo set only once
                $this->export = $contentsParsed->response->export;
            }
        }

        $this->testphaseCollection = $testphaseCollection;
    }

    /**
     * Replace incline and full replace key inside a test object
     * @param object|array $inputObject
     * @return object|array
     * @todo add special exception, when global is not found test should
     * be ignored with special warning (EG unavailable)
     */
    private function searchAndReplace($inputObject)
    {
        if (is_object($inputObject)) {
            $object = clone $inputObject;
        } else {
            $object = $inputObject;
        }

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
}
