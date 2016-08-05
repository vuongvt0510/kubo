<?php

class CIUnit_Api_Constraint_IsSubmitError extends CIUnit_Api_Constraint
{
    protected function matches($other)
    {
        return $other['success'] === TRUE && $other['submit'] === FALSE;
    }

    public function toString()
    {
        return 'is submit error response';
    }
}

