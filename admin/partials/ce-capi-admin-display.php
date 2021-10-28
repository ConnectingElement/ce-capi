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
            $options = get_option($this->plugin_name) ?: [];
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
                        <?php 
                            printf('<input type="text" id="%1$s-api_key" name="%1$s[api_key]" value="%2$s" class="regular-text" />', 
                                $this->plugin_name, 
                                array_key_exists('api_key', $options) ? $options['api_key'] : ''); 
                        ?>
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
                        <?php 
                            printf('<input type="text" id="%1$s-api_secret" name="%1$s[api_secret]" value="%2$s" class="regular-text" />', 
                                $this->plugin_name, 
                                array_key_exists('api_secret', $options) ? $options['api_secret'] : ''); 
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <h3>Category/Premises Mapping</h3>
        <p>If your WordPress site has multiple CAPI premises then you may need to link WordPress Categories to CAPI Premises IDs below. Otherwise leave these fields blank.</p>
        <table class="form-table">
            <tbody>
                <?php
                    foreach (get_categories(['hide_empty' => false]) as $category) {
                        $fieldKey = sprintf('%s-category-%s', $this->plugin_name, $category->term_id)
                        ?>
                        <tr>
                            <th scope="row">
                                <?php printf('<label for="%s"><span>%s</span></label>', $fieldKey, $category->name); ?>
                            </th>
                            <td>
                                <?php 
                                    printf('<input type="text" id="%s" name="%s[category-%s]" value="%s" class="regular-text"/>', 
                                        $fieldKey, 
                                        $this->plugin_name, 
                                        $category->term_id, 
                                        array_key_exists('category-' . $category->term_id, $options) ? $options['category-' . $category->term_id] : ''
                                    );
                                 ?>
                            </td>
                        </tr>
                        <?php
                    }
                ?>
            </tbody>
        </table>

        <?php submit_button('Save all changes', 'primary','submit', TRUE); ?>

    </form>

</div>
