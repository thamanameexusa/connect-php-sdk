<?php
require 'vendor/autoload.php';

/**
 * An empty interface for Constraints that match against invoking callables.
 * This is mainly useful for the Eventually constraint, which distinguishes
 * between CallableConstraints and Constraints that only work on values.
 */
interface CallableConstraint
{
}
?>
