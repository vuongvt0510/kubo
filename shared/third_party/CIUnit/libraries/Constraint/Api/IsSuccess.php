<?php


class CIUnit_Api_Constraint_IsSuccess extends CIUnit_Api_Constraint
{
    protected function matches($other)
    {
        return $other['success'] === TRUE && $other['submit'] === TRUE;
    }

    public function toString()
    {
        return 'is successful response';
    }
}

