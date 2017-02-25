<?php
require 'vendor/autoload.php';
require_once 'AssertThrows.php';
require_once 'CallableLogicalNot.php';
require_once 'Eventually.php';

/**
 * TestCase implementation that incorporates many of the extensions in
 * tests/support.
 */
abstract class TestCase_ext extends PHPUnit_Framework_TestCase
{
    /**
     * @param string $exceptionClass Class name of the exception to be tested
     * against. Optional; if not provided, the Constraint will match against any
     * exception.
     * @param string $excMessage Exception message. Optional.
     * @param mixed $code Exception code. Optional.
     *
     * @return PHPUnit_Framework_Constraint
     */
    public function throws($exceptionClass = NULL,
        $excMessage = NULL, $code = NULL)
    {
        return new AssertThrows($exceptionClass, $excMessage, $code);
    }

    /**
     * @param callable $test Code to be asserted for exceptions
     * @param string $exceptionClass Class name of the exception to be tested
     * against. Optional; if not provided, the Constraint will match against any
     * exception.
     * @param string $excMessage Exception message. Optional.
     * @param mixed $code Exception code. Optional.
     */
    public function assertThrows($test, $exceptionClass,
        $excMessage = NULL, $code = NULL)
    {
        $this->assertThat($test, $this->throws($exceptionClass, $excMessage, $code));
    }

    /**
     * @param string $exceptionClass Class name of the exception to be tested
     * against. Optional; if not provided, the Constraint will match against any
     * exception.
     * @param string $excMessage Exception message. Optional.
     * @param mixed $code Exception code. Optional.
     *
     * @return PHPUnit_Framework_Constraint
     */
    public function doesNotThrow($exceptionClass = NULL,
        $excMessage = NULL, $code = NULL)
    {
        return new CallableLogicalNot($this->throws($exceptionClass, $excMessage, $code));
    }

    /**
     * @param callable $test Code to be asserted for lack of exceptions
     */
    public function assertDoesNotThrow($test)
    {
        $this->assertThat($test, $this->doesNotThrow());
    }

    /**
     * @param PHPUnit_Framework_Constraint $constraint The constraint to be
     * matched repeatedly (with exponential backoff) until it actually matches,
     * or until the maximum number of retries is exceeded.
     *
     * @return PHPUnit_Framework_Constraint
     */
    public function eventually(PHPUnit_Framework_Constraint $constraint)
    {
        return new Eventually($constraint);
    }

    /**
     * @param PHPUnit_Framework_Constraint $constraint Same as above
     * @param callable $test Code to be asserted for exceptions
     */
    public function assertEventually(PHPUnit_Framework_Constraint $constraint, $test)
    {
        $this->assertThat($test, $this->eventually($constraint));
    }
}
?>
