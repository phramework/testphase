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
class BodyRule extends Rule
{
    public function __construct(
        string $pointer,
        BaseValidator $schema,
        string $message = null
    ) {
        if (!Util::startsWith($pointer, static::ROOT_BODY)) {
            $pointer = str_replace(
                '//',
                '/',
                static::ROOT_BODY . '/' . $pointer
            );
        }

        parent::__construct($pointer, $schema, $message);
    }
}
