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

class BinaryTest extends \PHPUnit_Framework_TestCase
{
    protected $binary;

    public function setUp()
    {
        Globals::set('getIds', [7, 6]);

        $argv = [
            __FILE__,
            '-d',
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests'
        ];

        $this->binary = new Binary($argv);
    }

    /**
     * @covers Phramework\Testphase\Binary::__construct
     */
    public function testConstruct()
    {
        $argv = [
            __FILE__,
            '-d',
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests'
        ];

        $binary = new Binary($argv);
    }

    /**
     * @covers Phramework\Testphase\Binary::invoke
     */
    public function testInvoke()
    {
        $this->binary->invoke();
    }

    /**
     * @covers Phramework\Testphase\Binary::getArgumentSpecifications
     */
    public function getArgumentSpecifications()
    {
        $specifications = getArgumentSpecifications();

        $this->assertInstanceOf(
            \GetOptionKit\OptionCollection::class,
            $specifications
        );
    }
}
