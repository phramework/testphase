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


namespace Phramework\Testphase\Rule;

use Phramework\Util\Util;
use Phramework\Validate\BaseValidator;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 2.0.0
 */
class Rule implements \JsonSerializable
{
    /**
     * @var string
     */
    private $pointer;

    /**
     * @var BaseValidator
     */
    private $schema;

    /**
     * @var string
     */
    private $message;

    /**
     * @param string $pointer JSON pointer
     * @param BaseValidator $schema
     * @param string $message
     * @throws \Exception
     */
    public function __construct(
        string $pointer,
        BaseValidator $schema,
        string $message = ''
    ) {
        $topMembers = ['header/', 'body/', 'statusCode/'];

        $found = false;
        foreach ($topMembers as $member) {
            $found = $found || Util::startsWith($pointer, '/' . $member);
        }

        if (!$found) {
            throw new \Exception(
                'Invalid pointer, must start with one of ' . implode(',', $topMembers)
            );
        }

        $this->pointer = $pointer;
        $this->schema  = $schema;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getPointer()
    {
        return $this->pointer;
    }

    /**
     * @return BaseValidator
     */
    public function getSchema()
    {
        return $this->schema;
    }

    public function jsonSerialize()
    {
        $vars = [
            'pointer' => $this->pointer,
            'schema'  => $this->schema
        ];

        if (!empty($this->message)) {
            $vars['message'] = $this->message;
        }

        return $vars;
    }
}