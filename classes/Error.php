<?php

namespace ContentAPI;

class Error
{
    public $code;
    public $description;
    public $information;
    
    public function __construct($code, $description, $information = null)
    {
        $this->code = $code;
        $this->description = $description;
        $this->information = $information;
    }
    
    public static function fromData($errorData)
    {
        $requiredKeys = ['code', 'description', 'information'];
        foreach ($requiredKeys as $key){
            if (!array_key_exists($key, $errorData)){
                throw new Exception('Error does not contain a key "' . $key . '": ' . print_r($errorData, true));
            }
        }
        
        return new self($errorData['code'], $errorData['description'], $errorData['information']);
    }
}