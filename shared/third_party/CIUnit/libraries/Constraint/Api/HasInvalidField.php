<?php

class CIUnit_Api_Constraint_HasInvalidField extends CIUnit_Api_Constraint
{
    protected $field = NULL;
    protected $rule = NULL;

    public function __construct($field, $rule = NULL)
    {
        $this->field = $field;
        $this->rule = $rule;
    }

    protected function matches($other)
    {
        if (!array_key_exists('invalid_fields', $other)) {
            return FALSE;
        }

        if (!array_key_exists($this->field, $other['invalid_fields'])) {
            return FALSE;
        }

        // TODO: ruleに基づいたエラーメッセージのチェックができれば良いかな

        return TRUE;
    }

    public function toString()
    {
        return "has {$this->field} in invalid_fields";
    }
}

