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
 * @since 2.0.0
 */
class StatusReport implements \JsonSerializable
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
     * @var Response
     */
    private $response;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var RuleReport[]
     */
    private $ruleReport;

    /**
     * StatusReport constructor.
     * @param string $status
     * @param Request $request
     * @param Response $response
     * @param array $ruleReport
     */
    public function __construct(
        $status,
        Request $request,
        Response $response,
        array $ruleReport = []
    ) {
        $this->status     = $status;
        $this->request    = $request;
        $this->response   = $response;
        $this->ruleReport = $ruleReport;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function jsonSerialize()
    {
        return [
            'status'     => $this->status,
            'request'    => $this->request,
            'response'   => $this->response,
            'ruleReport' => $this->ruleReport
        ];
    }
}
