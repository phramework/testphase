<?php
/*
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
use Phramework\Testphase\Rule\StatusCodeRule;
use Phramework\Util\Util;
use Phramework\Validate\BaseValidator;
use Rs\Json\Pointer;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @version 3.0.0
 */
class Testphase extends AbstractTestphase
{
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
     * @since 2.0.0
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
        HTTPResponse $response,
        int $start,
        int $end
    ) : TestphaseReport {
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

        $target = (object) [
            trim(Rule::ROOT_HEADER, '/')      => $response->getHeaders(),
            trim(Rule::ROOT_STATUS_CODE, '/') => $response->getStatusCode(),
            trim(Rule::ROOT_BODY, '/')        => $this->ruleJSON ? json_decode($body) : $body,
            trim(Rule::ROOT_TIMEOUT, '/')     => $end - $start
        ];

        $test = new Pointer(json_encode($target));

        foreach ($this->rules as $rule) {
            $pointer = rtrim($rule->getPointer(), '/');

            try {
                $value = $test->get($pointer);
            } catch (Pointer\NonexistentValueReferencedException $e) {
                $reportStatus = $reportStatus && false;

                $ruleReport[] = new RuleReport(
                    $rule,
                    false,
                    new RuleException($e->getMessage(), $rule->getPointer())
                );
                continue;
            }
            $value = json_decode(json_encode($value)); //array to object

            $validateResult = $rule->getSchema()->validate($value);

            $reportStatus = $reportStatus && $validateResult->status;

            $ruleReport[] = new RuleReport(
                $rule,
                $validateResult->status,
                $validateResult->exception ?? null
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
     * Set expected HTTP response Status Code
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     * @param  int|int[] $statusCode
     * @return $this
     * @deprecated 3.0.0
     */
    public function expectStatusCode($statusCode)
    {
        //Work with arrays, if single int is given
        if (!is_array($statusCode)) {
            $statusCode = [$statusCode];
        }

        return $this->expectRule(
            StatusCodeRule::fromEnum($statusCode)
        );
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
    public function expectRule(Rule $rule) : AbstractTestphase
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
