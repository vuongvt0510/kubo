<?php

class CIUnit_Api_Constraint_IsError extends CIUnit_Api_Constraint
{
    protected $errno = NULL;
    protected $errmsg = NULL;

    public function __construct($errno, $errmsg)
    {
        $this->errno = $errno;
        $this->errmsg = $errmsg;
    }

    protected function matches($other)
    {
        if (!($other['success'] === FALSE && $other['submit'] === FALSE)) {
            return FALSE;
        }

        if (!is_null($this->errno)) {
            if ($other['errno'] != $this->errno) {
                return FALSE;
            }
        }

        if (!is_null($this->errmsg)) {
            if ($other['errmsg'] != $this->errmsg) {
                return FALSE;
            }
        }

        return TRUE;
    }

    public function toString()
    {
        $message = 'is error response';

        if (!is_null($this->errno)) {
            $message .= ". errno is {$this->errno}";
        }

        if (!is_null($this->errmsg)) {
            $message .= ". errmsg is '{$this->errmsg}'";
        }

        return $message;
    }
}

