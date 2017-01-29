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

use Phramework\Testphase\Rule\Rule;


/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 2.0.0
 */
class RuleReport implements \JsonSerializable
{
    /**
     * @var Rule
     */
    private $rule;

    /**
     * @var boolean
     */
    private $status;

    /**
     * @var \Exception|null
     */
    private $error;

    /**
     * RuleReport constructor.
     * @param Rule $rule
     * @param boolean $status
     * @param \Exception|null $error
     */
    public function __construct(
        Rule $rule,
        bool $status,
        \Exception $error = null
    ) {
        $this->rule   = $rule;
        $this->status = $status;
        $this->error  = $error;
    }

    /**
     * @return Rule
     */
    public function getRule(): Rule
    {
        return $this->rule;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    /**
     * @return \Exception|null
     */
    public function getError()
    {
        return $this->error;
    }

    public function jsonSerialize()
    {
        $vars = [
            'rule'   => $this->rule,
            'status' => $this->status
        ];

        if (!empty($this->error) && $this->error) {
            $vars['error'] = $this->error;
        }

        return $vars;
    }
}
