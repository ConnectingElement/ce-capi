<?php

namespace ContentAPI;

use ContentAPI\Error;
use ContentAPI\Payload;

class Payload
{
    const ACTION_CREATE     = 'post';
    const ACTION_DELETE     = 'delete';
    const ACTION_RETRIEVE   = 'get';
    const ACTION_UPDATE     = 'patch';
    
    const MODULE_ARTICLES = 'articles';
    
    public $action;
    public $data;
    public $errors;
    public $extra;      // this is additional info not to be sent, but useful to keep associated with a payload
    public $identifier;
    public $module;
    public $status;
    public $success;
    
    public function __construct($identifier, $module, $action, $data = [], $extra = [])
    {
        switch ($module){
            case static::MODULE_ARTICLES:
                break;
            default:
                throw new Exception('Unknown module provided for payload: ' . $module);
        }
        switch ($action){
            case static::ACTION_CREATE:
            case static::ACTION_DELETE:
            case static::ACTION_RETRIEVE:
            case static::ACTION_UPDATE:
                break;
            default:
                throw new Exception('Unknown action provided for payload: ' . $action);
        }
        $this->action       = $action;
        $this->errors       = [];
        $this->extra        = $extra;
        $this->identifier   = $identifier;
        $this->module       = $module;
        $this->data         = $data;
        $this->status       = null;
        $this->success      = false;
    }
    
    public static function fromData($payloadData)
    {
        $requiredKeys = ['identifier', 'module', 'action'];
        foreach ($requiredKeys as $key){
            if (!array_key_exists($key, $payloadData)){
                throw new Exception('Payload does not contain a key "' . $key . '": ' . print_r($payloadData, true));
            }
        }
        
        $payload = new Payload($payloadData['identifier'], $payloadData['module'], $payloadData['action'], $payloadData['data']);
        
        if(array_key_exists('errors', $payloadData)){
            foreach ($payloadData['errors'] as $errorData){
                $payload->addError(Error::fromData($errorData));
            }
        }
        if (array_key_Exists('status', $payloadData)){
            $payload->status = $payloadData['status'];
        }
        if (array_key_Exists('success', $payloadData)){
            $payload->success = $payloadData['success'];
        }
        
        return $payload;
    }
    
    public function addData($arrayOrKey, $value = null)
    {
        if (is_array($arrayOrKey)){
            $this->data = array_merge($this->data, $arrayOrKey);
        } else {
            $this->data[$arrayOrKey] = $value;
        }
        return $this;
    }
    
    public function addError(Error $error)
    {
        $this->errors[] = $error;
        return $this;
    }
    
    public function addExtra($data, $key = null)
    {
        if ($key !== null && array_key_exists($key, $this->extra)){
            $this->extra = array_merge($this->extra, $data);
        } else {
            $this->extra[$key] = $data;
        }
        return $this;
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    public function getExtra($key = null)
    {
        if ($key === null){
            return $this->extra;
        } else if (array_key_exists($key, $this->extra)){
            return $this->extra[$key];
        } else {
            return null;
        }
    }
    
    public function getIdentifier()
    {
        return $this->identifier;
    }
    
    public function makeResponse()
    {
        return new Payload($this->identifier, $this->module, $this->action, []);
    }
    
    public function setStatus($status)
    {
        $this->status = $status;
        if ($this->status >= 200 && $status < 300){
            $this->success = true;
        }
        return $this;
    }
}