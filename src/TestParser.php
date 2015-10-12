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
use \Phramework\Validate\Integer;
use \Phramework\Validate\UnsignedInteger;
use \Phramework\Validate\ArrayValidator;
use \Phramework\Validate\Enum;
use \Phramework\Validate\String;

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenophon Spafaridis <nohponex@gmail.com>
 */
class TestParser
{

    /**
     * Parsed test
     * @var Object
     */
    protected $test;

    public function __construct($filename)
    {
        if (!file_exists($filename)) {
            throw new \Exception(sprintf(
                'File %s doesn\'t exist',
                $filename
            ));
        }

        $contents = file_get_contents($filename);

        //Check if contents are a valid jsonfile
        if (!Testphase::isJSON($contents)) {
            throw new \Exception(sprintf(
                'File %s isn\'t a valid JSON file',
                $filename
            ));
        }


        $object = json_decode($contents);

        $validatorRequest = new Object(
            [
                'url' => new String(1, 1024),
                'method' => (new Enum(Phramework::$methodWhitelist, true))
                    ->setDefault(Phramework::METHOD_GET),
                'headers' => (new ArrayValidator())
                    ->setDefault([])
            ],
            ['url']
        );
        $validatorResponse = new Object(
            [
                'statusCode' => new UnsignedInteger(100,999)
            ],
            ['statusCode']
        );

        //Setup validator for parsed test
        $validator = new Object(
            [
                'order' => (new Integer(-99999999,-99999999))
                    ->setDefault(0),
                'request' => $validatorRequest,
                'response' => $validatorResponse
            ],
            ['request', 'response']
        );

        echo 'validator:' . PHP_EOL;
        echo $validator->toJSON(true);
        echo PHP_EOL;

        $this->test = $validator->parse($object);

        var_dump($this->test);
    }

}
