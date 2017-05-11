<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://www.connectingelement.co.uk
 * @since      1.0.0
 *
 * @package    CE_CAPI
 * @subpackage CE_CAPI/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">

    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
    
    <form method="post" name="ce-capi_options" action="options.php">
        <?php 
            settings_fields($this->plugin_name); 
            $options = get_option($this->plugin_name);
        ?>
        
        <table class="form-table">
            <tbody>
                <!-- API key -->
                <tr>
                    <th scope="row">
                        <legend class="screen-reader-text"><span>API Key</span></legend>
                        <label for="<?php echo $this->plugin_name; ?>-api_key">
                            <span><?php esc_attr_e('API Key', $this->plugin_name); ?></span>
                        </label>
                    </th>
                    <td>
                        <input type="text" id="<?php echo $this->plugin_name; ?>-api_key" name="<?php echo $this->plugin_name; ?>[api_key]" value="<?php print($options['api_key']); ?>"  class="regular-text"/>
                    </td>
                </tr>
                <!-- API secret -->
                <tr>
                    <th scope="row">
                        <legend class="screen-reader-text"><span>API Secret</span></legend>
                        <label for="<?php echo $this->plugin_name; ?>-api_secret">
                            <span><?php esc_attr_e('API Secret', $this->plugin_name); ?></span>
                        </label>
                    </th>
                    <td>
                        <input type="text" id="<?php echo $this->plugin_name; ?>-api_secret" name="<?php echo $this->plugin_name; ?>[api_secret]" value="<?php print($options['api_secret']); ?>"  class="regular-text"/>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button('Save all changes', 'primary','submit', TRUE); ?>

    </form>

</div>
