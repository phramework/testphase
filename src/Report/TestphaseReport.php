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

use Phramework\Testphase\HTTPResponse;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 2.0.0
 */
class TestphaseReport implements \JsonSerializable
{
    const STATUS_SUCCESS    = 'success';
    const STATUS_ERROR      = 'error';
    const STATUS_FAILURE    = 'failure';
    const STATUS_INCOMPLETE = 'incomplete';
    const STATUS_IGNORE     = 'ignore';

    /**
     * @var string
     */
    private $status;

    /**
     * @var ResponseReport
     */
    private $responseReport;

    /**
     * @var RequestReport
     */
    private $requestReport;

    /**
     * @var RuleReport[]
     */
    private $ruleReport;

    /**
     * Raw Response
     * @var HTTPResponse
     */
    private $response;

    /**
     * StatusReport constructor.
     * @param HTTPResponse   $response Raw response
     * @param string         $status
     * @param RequestReport  $requestReport
     * @param ResponseReport $responseReport
     * @param array          $ruleReport
     */
    public function __construct(
        HTTPResponse $response,
        string $status,
        RequestReport $requestReport,
        ResponseReport $responseReport,
        array $ruleReport = []
    ) {
        $this->response       = $response;
        $this->status         = $status;
        $this->responseReport = $requestReport;
        $this->responseReport = $responseReport;
        $this->ruleReport     = $ruleReport;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return ResponseReport
     */
    public function getResponseReport()
    {
        return $this->rrequestReport;
    }

    /**
     * @return RequestReport
     */
    public function getRequestReport()
    {
        return $this->requestReport;
    }

    /**
     * @return RuleReport[]
     */
    public function getRuleReport(): array
    {
        return $this->ruleReport;
    }

    /**
     * Get raw response
     * @return HTTPResponse
     */
    public function getResponse(): HTTPResponse
    {
        return $this->response;
    }

    public function jsonSerialize()
    {
        return [
            'status'     => $this->status,
            'request'    => $this-$this->responseReport,
            'response'   => $this->responseReport,
            'ruleReport' => $this->ruleReport
        ];
    }
}
