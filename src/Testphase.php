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

use Phramework\JSONAPI\Client\Endpoint;
use Phramework\JSONAPI\Client\Exceptions\ResponseException;
use Phramework\Testphase\Exceptions\HeaderException;
use Phramework\Testphase\Exceptions\RuleException;
use Phramework\Testphase\Report\RequestReport;
use Phramework\Testphase\Report\ResponseReport;
use Phramework\Testphase\Report\RuleReport;
use Phramework\Testphase\Report\TestphaseReport;
use Phramework\Testphase\Rule\Rule;
use Phramework\Util\Util;
use Phramework\Validate\BaseValidator;
use Rs\Json\Pointer;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @version 3.0.0
 */
class Testphase extends RawEndpoint
{
    /**
     * @var callable[]
     */
    private static $globalCallbacks = [];

    /**
     * Base API url
     * @var string
     */
    private static $base = '';

    /**
     * @var int[]
     * @deprecated since 2.0.0
     */
    private $ruleStatusCode = [200];

    /**
     * @var array
     * @deprecated since 2.0.0
     */
    private $ruleHeaders = [];

    /**
     * @var array
     * @deprecated since 2.0.0
     */
    private $ruleObjects = [];

    /**
     * @var Rule[]
     */
    private $rules;

    /**
     * @var int|null
     */
    private $timeout = null;

    /**
     * @var boolean
     */
    private $ruleJSON = false;

    /**
     * Request
     * @var string
     */
    private $method;

    /**
     * Request
     * @var null|string
     */
    private $body;

    /**
     * @param string      $url
     *     Request url, without the base part, (see setBase method)
     * @param string      $method       HTTP request method
     * @param array       $headers      HTTP request headers
     * @param string      $body         HTTP request body
     * @param boolean     $ruleJSON     Response rule, expect JSON encoded response body
     * @throws \Phramework\Exceptions\IncorrectParametersException When method is not correct
     */
    public function __construct(
        string $url,
        string $method = 'GET',
        array $headers = [],
        string $body = null,
        bool $ruleJSON = true
    ) {
        //When url does not contain schema use base as prefix
        if (parse_url($url, PHP_URL_SCHEME) !== null) {
            $url = $this->url;
        } else {
            $url = static::$base . $url;
        }

        $this->method = $method;

        $this->headers = $headers;

        //not for GET
        $this->body = $body;

        $this->ruleJSON = $ruleJSON;

        $this->rules = [];

        parent::__construct(''); //no type

        $this->setUrl($url);
    }

    /**
     * Run testphase
     * Will execute the request and apply all defined rules to validate the response
     * completing the test rules
     * @return TestphaseReport
     * @throws \Exception
     */
    public function run() : TestphaseReport
    {
        $start = time();

        try {
            //Get response
            $response = $this->raw(
                $this->method,
                $this->body
            );
        } catch (ResponseException $e) {
            /*
             * Extract response from exception
             */
            $response = $e->getResponse();
        }

        $end = time();

        return $this->handleResponse(
            $response,
            $start,
            $end
        );
    }

    /**
     * Handle response, test response against provided rules
     * @throws \Exception
     * @return TestphaseReport
     */
    private function handleResponse(
        \Phramework\Testphase\HTTPResponse $response,
        int $start,
        int $end
    ) : TestphaseReport {
        $headers = $response->getHeaders();

        if (!in_array($response->getStatusCode(), $this->ruleStatusCode, true)) {
            //todo convert to header rule
            throw new \Exception(sprintf(
                'Expected status code "%s" got "%s"',
                implode(' or ', $this->ruleStatusCode),
                $response->getStatusCode()
            ));
        }

        foreach ($this->ruleHeaders as $headerKey => $headerValue) {
            if (!isset($headers[$headerKey])) {
                /*throw new \Exception(sprintf(
                    'Expected header "%s" is not set',
                    $headerKey
                ));*/

                throw new HeaderException(
                    sprintf(
                        'Expected header "%s" is not set',
                        $headerKey
                    ),
                    $headerKey
                );
            }

            if ($headerValue != $headers[$headerKey]) {
                throw new HeaderException(
                    sprintf(
                        'Expected header value "%s" for header "%s" got "%s"',
                        $headerValue,
                        $headerKey,
                        $headers[$headerKey]
                    ),
                    $headerKey
                );

                /*throw new \Exception(sprintf(
                    'Expected header value "%s" for header "%s" got "%s"',
                    $headerValue,
                    $headerKey,
                    $headers[$headerKey]
                ));*/
            }
        }

        /**
         * @var RuleReport
         */
        $ruleReport = [];

        /**
         * Report status
         * @var bool
         */
        $reportStatus = true;

        $body = $response->getResponse()->getBody()->__toString();

        if ($this->ruleJSON && !Util::isJSON($body)) {
            //Ignore isJSON body on "204 No Content" when it's empty
            if ($response->getStatusCode() != 204 || !empty($body)) {
                throw new \Exception(sprintf(
                    'Expected valid JSON response Body'
                ));
            }
        }

        if ($this->ruleJSON) {
            $responseBodyObject = json_decode($body);

            //todo Throw rule object exception
            foreach ($this->ruleObjects as $ruleObject) {
                $ruleObject->parse($responseBodyObject);
            }

            $jsonPointer = new Pointer($body);
            foreach ($this->rules as $rule) {
                //TODO
                if (substr($rule->getPointer(), 0, strlen('/body')) === '/body') {
                    $pointer = substr(
                        $rule->getPointer(),
                        strlen('/body')
                    );
                }

                //$pointer = $rule->getPointer();
                //try {
                //get value from pointer
                if ($pointer === '/') {
                    $value = $responseBodyObject;
                } else {
                    $value = $jsonPointer->get($pointer);
                    $value = json_decode(json_encode($value)); //array to object
                }

                //} catch (Pointer\NonexistentValueReferencedException $e) {
                //    //todo
                //    throw new RuleException($e->getMessage());
                //}

                //if (is_subclass_of($rule->getSchema(), BaseValidator::class)) {
                    $validateResult = $rule->getSchema()->validate($value);

                    $reportStatus = $reportStatus && $validateResult->status;

                    $ruleReport[] = new RuleReport(
                        $rule,
                        $validateResult->status,
                        $validateResult->exception ?? null
                    );
                //}
                /*} else { //literal value
                    if ($value != $rule->getSchema()) {

                        //TODO
                        $ruleReport[] = new RuleReport(
                            $rule,
                            false,
                            new RuleException('invalid value for rule' . $rule->getPointer())
                        );
                    }
                }*/
            }
        }

        $callbackArguments = [
            $response
        ];

        //Call global callbacks
        foreach (static::$globalCallbacks as $globalCallback) {
            call_user_func_array(
                $globalCallback,
                $callbackArguments
            );
        }

        return new TestphaseReport(
            $response,
            (
                $reportStatus === true
                ? Report\TestphaseReport::STATUS_SUCCESS
                : Report\TestphaseReport::STATUS_FAILURE
            ),
            new RequestReport(
                $this->url,
                $this->method,
                $this->headers,
                $this->body,
                $start
            ),
            new ResponseReport( //this class should parse from $response
                $response->getStatusCode(),
                $response->getHeaders(),
                $body,
                $end,
                $end - $start
            ),
            $ruleReport
        );
    }

    /**
     * @param callable $callable
     * @throws \Exception
     */
    public static function addGlobalCallback($callable) {
        if (!is_callable($callable)) {
            throw new \Exception('Not a callable');
        }

        static::$globalCallbacks[] = $callable;
    }

    /**
     * Set expected HTTP response Status Code
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     * @param  int|int[] $statusCode
     * @return $this
     * @deprecated
     */
    public function expectStatusCode($statusCode)
    {
        //Work with arrays, if single int is given
        if (!is_array($statusCode)) {
            $statusCode = [$statusCode];
        }

        $this->ruleStatusCode = $statusCode;

        return $this;
    }

    /**
     * Add expected response header
     * @param  array[]|object $ruleHeaders
     * @return $this
     * @throws \Exception When $ruleHeaders is not an array
     * @deprecated
     */
    public function expectHeader($ruleHeaders)
    {
        if (is_object($ruleHeaders)) {
            $ruleHeaders = (array) $ruleHeaders;
        }

        if (!is_array($ruleHeaders)) {
            throw new \Exception(
                'Expecting array at expectResponseHeader method'
            );
        }

        $this->ruleHeaders = array_merge(
            $this->ruleHeaders,
            $ruleHeaders
        );

        return $this;
    }

    /**
     * Set rule, expect JSON encoded response body.
     * When true it will throw an error if the response is not a valid JSON.
     * **NOTE** ruleObjects only works with this flag set to true
     * @param  boolean $flag  Value of the flag, default is true
     * @return $this
     */
    public function expectJSON($flag = true)
    {
        $this->ruleJSON = $flag;

        return $this;
    }

    /**
     * Object validator, as an additional set of rules to validate the response.
     * @param  BaseValidator $object Validator object
     * @return $this
     * @deprecated
     */
    public function expectObject($object)
    {
        $this->ruleObjects[] = $object;

        return $this;
    }

    /**
     * @param integer $timeout
     * @return $this
     */
    public function expectTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @param Rule $rule
     * @return $this
     */
    public function expectRule(Rule $rule)
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * Set base API url
     * @param string $base
     */
    public static function setBase($base)
    {
        static::$base = $base;
    }

    /**
     * Get base API url
     * @return string
     */
    public static function getBase()
    {
        return static::$base;
    }


    /**
     * Get library's version
     * @uses Doc comments of Testphase class to extract version tag
     * @return string
     */
    public static function getVersion()
    {
        /*$reflection = new \ReflectionClass(Testphase::class);
        $comment = $reflection->getDocComment();

        preg_match('/\@version ([\w\.]+(:?\-[a-zA-Z0-9]+)?)\n/', $comment, $matches);

        if ($matches && count($matches) > 1) {
            return $matches[1];
        } else {
            throw new \Exception('Unable retrieve library`s version');
        }*/

        return '3.0.0';
    }
}
