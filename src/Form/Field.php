<?php

namespace Form;


class Field
{
    public $value;
    public $error;

    public function __construct($value)
    {
        $this->value = $value;
        $this->error = '';
    }
}