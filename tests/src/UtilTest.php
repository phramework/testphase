<?php

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
}
