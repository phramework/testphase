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

class GlobalsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Phramework\Testphase\Globals::set
     *
     */
    public function testSet()
    {
        Globals::set('myVariable', 5);

        $this->assertSame(5, Globals::get('myVariable'));

        Globals::set(
            'dots',
            function ($length = 4) {
                return str_repeat('.', $length);
            }
        );
        $this->assertSame(
            '....',
            Globals::get('dots()')
        );

        $this->assertSame(
            '.....',
            Globals::get('dots(5)')
        );
    }

    /**
     * @covers Phramework\Testphase\Globals::set
     * @expectedException Exception
     */
    public function testSetFailure1()
    {
        Globals::set('myVariable()', 5);
    }

    /**
     * @covers Phramework\Testphase\Globals::set
     * @expectedException Exception
     */
    public function testSetFailure2()
    {
        Globals::set('myVariable[]', 5);
    }

    /**
     * @covers Phramework\Testphase\Globals::set
     * @expectedException Exception
     */
    public function testSetFailure3()
    {
        Globals::set('0123', 5);
    }

    /**
     * @covers Phramework\Testphase\Globals::set
     *
     */
    public function testSetArray()
    {
        $array = [1, 3, 5, 7, 11, 13];

        Globals::set('array', $array);

        $this->assertTrue(Globals::exists('array'));

        return $array;
    }

    /**
     * @covers Phramework\Testphase\Globals::get
     */
    public function testGet()
    {
        $globals = Globals::get();

        $this->assertInternalType('object', $globals);
    }

    /**
     * @covers Phramework\Testphase\Globals::get
     * @expectedException Exception
     */
    public function testGetFailure1()
    {
        Globals::get('0123');
    }

    /**
     * @covers Phramework\Testphase\Globals::get
     * @expectedException Exception
     */
    public function testGetFailure2()
    {
        Globals::get('myFunc{1}');
    }

    /**
     * @covers Phramework\Testphase\Globals::get
     * @expectedException \Phramework\Testphase\Exceptions\UnsetGlobalException
     */
    public function testGetFailure3()
    {
        Globals::get('NotFoundXXXSADASDASDAS');
    }

    /**
     * @covers Phramework\Testphase\Globals::get
     */
    public function testGetVariableAsArgument1()
    {
        $variableSize = '10';

        Globals::set('variableSize', $variableSize);

        $return = Globals::get('rand-string(variableSize)');

        $this->assertSame(mb_strlen($return), (int)$variableSize);
    }

    /**
     * @covers Phramework\Testphase\Globals::get
     */
    public function testGetVariableAsArgument2()
    {
        Globals::set('echo', function ($value) {
            return $value;
        });

        $echoVariable = Globals::get('rand-string()');

        $return = Globals::get(sprintf(
            'echo("%s")', //literal
            $echoVariable
        ));

        $this->assertSame($echoVariable, $return);

        Globals::set('echoVariable', $echoVariable);

        $return = Globals::get(sprintf(
            'echo(%s)', //literal
            'echoVariable'
        ));

        $this->assertSame($echoVariable, $return);

    }

    /**
     * @depends testSetArray
     * @params int[] $array
     * @covers Phramework\Testphase\Globals::get
     */
    public function testGetArray($array)
    {
        //Get array
        $return = Globals::get('array');
        $this->assertInternalType('array', $return);

        $this->assertEquals($array, $return);

        //Get element
        $return = Globals::get('array[0]');
        $this->assertInternalType('integer', $return);

        $this->assertSame($array[0], $return);

        //Get element
        $return = Globals::get('array[3]');
        $this->assertInternalType('integer', $return);

        $this->assertSame($array[3], $return);
    }

    /**
     * @covers Phramework\Testphase\Globals::get
     */
    public function testGetRandString()
    {
        $return = Globals::get('rand-string');

        $this->assertInternalType(
            'callable',
            $return,
            'Expect rand-string() to return a function'
        );

        $return = Globals::get('rand-string(6)');

        $this->assertInternalType('string', $return);
        $this->assertSame(
            6,
            strlen($return),
            'Expect same length as given length parameter'
        );

        $this->assertSame(
            5,
            strlen(Globals::get('rand-string(5)')),
            'Expect same length as given length parameter'
        );

        $this->assertNotEquals(
            Globals::get('rand-string()'),
            Globals::get('rand-string()'),
            'Expect different value for each call to random'
        );
    }

    /**
     * @covers Phramework\Testphase\Globals::get
     *
     */
    public function testGetRandInteger()
    {
        $return = Globals::get('rand-integer');

        $this->assertInternalType(
            'callable',
            $return,
            'Expect rand-integer to return a function'
        );

        $return = Globals::get('rand-integer()');
        $this->assertInternalType(
            'integer',
            $return,
            'Expect rand-integer() to return an integer'
        );

        $this->assertSame(0, Globals::get('rand-integer(0)'));

        $this->assertNotEquals(
            Globals::get('rand-integer()'),
            Globals::get('rand-integer()'),
            'Expect different value for each call to random'
        );
    }

    /**
     * @covers Phramework\Testphase\Globals::get
     *
     */
    public function testGetTimestamp()
    {
        $return = Globals::get('timestamp');

        $this->assertInternalType(
            'callable',
            $return,
            'Expect to return a function'
        );

        $return = Globals::get('timestamp()');

        $this->assertInternalType(
            'integer',
            $return,
            'Expect to return integer'
        );
    }

    /**
     * @covers Phramework\Testphase\Globals::exists
     */
    public function testExists()
    {
        $this->assertFalse(Globals::exists('NotFoundXXXSADASDASDAS'));
        $this->assertFalse(Globals::exists('NotFoundXXXSADASDASDAS[]'));

        $this->assertTrue(Globals::exists('rand-integer'));
    }

    /**
     * @covers Phramework\Testphase\Globals::initializeGlobals
     */
    public function testInitializeGlobals()
    {
    }
}
