<?php
require 'vendor/autoload.php';
require_once 'CallableConstraint.php';

/**
 * A Constraint that eventually matches.
 *
 * Use this constraint to retry test code (with exponential backoff). This is
 * useful for full-stack acceptance testing that sometimes have problems with
 * transient failures.
 */
class Eventually extends PHPUnit_Framework_Constraint implements CallableConstraint
{
    /**
     * @var PHPUnit_Framework_Constraint
     */
    protected $constraint;

    /**
     * @var int
     */
    protected $maxAttempts;

    public function __construct(PHPUnit_Framework_Constraint $constraint,
        $maxAttempts = 6)
    {
        parent::__construct();
        $this->constraint = $constraint;
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Attempt to evaluate the provided callable test code, up to a maximum
     * number of attempts. Returns true if the test code eventually succeeds;
     * otherwise returns false if the test code never succeeds.
     */
    protected function matches($test)
    {
        if (!is_callable($test)) {
            throw new InvalidArgumentException('Can only match with callables');
        }

        // If the constraint accepts a callable, then just pass the arg as-is
        $constraint = $this->constraint;
        if ($constraint instanceof CallableConstraint) {
            return self::attempt($this->maxAttempts, function() use ($test, $constraint) {
                try {
                    return $constraint->evaluate($test, '', true);
                } catch (PHPUnit_Framework_ExpectationFailedException $e) {
                    return false;
                }
            });
        } else {
            // Otherwise, evaluate the callable
            return self::attempt($this->maxAttempts, function() use ($test, $constraint) {
                try {
                    return $constraint->evaluate($test(), '', true);
                } catch (PHPUnit_Framework_ExpectationFailedException $e) {
                    return false;
                }
            });
        }
    }

    /**
     * @param int $totalAttempts Maximum attempts to call $fn
     * @param callable $fn Code to attempt multiple times
     */
    private static function attempt($totalAttempts, $fn)
    {
        # 1.75 ^ (0..5) == [1.0, 1.75, 3.0625, 5.359375, 9.37890625, 16.4130859375]
        # Subtract 2 attempts because first attempt is sleep(0)
        $sleepTimes = array_map(function ($time) {
            return pow(1.75, $time);
        }, range(0, $totalAttempts - 2));
        array_unshift($sleepTimes, 0);

        foreach (range(0, $totalAttempts - 1) as $attempt) {
            error_log("Attempt ". ($attempt + 1) ."/{$totalAttempts}: sleep({$sleepTimes[$attempt]} s)");
            // Cannot actually sleep for fractional seconds, so we use usleep
            usleep($sleepTimes[$attempt] * 1000000);

            $result = $fn();
            if ($result) {
                return $result;
            }
        }

        return false;
    }

    public function toString()
    {
        return 'eventually ' . $this->constraint->toString();
    }
}

?>
