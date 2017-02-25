<?php
require 'vendor/autoload.php';
require_once 'CallableConstraint.php';

/**
 * A Constraint that matches if invoking the callable throws an exception
 * matching the parameters provided.
 *
 * The logic in this class is derived from the raise_errors matcher in RSpec.
 */
class AssertThrows extends PHPUnit_Framework_Constraint implements CallableConstraint
{
    /**
     * @var Exception
     */
    protected $exception;

    /**
     * @var string
     */
    protected $message;

    protected $code;

    /**
     * @param $exceptionClass Class name of the exception to be tested
     * against. Optional; if not provided, the Constraint will match against any
     * exception.
     * @param $excMessage Exception message. Optional.
     * @param mixed $code Exception code. Optional.
     */
    public function __construct($exceptionClass = NULL, $message = NULL, $code = NULL)
    {
        parent::__construct();
        $this->exceptionClass = $exceptionClass;
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * @param callable $test
     *
     * @return boolean
     */
    protected function matches($test)
    {
        if (!is_callable($test)) {
            throw new InvalidArgumentException('Can only match with callables');
        }

        try {
            $test();
        } catch (Exception $e) {
            error_log("Exception " . get_class($e) . " with message {$e->getMessage()} was thrown");
            if ($this->exceptionMatches($e)) {
                return true;
            }
        }
        return false;
    }

    private function exceptionMatches(Exception $thrown)
    {
        if (!is_null($this->exceptionClass) && $this->exceptionClass !== get_class($thrown)) {
            return false;
        }

        if (!is_null($this->message) && strpos($thrown->getMessage(), $this->message) === false) {
            return false;
        }

        if (!is_null($this->code) && $this->code !== $thrown->getCode()) {
            return false;
        }

        return true;
    }

    public function toString()
    {
        return 'throws exception ' . $this->exceptionClass;
    }
}
