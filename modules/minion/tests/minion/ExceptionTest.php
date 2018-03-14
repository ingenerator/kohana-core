<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

class Minion_ExceptionTest extends Kohana_Unittest_TestCase
{

    public function test_format_for_cli_defaults_to_kohana_exception_text()
    {
        $e = new Minion_Exception('Anything');
        $this->assertEquals(Kohana_Exception::text($e), $e->format_for_cli());
    }
}
