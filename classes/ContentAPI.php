<?php 

namespace ContentAPI;

use Exception;

class ContentAPI
{
    public static function send(Message $message, $endpoint)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint->uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        $json = json_encode($message->getMessage($endpoint->api_key, $endpoint->api_secret));
        $message->setSent($json);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['message' => $json]);
        if ($response = curl_exec($ch)){
            return Message::response($response, $endpoint->api_key, $endpoint->api_secret);
        } else {
            if($curlError = curl_error($ch)){
                throw new Exception(curl_error($ch));
            } else {
                throw new Exception('ContentAPI got an unexpected response: ' . var_export($response, true));
            }
        }
    }
}
?>