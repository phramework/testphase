<?php
/*
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
 * @version 3.0.0
 */
class Rule implements \JsonSerializable
{
    const ROOT_HEADER      = '/header';
    const ROOT_BODY        = '/body';
    const ROOT_STATUS_CODE = '/statusCode';
    const ROOT_TIMEOUT     = '/timeout';

    const ROOT = [
        self::ROOT_HEADER,
        self::ROOT_BODY,
        self::ROOT_STATUS_CODE,
        self::ROOT_TIMEOUT,
    ];

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
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $pointer,
        BaseValidator $schema,
        string $message = null
    ) {
        $found = false;
        foreach (static::ROOT as $member) {
            $found = Util::startsWith($pointer, $member);
            if ($found) {
                break;
            }
        }

        if (!$found) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid pointer, must start with one of %s',
                implode(',', static::ROOT)
            ));
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