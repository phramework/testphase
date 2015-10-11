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

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenophon Spafaridis <nohponex@gmail.com>
 */
class Testphase
{
    /**
     * Base API url
     * @var string
     */
    private static $base = 'http://localhost/ostomate/api/';

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

    private $requestBody;

    private $ruleStatusCode = 200;

    private $ruleHeaders = [];

    private $ruleObjects = [];

    private $ruleJSON = false;

    private $inspectOnFailure = false;

    public function __construct(
        $url,
        $method = Phramework::METHOD_GET,
        $headers = [],
        $requestBody = null,
        $ruleJSON = false
    ) {
        $this->url = $url;

        if (!in_array($method, Phramework::$methodWhitelist, true)) {
            throw new \Exception('Unsupported method');
        }

        $this->method = $method;

        $this->headers = $headers;

        //not for GET
        $this->requestBody = $requestBody;

        $this->ruleJSON = $ruleJSON;
    }

    private $responseStatusCode;
    private $responseHeaders;
    private $responseBody;

    /**
     * Handle renspose
     * @param  [type] $responseStatusCode [description]
     * @param  [type] $responseHeaders    [description]
     * @param  [type] $responseBody       [description]
     * @param  callable|null
     * @return [type]                     [description]
     */
    private function handle($responseStatusCode, $responseHeaders, $responseBody, $callback)
    {
        if ($responseStatusCode != $this->ruleStatusCode) {
            throw new \Exception(sprintf(
                'Expected status code %s got %s',
                $this->ruleStatusCode,
                $responseStatusCode
            ));
        }

        foreach ($this->ruleHeaders as $headerKey => $headerValue) {
            if (!isset($responseHeaders[$headerKey])) {
                throw new \Exception(sprintf(
                    'Expected header %s is not set',
                    $headerKey
                ));
            }

            if ($headerValue != $responseHeaders[$headerKey]) {
                throw new \Exception(sprintf(
                    'Expected header value %s for header %s got %s',
                    $headerValue,
                    $headerKey,
                    $responseHeaders[$headerKey]
                ));
            }
        }

        if ($this->ruleJSON && !Testphase::isJSON($responseBody)) {
            throw new \Exception(sprintf(
                'Expected valid JSON response Body'
            ));
        }

        //Add extra rules ??
        if ($this->ruleJSON) {
            $responseBodyObject = json_decode($responseBody, true);

            foreach ($this->ruleObjects as $ruleObject) {
                $ruleObject->parse($responseBodyObject);
            }
        }

        if ($callback) {
            if ($this->ruleJSON) {
                call_user_func(
                    $callback,
                    $responseStatusCode,
                    $responseHeaders,
                    $responseBody,
                    $responseBodyObject
                );
            } else {
                call_user_func(
                    $callback,
                    $responseStatusCode,
                    $responseHeaders,
                    $responseBody
                );
            }
        }

        return true;
    }

    /**
     * Run testphase
     * Will execute the request and apply all defined rules to validate the response
     * @param  callable|null $callback [Optional] Callback to execute after
     * completing the test rules
     * @return true On success
     */
    public function run($callback = null)
    {
        $flags = self::REQUEST_EMPTY_FLAG;

        //Construct request url
        $url = static::$base . $this->url;

        //Is the request binary
        $binary = ($flags & self::REQUEST_BINARY) != 0;

        //If the request paramters form encoded
        $form_encoded = !(($flags & self::REQUEST_NOT_URL_ENCODED) != 0);

        //Initialize curl
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        //curl_setopt($curl, CURLOPT_VERBOSE, true);
        //Set timeout values ( in seconds )
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, static::$SETTING_CURLOPT_CONNECTTIMEOUT);
        curl_setopt($curl, CURLOPT_TIMEOUT, static::$SETTING_CURLOPT_TIMEOUT);
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
            case Phramework::METHOD_GET: //On METHOD_GET
            case Phramework::METHOD_HEAD: //On METHOD_HEAD
                break;
            case Phramework::METHOD_POST: //On METHOD_POST
                curl_setopt($curl, CURLOPT_POST, true);

                if ($this->$requestBody && $form_encoded) { //Encode fields if required ( URL ENCODED )
                    curl_setopt(
                        $curl,
                        CURLOPT_POSTFIELDS,
                        http_build_query($this->$requestBody)
                    );
                } elseif ($this->$requestBody) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $this->$requestBody);
                }
                break;
            case Phramework::METHOD_PUT: //On METHOD_PUT
            case Phramework::METHOD_PATCH: //On METHOD_PATCH
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->$requestBody);
                break;
            case Phramework::METHOD_DELETE: //On METHOD_DELETE
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, Phramework::METHOD_DELETE);
                break;
            default:
                throw new \Exception('Unsupporter method');
        }

        //Get response
        $response = curl_exec($curl);
        //Get response code
        $responseStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $responseHeadersTemp = str_replace("\r", "", substr($response, 0, $headerSize));
        $responseHeaders = [];
        foreach (explode("\n", $responseHeadersTemp) as $i => $line) {
            if ($i !== 0 && !empty($line)) {
                list($key, $value) = explode(': ', $line);

                $responseHeaders[$key] = $value;
            }
        }

        $responseBody = substr($response, $headerSize);

        curl_close($curl);

        $this->responseStatusCode = $responseStatusCode;
        $this->responseHeaders = $responseHeaders;
        $this->responseBody = $responseBody;

        return $this->handle($responseStatusCode, $responseHeaders, $responseBody, $callback);
    }

    /**
     * Set expected HTTP response Status Code
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     * @param  Integer $statusCode
     * @return Testphase Return's $this object
     */
    public function expectStatusCode($statusCode)
    {
        $this->ruleStatusCode = $statusCode;

        return $this;
    }

    /**
     * Add expected response header
     * @param  array[] $ruleHeaders
     * @return Testphase Return's $this object
     */
    public function expectResponseHeader($ruleHeaders)
    {
        if (!is_array($ruleHeaders)) {
            throw new \Exception('Expecting array for method expectResponseHeader');
        }

        $this->ruleHeaders = array_merge(
            $this->ruleHeaders,
            $ruleHeaders
        );

        return $this;
    }

    public function expectJSON($flag = true)
    {
        $this->ruleJSON = $flag;

        return $this;
    }

    /**
     * Object validator, used to validate the response
     * @param  PhrameworkValidateObject $object Validator object
     * @return Testphase Return's $this object
     */
    public function expectObject(\Phramework\Validate\Object $object)
    {
        $this->ruleObjects[] = $object;

        return $this;
    }


    public static function isJSON($string)
    {
        return (is_string($string)
            && is_object(json_decode($string))
            && (json_last_error() == JSON_ERROR_NONE)) ? true : false;
    }

    public static function setAuthorizationBasic($username, $password)
    {
        throw new \Phramework\Exceptions\NotImplementedExceptions();

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
     * @var integer
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
     * @var integer
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
     * GEt base API url
     * @return string
     */
    public static function getBase()
    {
        return static::$base;
    }
}
