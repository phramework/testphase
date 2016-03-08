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

use Phramework\Testphase\Exceptions\HeaderException;
use Phramework\Testphase\Exceptions\RuleException;
use Phramework\Testphase\Report\Request;
use Phramework\Testphase\Report\Response;
use Phramework\Testphase\Report\RuleReport;
use Phramework\Testphase\Report\StatusReport;
use Phramework\Validate\BaseValidator;
use Rs\Json\Pointer;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @version 2.0.0
 */
class Testphase
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
     * Request url, not including the base part
     * @var string
     */
    private $url;

    /**
     * Request HTTP headers
     * @var array
     */
    private $headers;

    /**
     * Request HTTP method
     * @var string
     */
    private $method;

    /**
     * @var string|null
     */
    private $body;

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
     * @var Rule\Rule[]
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
     * @var boolean
     */
    private $inspectOnFailure = false;

    /**
     * @var int
     */
    private $responseStatusCode;

    /**
     * @var array
     */
    private $responseHeaders;

    /**
     * @var string
     */
    private $responseBody;

    /**
     * @param string      $url
     *     Request url, without the base part, (see setBase method)
     * @param string      $method      *[Optional]* HTTP request method
     * @param array       $headers     *[Optional]* HTTP request headers
     * @param string|null $body *[Optional]* HTTP request body
     * @param boolean     $ruleJSON    *[Optional]* Response rule, expect JSON encoded response body
     * @throws \Phramework\Exceptions\IncorrectParametersException When method is not correct
     */
    public function __construct(
        $url,
        $method = 'GET',
        $headers = [],
        $body = null,
        $ruleJSON = false
    ) {
        $this->url = $url;

        if (!is_string($method)) {
            throw new \Phramework\Exceptions\IncorrectParametersException(
                ['method'],
                'Method must be a string'
            );
        }

        $this->method = $method;

        $this->headers = $headers;

        //not for GET
        $this->body = $body;

        $this->ruleJSON = $ruleJSON;

        $this->rules = [];
    }

    /**
     * Run testphase
     * Will execute the request and apply all defined rules to validate the response
     * @param  callable|null $callback *[Optional]* Callback to execute after
     * completing the test rules
     * @return StatusReport
     * @uses self::$base When url does not contain schema use base as prefix.
     * @throws \Exception
     */
    public function run($callback = null)
    {
        $flags = self::REQUEST_EMPTY_FLAG;

        //When url does not contain schema use base as prefix
        if (parse_url($this->url, PHP_URL_SCHEME) !== null) {
            $url = $this->url;
        } else {
            $url = static::$base . $this->url;
        }

        //Is the request binary
        $binary = ($flags & self::REQUEST_BINARY) != 0;

        //If the request parameters form encoded
        $form_encoded = false; //!(($flags & self::REQUEST_NOT_URL_ENCODED) != 0);

        $timeout = static::$SETTING_CURLOPT_TIMEOUT;
        if ($this->timeout !== null) {
            $timeout = $this->timeout;
        }

        //Initialize curl
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        //curl_setopt($curl, CURLOPT_VERBOSE, true);
        //Set timeout values (in seconds)
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, static::$SETTING_CURLOPT_CONNECTTIMEOUT);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_NOSIGNAL, 1);

        //Security options
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        //On binary transfers
        if ($binary) {
            curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
        }

        //Switch on HTTP Request method
        switch ($this->method) {
            case 'GET': //On GET
            case 'HEAD': //On HEAD
                break;
            case 'POST': //On POST
                curl_setopt($curl, CURLOPT_POST, true);

                if ($this->body && $form_encoded) { //Encode fields if required (URL ENCODED)
                    curl_setopt(
                        $curl,
                        CURLOPT_POSTFIELDS,
                        http_build_query($this->body)
                    );
                } elseif ($this->body) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $this->body);
                }
                break;
            case 'PUT': //On PUT
            case 'PATCH': //On PATCH
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->body);
                break;
            case 'DELETE': //On DELETE
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            default:
                throw new \Exception('Unsupported method');
        }

        $start = time();
        //Get response
        $response = curl_exec($curl);
        $end = time();
        //Get response code
        $responseStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $responseHeadersTemp = str_replace("\r", '', substr($response, 0, $headerSize));
        $responseHeaders = [];

        foreach (explode("\n", $responseHeadersTemp) as $i => $line) {
            if ($i !== 0 && !empty($line)) {
                if (count($parts = explode(': ', $line)) === 2) {
                    list($key, $value) = explode(': ', $line);

                    $responseHeaders[$key] = $value;
                }
            }
        }

        //todo get timeout error CURLE_OPERATION_TIMEDOUT (28) https://curl.haxx.se/libcurl/c/libcurl-errors.html

        $responseBody = substr($response, $headerSize);

        curl_close($curl);

        $this->responseStatusCode = $responseStatusCode;
        $this->responseHeaders = $responseHeaders;
        $this->responseBody = $responseBody;

        return $this->handleResponse(
            new Response(
                $responseStatusCode,
                $responseHeaders,
                $responseBody,
                $end,
                ($end - $start)
            ),
            $start,
            $callback
        );
    }

    /**
     * Handle response, test response against provided rules
     * @param callable|null $callback
     * @throws \Exception
     * @return Report\StatusReport
     */
    private function handleResponse(
        Response $response,
        $start,
        $callback
    ) {
        $headers = $response->getHeaders();

        if (!in_array($response->getStatusCode(), $this->ruleStatusCode, true)) {
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

        $success = true;

        $body = $response->getBody();

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
                $pointer = trim('/body', $rule->getPointer());
                //try {
                //get value from pointer
                $value = $jsonPointer->get($pointer);

                //} catch (Pointer\NonexistentValueReferencedException $e) {
                //    //todo
                //    throw new RuleException($e->getMessage());
                //}

                if (is_subclass_of($rule->getSchema(), BaseValidator::class)) {
                    $validateResult = $rule->getSchema()->validate(json_decode($value));

                    $success = $success && $validateResult->status;

                    $ruleReport[] = new RuleReport(
                        $rule,
                        $validateResult->status,
                        $validateResult->errorObject
                    );
                }
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

        //Call callback
        if ($callback !== null) {
            call_user_func_array(
                $callback,
                $callbackArguments
            );
        }

        //Call global callbacks
        foreach (static::$globalCallbacks as $globalCallback) {
            call_user_func_array(
                $globalCallback,
                $callbackArguments
            );
        }

        return new Report\StatusReport(
            (
                $success === true
                ? Report\StatusReport::STATUS_SUCCESS
                : Report\StatusReport::STATUS_FAILURE
            ),
            new Request(
                $this->url,
                $this->method,
                $this->headers,
                $this->body,
                $start
            ),
            $response,
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
     * @param  boolean $flag *[Optional]* Value of the flag, default is true
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
     * @param Rule\Rule $rule
     * @return $this
     */
    public function expectRule(Rule\Rule $rule)
    {

        $this->rules[] = $rule;

        return $this;
    }

    const REQUEST_EMPTY_FLAG = 0;
    const REQUEST_BINARY = 1;
    const REQUEST_NOT_URL_ENCODED = 2;

    /**
     * Setting CURLOPT_CONNECTTIMEOUT - timeout for the connect phase
     * Pass a long. It should contain the maximum time in seconds that you allow
     * the connection phase to the server to take.
     * This only limits the connection phase, it has no impact once it has connected.
     * Set to zero to switch to the default built-in connection timeout - 300 seconds.
     * Default timeout is 300.
     * @see CURLOPT_CONNECTTIMEOUT
     * @var int
     */
    public static $SETTING_CURLOPT_CONNECTTIMEOUT = 300;

    /**
     * Setting CURLOPT_TIMEOUT - set maximum time the request is allowed to take
     *
     * Pass a long as parameter containing timeout - the maximum time in seconds
     * that you allow the libcurl transfer operation to take.
     * Normally, name lookups can take a considerable time and limiting operations
     * to less than a few minutes risk aborting perfectly normal operations.
     * This option may cause libcurl to use the SIGALRM signal to timeout system calls.
     * Default timeout is 0 (zero) which means it never times out during transfer.
     * @see CURLOPT_TIMEOUT
     * @var int
     */
    public static $SETTING_CURLOPT_TIMEOUT = 0;

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
     * Get the value of Response Status Code
     * @return mixed
     */
    public function getResponseStatusCode()
    {
        return $this->responseStatusCode;
    }

    /**
     * Get the value of Response Headers
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * Get the value of Response Body
     * @return string
     */
    public function getResponseBody()
    {
        return $this->responseBody;
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

        return '2.0.0-dev';
    }
}
