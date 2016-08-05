<?php

abstract class CIUnit_Api_Constraint extends PHPUnit_Framework_Constraint
{                            
    protected function additionalFailureDescription($other)
    {
        return 'response is ' . PHPUnit_Util_Type::export($other);
    }

    protected function failureDescription($other)
    {
        return 'api response ' . $this->toString();
    }
}

