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

class UtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Phramework\Testphase\Util::isJSON
     */
    public function testIsJSON()
    {
        $this->assertTrue(
            Util::isJSON('{"object" : "OK"}')
        );

        $this->assertTrue(
            Util::isJSON('["ok", "y" ]')
        );

        $this->assertTrue(
            Util::isJSON('"ok"')
        );

        $this->assertTrue(
            Util::isJSON('5')
        );

        $this->assertFalse(
            Util::isJSON('{"object" : "OK"}warning')
        );
    }

    /**
     * @covers Phramework\Testphase\Util::readableRandomString
     */
    public function testReadableRandomString()
    {
        $this->assertSame(
            5,
            strlen(Util::readableRandomString(5)),
            'Expect same length as given length parameter'
        );

        $this->assertSame(
            6,
            strlen(Util::readableRandomString(6)),
            'Expect same length as given length parameter'
        );
    }

    /**
     * @covers Phramework\Testphase\Util::directoryToArray
     */
    public function testDirectoryToArray()
    {
        $files = Util::directoryToArray(
            __DIR__,
            false,
            false,
            true,
            '',
            ['php'],
            true //Relative paths
        );

        $this->assertInternalType('array', $files);

        $this->assertContains(
            basename(__FILE__),
            $files,
            'Current file must be in array'
        );


        $files = Util::directoryToArray(
            __DIR__,
            false,
            false,
            true,
            '',
            ['php', 'html'],
            false //Absolut paths
        );

        $this->assertInternalType('array', $files);

        $this->assertContains(
            __FILE__,
            $files,
            'Current file must be in array'
        );
    }

    /**
     * @covers Phramework\Testphase\Util::cartesian
     */
    public function testCartesian()
    {
        $input = [
            'arm' => ['A', 'B', 'C']
        ];

        $return = Util::cartesian($input);

        $this->assertInternalType('array', $return);

        $this->assertCount(3, $return);

        $input = [
            'arm' => ['A', 'B', 'C'],
            'gender' => ['Female', 'Male'],
            'location' => ['Vancouver', 'Calgary'],
        ];

        $return = Util::cartesian($input);

        $this->assertInternalType('array', $return);
        $this->assertCount(3*2*2, $return);

        $this->assertInternalType('array', $return[0]);

        $this->assertCount(count($input), $return[0]);
    }
}
