<?php
require 'vendor/autoload.php';
require_once 'CallableConstraint.php';

/**
 * A version of PHPUnit_Framework_Constraint_Not that implements
 * CallableConstraint. Useful if the inverse of a CallableConstraint is to be
 * used in the Eventually constraint.
 *
 * TODO: Override LogicalNot::negate to support Eventually
 */
class CallableLogicalNot extends PHPUnit_Framework_Constraint_Not implements CallableConstraint
{
    public function __construct($constraint)
    {
        parent::__construct($constraint);
    }
}
?>
