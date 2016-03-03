<?php
/**
 * Copyright 2015-2016 Xenofon Spafaridis
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
namespace Phramework\Testphase\Report;


/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @todo add index ??
 */
class Request implements \JsonSerializable
{
    /**
     * @var string
     */
    private $url;

    private $headers;

    /**
     * HTTP request method
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $body;

    /**
     * Request timestamp
     * @var int
     */
    private $timestamp;

    public function __construct(
        $url,
        $method,
        $headers,
        $body,
        $timestamp
    ) {
        $this->url       = $url;
        $this->method    = $method;
        $this->headers   = $headers;
        $this->body      = $body;
        $this->timestamp = $timestamp;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
