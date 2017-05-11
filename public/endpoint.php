<?php 

namespace ContentAPI;

require_once('../classes/ContentAPI.php');
require_once('../classes/Error.php');
require_once('../classes/Message.php');
require_once('../classes/Payload.php');

require_once('../classes/WP-ArticleHandler.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
$options = get_option('ce-capi');

/**
 * hash_equals was added in PHP 5.6.0
 */
if (!function_exists('hash_equals')){
    function hash_equals($known_string, $user_string){
        if (!is_string($known_string)){
            trigger_error('hash_equals(): Expected known_string to be a string, ' . gettype($known_string) . ' given', E_USER_WARNING);
            return false;
        }
        if (!is_string($user_string)){
            trigger_error('hash_equals(): Expected user_string to be a string, ' . gettype($user_string) . ' given', E_USER_WARNING);
        }
        if (strlen($known_string) !== strlen($user_string)){
            return false;
        }
        $return = 0;
        for ($i = 0; $i < strlen($known_string); $i++){
            $return |= $known_string ^ $user_string;
        }
        return ($return === 0);
    }
}

function finish()
{
    global $response,$options;
    if ($error = error_get_last()){
        $response->addError(new Error(0x00, sprintf('A fatal error occurred at the end point: %s - %s at line %s of %s', $error['type'], $error['message'], $error['line'], $error['file'])))->setStatus(500);
    }
    $output = json_encode($response->getMessage($options['api_key'], $options['api_secret']));
    error_log('sending response: ' . $output);
    print($output);
}

ini_set('display_errors', 0); 
register_shutdown_function('ContentAPI\finish'); // make sure we output a proper message even if we get a fatal error

// make the response message
$response = Message::respond();

if (!isset($options['api_key']) || !isset($options['api_secret'])){
    $response->addError(new Error(0x06, 'A required endpoint setting is not defined'));
}

if ($_POST || file_get_contents("php://input")){
    try {
        $_POST = wp_unslash($_POST);
        error_log('Request received - $_POST = ' . var_export($_POST, true));
        $message = Message::receive($options['api_key'], $options['api_secret']);
        
        // validate the message
        if ($message->valid($options['api_key'], $options['api_secret'], $response)){
            // act on the payloads
            if ($message->getPayloads()) {
                foreach ($message->getPayloads() as $payload){
                    switch ($payload->module){
                        case Payload::MODULE_ARTICLES:
                            $class = new ArticleHandler;
                            break;
                        default:
                            $response->addPayload($payload->makeResponse()->addError(new Error(0x01, 'Unknown module ' . $payload->module))->setStatus(400));
                            continue 2;
                    }

                    switch ($payload->action){
                        case Payload::ACTION_CREATE:
                        case Payload::ACTION_DELETE:
                        case Payload::ACTION_RETRIEVE:
                        case Payload::ACTION_UPDATE:
                            $class->{$payload->action}($payload, $response);
                            break;
                        default:
                            $response->addPayload($payload->makeResponse()->addError(new Error(0x02, 'Unknown action ' . $payload->action))->setStatus(400));
                            continue 2;
                    }
                }
            } else {
                $response->addError(new Error(0x09, 'No payloads provided'))->setStatus(400);
            }
        }
        //logError('Generated response: ' . var_export($response, true));
    } catch (\Exception $e){
        $response->addError(new Error(0x00, $e->getMessage()))->setStatus(500);
    }
} else {
    $response->addError(new Error(0x03, 'Bad method; expected POST'))->setStatus(405);
}
?>