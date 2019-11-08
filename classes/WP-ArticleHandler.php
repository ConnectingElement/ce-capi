<?php 

namespace ContentAPI;

define('ERROR_HANDLING', 'ERRORS_DATABASE');

class ArticleHandler 
{
    const REFERENCE_META_KEY = '_ce-capi_reference';
    
    /**
     * Adds the given image to the specified post
     * 
     * @param int $postID The ID of the post to add the image to
     * @param string $imageURL The URL of the image to add
     * @return boolean Whether the addition was successful
     */
    public static function addPostImage($postID, $imageURL)
    {
        // download the image
        if ($imagePath = self::downloadFile($imageURL)) {
            // add the file to the database
            $filename = basename($imagePath);
            $wp_filetype = wp_check_filetype($filename, null);
            $attachment = array(
                'post_mime_type'    => $wp_filetype['type'],
                'post_title'        => sanitize_file_name($filename),
                'post_content'      => '',
                'post_status'       => 'inherit'
            );
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachmentID   = wp_insert_attachment($attachment, $imagePath, $postID);
            $attachmentData = wp_generate_attachment_metadata($attachmentID, $imagePath);
            if ($result = wp_update_attachment_metadata($attachmentID, $attachmentData)) {
                return $attachmentID;
            } else {
                return $result;
            }
        }
        return false;
    }

    /**
     * Deletes one or more articles from this endpoint
     * 
     * @global wpdb $wpdb Wordpress database object
     * @param \ContentAPI\Payload $payload The payload received
     * @param \ContentAPI\Message $response The message to add responses to
     */
    public function delete(Payload $payload, Message $response)
    {
        global $wpdb;
        $responsePayload = $payload->makeResponse();
        
        $this->validatePayload($payload, $responsePayload);
        
        // delete article
        if (!$responsePayload->getErrors()){
            $wpdb->query('START TRANSACTION');
            if (wp_delete_post($payload->data['master']['id'], true) === false) {
                $responsePayload->addError(new Error(0x25, 'Could not delete article'))->setStatus(500);
            }
        }
        
        // delete image
        if (!$responsePayload->getErrors()){
            if (wp_delete_post($payload->data['master']['imageid'], true) === false){
                $responsePayload->addError(new Error(0x26, 'Could not delete image for article'))->setStatus(500);
            }
        }
        
        // nothing to update in bgpp_article_images
        
        // action
        if ($responsePayload->getErrors()){  
            $wpdb->query('ROLLBACK');
        } else {
            $wpdb->query('COMMIT');
            $responsePayload->setStatus(200); // okay
        }
        $response->addPayload($responsePayload);
    }
    
    /**
     * Downloads the specified file
     * 
     * @param string $url The URL of the file to download
     * @param int|null $time A timestamp used to determine the wordpress directory to write to
     * @param string|null $saveFilename The filename to save as locally (or false to use filename from URL)
     * @return string|bool The local path to the image if successful, else false
     */
    public static function downloadFile($url, $time = null, $saveFilename = false)
    {
        $ch             = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response       = curl_exec($ch);
        $responseCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($responseCode === 200) {
            // write it to a file
            if ($time !== null) {
                $time = date('Y/m', $time);
            }
            if (is_string($saveFilename) && $saveFilename) {
                $imagePath = $saveFilename;
            } else {
                $upload_dir     = wp_upload_dir($time, true);           
                if ($upload_dir['path']) {
                    $imagePath = $upload_dir['path'];
                } else {
                    return false;
                }
                $imagePath .= '/' . wp_unique_filename($imagePath, basename($url));
            }
            if (file_put_contents($imagePath, $response)) {
                return $imagePath;
            }
        } else {
            error_log(sprintf('CE-CAPI: Got response code %d when trying to fetch %s', $responseCode, $url));
        }
        return false;
    }
    
    /**
     * Placeholder function for retrievals, may be used in future
     * 
     * @param \ContentAPI\Payload $payload The payload received
     * @param \ContentAPI\Message $response The message to add responses to
     */
    public function get($payload, $response) {}
    
    /**
     * Retrieve an article by its identifier
     * 
     * @param int $id The ID of the article to find
     * @return WP_Post|array|null The post found
     */
    public static function getArticlesByID($id)
    {
        return get_post($id);
    }
    
    /**
     * Retrieves an article by its reference string
     * 
     * @param string $reference The reference to search for
     * @return array|bool An associative array containing a post if found, else false
     */
    public static function getArticlesByReference($reference)
    {
        $args = [
            'meta_query' => [
                [
                    'key' => self::REFERENCE_META_KEY,
                    'value' => $reference,
                    'compare' => '='
                ]
            ]
         ];
         $query = new \WP_Query($args);
         if (count($query->posts) === 1 ) {
             return $query->posts[0];
         }
         return false;
    }
    
    /**
     * Retrieves articles by seo friendly name
     * 
     * @param string $seoFriendlyName The string to search for
     * @return array|null The posts found
     */
    public static function getArticlesBySeoFriendlyName($seoFriendlyName)
    {
        return get_posts([
            'name'        => $seoFriendlyName,
            'post_type'   => 'post',
            'post_status' => 'publish',
            'numberposts' => 1
        ]);
    }   
    
    /**
     * Updates one or more articles at this endpoint
     * 
     * @global wpdb $wpdb Wordpress database object
     * @param \ContentAPI\Payload $payload The payload received
     * @param \ContentAPI\Message $response The message to add responses to
     */
    public function patch(Payload $payload, Message $response)
    {
        global $wpdb;
        $responsePayload = $payload->makeResponse();
        
        $this->validatePayload($payload, $responsePayload);
        
        // update article
        if (!$responsePayload->getErrors()){
            $wpdb->query('START TRANSACTION');
            $articleID = wp_update_post([
                'ID'            => $payload->data['master']['id'],
                'post_date'     => $payload->data['start_date'],
                'post_date_gmt' => $payload->data['start_date'],
                'post_title'    => $payload->data['title'],
                'post_excerpt'  => $payload->data['snippet'],
                'post_content'  => $payload->data['content'],
                'post_status'   => 'publish'
            ]);
            if (!is_int($articleID)){
                $responsePayload->addError(new Error(0x23, 'Could not update article'))->setStatus(500);
            }
        }
        
        // update image
        if (!$responsePayload->getErrors()){
            $filename = get_attached_file($payload->data['master']['imageid']);
            $filePath = self::downloadFile($payload->data['image']['src'], $payload->data['master']['created_at'], $filename);
            if (!$filePath) {
                $responsePayload->addError(new Error(0x24, 'Could not update image', sprintf('The existing file at %s could not be overwritten', $filename)))->setStatus(500);
            }
        }

        if (!$responsePayload->getErrors()){
            $tags = [];
            if (array_key_exists('tags', $payload->data)) {
                foreach ($payload->data['tags'] as $tag) {
                    $tags[] = $tag['name'];
                }
            }
            wp_set_post_tags($articleID, $tags, false);
        }
        
        // action
        if ($responsePayload->getErrors()){  
            $wpdb->query('ROLLBACK');
        } else {
            $wpdb->query('COMMIT');
            $responsePayload->setStatus(200); // okay
        }
        $response->addPayload($responsePayload);
    }
    
    /**
     * Inserts one or more articles at this endpoint
     * 
     * @global wpdb $wpdb Wordpress database object
     * @param \ContentAPI\Payload $payload The payload received
     * @param \ContentAPI\Message $response The message to add responses to
     * @return \ContentAPI\Message The response
     */
    public function post(Payload $payload, Message $response)
    {
        global $wpdb;
        
        $responsePayload = $payload->makeResponse();
        
        // reference
        $articleReference = $payload->identifier;
        $articles = static::getArticlesByReference($articleReference);
        if($articles){
            // already exist!
            $responsePayload->addError(new Error(0x1A, 'Article reference already exists'))->setStatus(400);
        }
        
        // check seo name
        $seoFriendlyName = false;
        if (!$responsePayload->getErrors()){
            $seoFriendlyName = sanitize_title($payload->data['title']);
            if ($articles = static::getArticlesBySeoFriendlyName($seoFriendlyName)){
                // matching name already exists, alter
                $seoFriendlyName .= '-' . date('M-y');
                if ($articles = static::getArticlesBySeoFriendlyName($seoFriendlyName)){
                    $responsePayload->addError(new Error(0x11, 'Could not assign a new unique seo_friendly_name/slug to article'))->setStatus(500);
                }
            }
        }
        
        // check for master data and attempt article insert
        if (!$responsePayload->getErrors()){
            $master = true;
            if ($master){
                if (array_key_exists('master', $payload->data)){
                    $responsePayload->addError(new Error(0x07, 'Endpoint is set as master locally, but master data was sent from CAPI'))->setStatus(500);
                }
            } else {
                $responsePayload->addError(new Error(0x06, 'Wordpress Endpoint must be set as master'))->setStatus(500);
            }
        }
        
        // insert article
        if (!$responsePayload->getErrors()){
            $wpdb->query('START TRANSACTION');
            
            $articleID = wp_insert_post([
                'post_name'     => $seoFriendlyName,
                'post_date'     => $payload->data['start_date'],
                'post_date_gmt' => $payload->data['start_date'],
                'post_title'    => $payload->data['title'],
                'post_excerpt'  => $payload->data['snippet'],
                'post_content'  => $payload->data['content'],
                'post_status'   => 'publish'
            ]);
            $post = get_post($articleID);
            
            if (is_int($articleID) || !$post){
                // add meta
                if (!add_post_meta($articleID, self::REFERENCE_META_KEY, $articleReference)) {
                    $responsePayload->addError(new Error(0x12, 'Could not add article', 'Reference meta data could not be added'))->setStatus(500);
                }
            } else {
                $responsePayload->addError(new Error(0x12, 'Could not add article'))->setStatus(500);
            }
        }
        
        // add image
        if (!$responsePayload->getErrors()){
            if ($attachmentID = self::addPostImage($articleID, $payload->data['image']['src'])){
                // assign image to article
                if (!self::setPostImage($articleID, $attachmentID)){
                    $responsePayload->addError(new Error(0x15, 'Could not assign image to article', ['articleid' => $articleID, 'attachmentid' => $attachmentID]))->setStatus(500);
                }
            } else {
                $responsePayload->addError(new Error(0x14, 'Could not insert image for article'))->setStatus(500);
            }
        }

        // add tags
        if (!$responsePayload->getErrors() && count($payload->data['tags']) > 0) {
            $tags = [];
            foreach ($payload->data['tags'] as $tag) {
                $tags[] = $tag['name'];
            }
            wp_set_post_tags($articleID, $tags);
        }
        
        // action
        if ($responsePayload->getErrors()){  
            $wpdb->query('ROLLBACK');
        } else {
            $wpdb->query('COMMIT');
            if ($master){
                // we're the master, send back all the ids
                
                // we have to trick wordpress into giving us the friendly url on a future post for some reason
                $post->post_status = 'publish';
                $permalink = get_permalink($post);
                
                $responsePayload->addData([
                    'url'       => $permalink,
                    'master'    => [
                        'id'           => $articleID, 
                        'imageid'      => $attachmentID,
                        'created_at'   => time()]
                    ]);
                                            
            }
            $responsePayload->setStatus(201); // created
        }
        $response->addPayload($responsePayload);
        
        return $response;
    }
    
    /**
     * Associates a post with an image
     * 
     * @param int $postID The ID of the post
     * @param int $attachmentID The ID of the image attachement
     * @return bool True on success, false on failure.
     */
    public static function setPostImage($postID, $attachmentID) 
    {
        $result = set_post_thumbnail($postID, $attachmentID);
        //error_log(sprintf('Attempting to set attachment %s as post thumbnail for post %s resulted in %s', $attachmentID, $postID, var_export($result, true)));
        return $result;
    }
    
    /**
     * Validates the given payload
     * 
     * @param \ContentAPI\Payload $payload The payload to validate
     * @param \ContentAPI\Payload $responsePayload The response payload to add any errors to
     */
    public function validatePayload(Payload $payload, Payload &$responsePayload)
    {
        // check data given
        if (!array_key_exists('master', $payload->data)){
            $responsePayload->addError(new Error(0x1F, 'Method was patch but master data was not passed'))->setStatus(400);
        }
        
        if (!$responsePayload->getErrors()){
            // article id
            $articleID = $payload->data['master']['id'];
            $article = static::getArticlesByID($articleID);
            if ($article && is_a($article, 'WP_Post')){
                // reference
                $articleReference = $payload->identifier;
                $articleByReference = static::getArticlesByReference($articleReference);
                if ($articleByReference && is_a($articleByReference, 'WP_Post')){
                    if ($articleByReference->ID !== $article->ID){
                        $responsePayload->addError(new Error(0x1E, sprintf('Article id (%s) and reference %s (%s) did not match', $articleID, $articleReference, $articleByReference->ID)))->setStatus(400);
                    }
                } else {
                    // does not exist
                    $responsePayload->addError(new Error(0x1C, 'Article with reference ' . $articleReference . ' could not be found'))->setStatus(400);
                }
            } else {
                // id not found
                $article = false;
                $responsePayload->addError(new Error(0x1D, 'Article with id ' . $articleID . ' could not be found'))->setStatus(400);
            }

            // image
            if (array_key_exists('imageid', $payload->data['master'])){
                $image = get_post($payload->data['master']['imageid']);
                if (!$image || !is_a($image, 'WP_Post')){
                    $responsePayload->addError(new Error(0x21, 'No data was found with a matching master key', 'image with id ' . $payload->data['master']['imageid']))->setStatus(400);
                }
            } else {
                // image not found
                $image = false;
                $responsePayload->addError(new Error(0x20, 'A required master data key was missing', 'imageid'))->setStatus(400);
            }

            // assocation
            if ($article && $image) {
                if ($image->post_parent !== $article->ID) {
                    $info = sprintf('Parent post of given image (with post->id %s) was %s and so did not match the post id of the article (%s)', $image->ID, $image->post_parent, $article->ID);
                    $responsePayload->addError(new Error(0x22, 'A synchronisation issue was detected', $info))->setStatus(400);
                }
            }
        }
    }
}
?>
