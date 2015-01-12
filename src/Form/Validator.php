<?php
namespace Form;

class Validator
{
    public $isValid = true;
    public $isGroupValid = true;
    public $fields;
    private $currentObj;

    const ERROR_REQUIRED = 'This field is required.';
    const ERROR_EMAIL = 'This field must contain a valid email address.';
    const ERROR_ALFA = 'This field must only contain alpha-numeric characters.';

    private $pattern_email = '/^([a-zA-Z0-9_\+\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/';
    private $pattern_alfa = '/^(\d|\-|_|\.| |(\p{L}\p{M}*))+$/u';

    function __construct($post)
    {
        foreach ($post as $key => $value)
        {
            $this->fields[$key] = new Field(trim($value));
        }
    }
    function isGroupValid()
    {
        return $this->isGroupValid;
    }
    private function setErrorMsg($errorMsg, $default, $params=null)
    {
        $this->isGroupValid = false;
        if ($errorMsg == '')
        {
            $this->currentObj->error = sprintf($default, $params);
        }
        else
            $this->currentObj->error = $errorMsg;
    }

    public function extractFieldsValues()
    {
        return array_map(function(\Form\Field $el){
            return $el->value;
        }, $this->fields);
    }
    public function extractFields()
    {
        return array_map(function(\Form\Field $el){
            return ['value' =>$el->value, 'error' => $el->error];
        }, $this->fields);
    }

    public function name($name)
    {
        if (!isset($this->fields[$name]))
            $this->fields[$name] = new Field('');
        $this->isValid = true;
        $this->currentObj = &$this->fields[$name];
        return $this;
    }
    public function required($errorMsg=null)
    {
        if ($this->isValid)
        {
            $this->isValid = ( $this->currentObj->value != '') ? true : false;
            if (!$this->isValid)

                $this->setErrorMsg($errorMsg, self::ERROR_REQUIRED);
        }
        return $this;
    }
    public function email($errorMsg=null)
    {
        if ($this->isValid && (!empty($this->currentObj->value)))
        {
            $this->isValid = (preg_match($this->pattern_email, $this->currentObj->value) > 0) ? true : false;
            if (!$this->isValid)
                $this->setErrorMsg($errorMsg, self::ERROR_EMAIL);
        }
        return $this;
    }
    public function alfa($errorMsg=null)
    {
        if ($this->isValid && (!empty($this->currentObj->value)))
        {
            $this->isValid = (preg_match($this->pattern_alfa, $this->currentObj->value)) ? true : false;
            if (!$this->isValid)
                $this->setErrorMsg($errorMsg, self::ERROR_ALFA);
        }
        return $this;
    }
}
?>