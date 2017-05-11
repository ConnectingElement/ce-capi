<?php 

namespace ContentAPI;

use Exception;
use ContentApi\Payload;

class Message 
{
    protected $key;
    protected $digest;  // only set on received messages to check against
    
    protected $errors; // message level errors
    protected $message; // this is the full message to be sent (in object form, pre json)
    protected $payloads; // this is used to construct the data to be sent
    protected $protectAPIKey; // if true, the api key will not be in the response
    protected $status; // message level status
    protected $success; // message level success flag
    
    protected $sent;    // whether the message has been transmitted
    public $raw;     // the raw JSON of the message as was transmitted
    
    protected function __construct()
    {
        $this->key      = null;
        
        $this->errors           = [];
        $this->message          = null;
        $this->payloads         = [];
        $this->protectAPIKey    = true;
        $this->status           = null;
        $this->success          = false;
    }
    
    public static function fromString($string)
    {
        if($received = json_decode($string, true)){
            $message = Message::fromData($received);
            return $message;
        } else {
            //return $string;
            throw new Exception('Message could not be parsed. Code: ' . json_last_error() . '; String: ' . var_export($string, true) . ' ---');
        }
    }
    
    public static function fromData($data)
    {
        $requiredKeys = ['api_key', 'digest', 'payloads', 'errors', 'status', 'success'];
        foreach ($requiredKeys as $requiredKey){
            if (!array_key_exists($requiredKey, $data)){
                throw new Exception('Required key was not found in message: ' . $requiredKey);
            }
        }
        $message = new Message();
        $message->raw = $data;
        $message->key = $data['api_key'];
        $message->digest = $data['digest'];
        $message->status = $data['status'];
        $message->success = $data['success'];
        foreach ($data['errors'] as $error){
            $message->addError(new Error($error['code'], $error['description'], $error['information']));
        }
        foreach ($data['payloads'] as $payloadData){
            $message->addPayload(Payload::fromData($payloadData));
        }
        $message->displayAPIKey()->generateMessage($data['api_key']);
        return $message;
    }
    
    public function addError(Error $error)
    {
        $this->errors[] = $error;
        return $this;
    }
    
    public function addPayload($payload)
    {
        $identifier = $payload->getIdentifier();
        if (array_key_exists($identifier, $this->payloads)){
            throw new Exception('Payload with identifier ' . $identifier . ' already exists');
        }
        $this->payloads[$identifier] = $payload;
        return $this;
    }
    
    public function createPayload($identifier)
    {
        if (array_key_exists($identifier)){
            throw new Exception('A payload already exists with the identifier ' . $identifier);
        } else {
            $this->payloads[$identifier] = new Payload($identifier);
            return $this->payloads[$identifier];
        }
    }
    
    protected function displayAPIKey()
    {
        $this->protectAPIKey = false;
        return $this;
    }
    
    protected function getDigest($secret)
    {
        return hash_hmac('sha256', json_encode($this->message), $secret);
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
    
    public function getPayloads()
    {
        return $this->payloads;
    }
    
    public function getPayload($identifier)
    {
        if (array_key_exists($identifier, $this->payloads)){
            return $this->payloads[$identifier];
        }
        return null;
    }
    
    protected function generateMessage($key)
    {
        $this->message = [
            'api_key'   => ($this->protectAPIKey) ? null : $key,
            'errors'    => $this->errors,
            'payloads'  => $this->payloads,
            'status'    => $this->status,
            'success'   => $this->success
        ];
        return $this;
    }
    
    public function getMessage($key, $secret)
    {
        $this->message['digest'] = $this->generateMessage($key)->getDigest($secret);
        return $this->message;
    }
    
    public static function receive()
    {
        if (array_key_exists('message', $_POST)){
            return static::fromString($_POST['message']);
        } else if ($content = file_get_contents("php://input")) {
            return static::fromString($content);
        } else {
            throw new Exception('Message key not in POST');
        }
    }
    
    public static function request()
    {
        $message = new Message();
        return $message->displayAPIKey();
    }
    
    public static function respond()
    {
        return new Message();
    }
    
    public static function response($string, $key, $secret)
    {
        return static::fromString($string, $key, $secret);
    }
    
    public function setSent($json)
    {
        $this->raw = $json;
        $this->sent = true;
    }
    
    public function setStatus($status)
    {
        $this->status = $status;
        if ($this->status >= 200 && $status < 300){
            $this->success = true;
        }
        return $this;
    }
    
    public function valid($key, $secret, Message $response)
    {
        $errorOccurred = false;
        if ($this->key !== $key){
            $response->addError(new Error(0x04, 'The message API key was incorrect'))->setStatus(400);
            $errorOccurred = true;
        }
        if (!hash_equals($this->getDigest($secret), $this->digest)){
            $response->addError(new Error(0x05, 'The message digest did not validate'))->setStatus(400);
            $errorOccurred = true;
        }
        if (!$errorOccurred){
            $response->displayAPIKey(); // avoid leaking the correct API key if the request did not contain it (and a valid digest) already
            return true;
        }
        return false;
    }
}
?>