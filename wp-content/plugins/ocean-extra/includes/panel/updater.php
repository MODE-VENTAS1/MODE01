<?php
/**
 * Allows plugins to use their own update API.
 */
if (!class_exists('OceanWP_Plugin_Updater')) {

    class OceanWP_Plugin_Updater {

        private $api_data = array();
        private $name = '';
        private $slug = '';
        private $file = '';
        private $item_name = '';
        private $item_id = '';
        private $item_shortname = '';
        private $license_key = '';
        private $api_url = 'https://oceanwp.org/';
        private $bundle_error = '';

        /**
         * Class constructor.
         * 
         * @uses plugin_basename()
         * @uses hook()
         *
         * @param string  $_api_url     The URL pointing to the custom API endpoint.
         * @param string  $_plugin_file Path to the plugin file.
         * @param array   $_api_data    Optional data to send with API calls.
         * @return void
         */
        function __construct($_file, $_item, $_version, $_author, $_optname = null, $_api_url = null) {

            global $oceanwp_options;

            if (is_numeric($_item)) {
                $this->item_id = absint($_item);
            }

            $this->file = $_file;
            $this->api_url = empty($_api_url) ? $this->api_url : trailingslashit($_api_url);
            $this->slug = basename($this->file, '.php');
            $this->name = plugin_basename($this->file);
            $this->item_name = $_item;
            $this->item_shortname = 'oceanwp_' . preg_replace('/[^a-zA-Z0-9_\s]/', '', str_replace(' ', '_', strtolower($this->item_name)));
            $this->version = $_version;
            $this->author = $_author;

            //Get license options
            $oceanwp_options = get_option('oceanwp_options');

            // Get Licence ke
            $this->license_key = isset($oceanwp_options['licenses'][$this->item_shortname . '_license_key']) ? $oceanwp_options['licenses'][$this->item_shortname . '_license_key'] : '';

            // Set up hooks.
            $this->init();
        }

        /**
         * Set up WordPress filters to hook into WP's update process.
         *
         * @uses add_filter()
         *
         * @return void
         */
        public function init() {

            // Add filter to enable license tab
            add_filter('oceanwp_licence_tab_enable', '__return_true');

            // Register settings
            add_action('oceanwp_licenses_tab_fields', array($this, 'oceanwp_add_settings_fields'));

            // Activate license key on settings save
            add_action('admin_init', array($this, 'oceanwp_activate_license'));

            // Deactivate license key
            add_action('admin_init', array($this, 'oceanwp_deactivate_license'));

            // Updater
            add_action('admin_init', array($this, 'oceanwp_auto_updater'), 0);

            // Show changelog
            add_action('admin_init', array($this, 'oceanwp_show_changelog'));

            // Add core extensions bundle block
            add_action('oceanwp_licenses_tab_top', array($this, 'add_core_extensions_bundle_block'));

            // Add core extensions bundle validation
            add_action('admin_init', array($this, 'add_core_extensions_bundle_validation'), 0);
        }

        /**
         * @author rf@objects
         * update options in all sites
         * @return void
         */
        public function update_oceanwp_option($option, $value) {
            if (is_multisite()) {
                $sites = wp_get_sites();
                foreach ($sites as $site) {
                    $site_id = $site["blog_id"];
                    switch_to_blog($site_id);
                    update_option($option, $value);
                    restore_current_blog();
                }
            } else {
                update_option($option, $value);
            }
        }

        /**
         * delete options in all sites
         * @return void
         */
        public function delete_oceanwp_option($option) {
            if (is_multisite()) {
                $sites = wp_get_sites();
                foreach ($sites as $site) {
                    $site_id = $site["blog_id"];
                    switch_to_blog($site_id);
                    delete_option($option);
                    restore_current_blog();
                }
            } else {
                delete_option($option);
            }
        }

        /**
         * Add core extensions bundle block
         * @static var boolean $block
         * @return type
         */
        public function add_core_extensions_bundle_block() {
            static $block;

            if (!empty($block)) {
                return;
            }

            $oceanwp_bundle_status = get_option("oceanwp_bundle_status");
            $oceanwp_bundle_key = get_option("oceanwp_bundle_key");
            ?>
            <table class="form-table bundle-block">
                <tbody>
                    <tr>
                        <th>
                            <label for="core_extensions_bundle_license_key"><?php _e("Core Extensions Bundle License Key", "oceanwp"); ?></label>
                        </th>
                        <td>
                            <input type="text" class="regular-text" id="core_extensions_bundle_license_key" name="core_extensions_bundle_license_key" value="<?php echo $this->get_hidden_license_key($oceanwp_bundle_key); ?>" <?php echo ($oceanwp_bundle_key) ? "readonly" : ""; ?> />
                            <?php
                            if ($oceanwp_bundle_status == "valid") {
                                $expire_date = get_option("oceanwp_bundle_expires");
                                ?>
                                <input type="submit" class="button-secondary" name="deactivate_all" value="Deactivate License">
                                <div class="oceanwp-license-data oceanwp-license-valid-msg valid-msg license-expiration-date-notice">
                                    <p><?php
                                        printf(__('Your license key expires on %1$s.', 'ocean-extra'), date_i18n(get_option('date_format'), strtotime($expire_date, current_time('timestamp'))));
                                        ?></p>
                                </div>                               
                                <?php
                            }

                            if (empty($oceanwp_bundle_status)) {
                                ?>
                                <div class="oceanwp-license-data oceanwp-license-empty-msg empty-msg ">

                                    <p><?php _e("If you bought the Core Extensions Bundle, add your bundle license key here to get automatic updates for all the extensions.", "ocean-extra"); ?></p>
                                </div>                                   
                                <?php
                            } elseif ($oceanwp_bundle_status != "valid") {
                                ?>
                                <div class="oceanwp-license-data oceanwp-license-error-msg error-msg license-error-msg-notice ">

                                    <?php
                                    switch ($oceanwp_bundle_status) {

                                        case 'expired' :
                                            _e('Your license key is expired. Please renew your license key for auto updates.', 'ocean-extra');

                                            break;

                                        case 'revoked' :

                                            printf(
                                                    __('Your license key has been disabled. Please <a href="%1$s" target="_blank">contact support</a> for more information.', 'ocean-extra'), 'https://oceanwp.org/support?utm_campaign=admin&utm_source=licenses&utm_medium=revoked'
                                            );

                                            break;

                                        case 'missing' :

                                            printf(
                                                    __('Invalid license. Please <a href="%1$s" target="_blank">visit your account page</a> and verify it.', 'ocean-extra'), 'https://oceanwp.org/your-account?utm_campaign=admin&utm_source=licenses&utm_medium=missing'
                                            );


                                            break;

                                        case 'invalid' :
                                        case 'site_inactive' :
                                            printf(
                                                    __('Your %1$s is not active for this URL. Please <a href="%2$s" target="_blank">visit your account page</a> to manage your license key URLs.', 'ocean-extra'), "Core Extensions Bundle", 'https://oceanwp.org/your-account?utm_campaign=admin&utm_source=licenses&utm_medium=invalid'
                                            );


                                            break;

                                        case 'item_name_mismatch' :

                                            printf(__('This appears to be an invalid license key for %1$s.', 'ocean-extra'), "Core Extensions Bundle");

                                            break;

                                        case 'no_activations_left':
                                            sprintf(__('Your license key has reached its activation limit. <a href="%1$s">View possible upgrades</a> now.', 'ocean-extra'), 'https://oceanwp.org/your-account/');
                                            break;

                                        case 'license_not_activable':

                                            _e('The key you entered belongs to a bundle, please use the product specific license key.', 'ocean-extra');

                                            break;
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                            ?>

                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
            $block = true;
        }

        /**
         * Add core extensions bundle validation
         * @static var boolean $validation
         * @return type
         */
        public function add_core_extensions_bundle_validation() {
            if (!current_user_can('manage_options') ||
            ! isset($_REQUEST['_wpnonce']) ||
            (isset($_REQUEST['_wpnonce'])&& !wp_verify_nonce($_REQUEST['_wpnonce'], 'oceanwp_options-options')))
                return;
            static $validation;

            if (!empty($validation)) {
                return;
            }


            if (isset($_POST['oceanwp_options']) && isset($_POST['oceanwp_licensekey_activateall']) && isset($_POST['core_extensions_bundle_license_key']) && !empty($_POST['core_extensions_bundle_license_key']) && strpos($_POST['core_extensions_bundle_license_key'], "XXX") === FALSE
            ) {



                /*                 * ************************************ */
                //Choose any unactive licenses
                $unactive = "";
                foreach ($_POST['oceanwp_options']['licenses'] as $key => $value) {
                    if (!$value) //check it not active licenses to activate it
                        $unactive = $key;break;
                }


                /*                 * ************************************ */
                $unactive = str_replace("oceanwp_", "", $unactive);
                $unactive = str_replace("_license_key", "", $unactive);
                $unactive = str_replace("_", " ", $unactive);
                $unactive = ucwords($unactive);

                if ($unactive) {
                    $api_params = array(
                        'edd_action' => 'activate_license',
                        'license' => $_POST['core_extensions_bundle_license_key'],
                        'item_name' => $unactive,
                        'url' => home_url()
                    );

                    // Call the API
                    $response = wp_remote_post(
                            $this->api_url, array(
                        'timeout' => 15,
                        'sslverify' => false,
                        'body' => $api_params
                            )
                    );

                    // Make sure there are no errors
                    if (is_wp_error($response)) {
                        return;
                    }

                    // Decode license data
                    $license_data = json_decode(wp_remote_retrieve_body($response));
                    $activated_licenses_by_bundle = array();
                    if ($license_data->license == "valid") {

                        if (isset($_POST['oceanwp_options']['licenses']) && $_POST['oceanwp_options']['licenses']) {

                            foreach ($_POST['oceanwp_options']['licenses'] as $key => $value) {
                                if (!$value) { //check it not active licenses to activate it
                                    $_POST['oceanwp_options']['licenses'][$key] = $_POST['core_extensions_bundle_license_key'];
                                    $activated_licenses_by_bundle[] = $key;
                                }
                            }
                        }
                        $this->update_oceanwp_option("oceanwp_bundle_key", $_POST['core_extensions_bundle_license_key']);
                        $this->update_oceanwp_option("oceanwp_activated_licenses_by_bundle", $activated_licenses_by_bundle);
                    }
                    $this->update_oceanwp_option("oceanwp_bundle_status", $license_data->license);
                    $this->update_oceanwp_option("oceanwp_bundle_expires", $license_data->expires);
                    $this->update_oceanwp_option("oceanwp_options", $_POST['oceanwp_options']);
                }
            } elseif (isset($_POST['deactivate_all'])) {
                $activated_licenses_by_bundle = get_option("oceanwp_activated_licenses_by_bundle");
                $this->delete_oceanwp_option('oceanwp_bundle_status');
                $this->delete_oceanwp_option('oceanwp_bundle_key');
                $this->delete_oceanwp_option('oceanwp_bundle_expires');
                $this->delete_oceanwp_option('oceanwp_activated_licenses_by_bundle');

                $license_details = get_option('edd_license_details');

                if (!empty($license_details)) {

                    // loop over active licenses and deactive
                    foreach ($license_details as $key => $value) {

                        if (is_array($activated_licenses_by_bundle) && in_array($key . '_license_key', $activated_licenses_by_bundle))
                            $_POST['oceanwp_' . $key . '_license_key_deactivate'] = TRUE;
                    }
                }
            }

            $validation = TRUE;
        }

        /**
         * Sanitize HTML Class Names
         *
         * @param  string|array $class HTML Class Name(s)
         * @return string $class
         */
        public function sanitize_html_class($class = '') {

            if (is_string($class)) {
                $class = sanitize_html_class($class);
            } else if (is_array($class)) {
                $class = array_values(array_map('sanitize_html_class', $class));
                $class = implode(' ', array_unique($class));
            }

            return $class;
        }

        /**
         * Hide license key
         */
        private function get_hidden_license_key($input_string = "") {
            $start = 5;
            $length = mb_strlen($input_string) - $start - 5;

            $mask_string = preg_replace('/\S/', 'X', $input_string);
            $mask_string = mb_substr($mask_string, $start, $length);
            $input_string = substr_replace($input_string, $mask_string, $start, $length);

            return $input_string;
        }

        /**
         * Add license field to settings
         *
         * @access  public
         * @param array   $settings
         * @return  array
         */
        public function oceanwp_add_settings_fields() {

            $license_key = $this->license_key;

            $messages = array();
            $license_details = get_option('edd_license_details');
            $expire_date = isset($license_details[$this->item_shortname]->expires) && trim($license_details[$this->item_shortname]->expires) != '' ? $license_details[$this->item_shortname]->expires : '';

            if (!empty($license_details[$this->item_shortname]) && is_object($license_details[$this->item_shortname])) {

                // activate_license 'invalid' on anything other than valid, so if there was an error capture it
                if (false === $license_details[$this->item_shortname]->success) {

                    switch ($license_details[$this->item_shortname]->error) {

                        case 'expired' :

                            $class = 'expired-msg';
                            $messages[] = sprintf(
                                    __('Your license key expires on %1$s. Please renew your license key for auto updates.', 'ocean-extra'), date_i18n(get_option('date_format'), strtotime($expire_date, current_time('timestamp')))
                            );

                            $license_status = 'license-' . $class . '-notice';

                            break;

                        case 'revoked' :

                            $class = 'error-msg';
                            $messages[] = sprintf(
                                    __('Your license key has been disabled. Please <a href="%1$s" target="_blank">contact support</a> for more information.', 'ocean-extra'), 'https://oceanwp.org/support?utm_campaign=admin&utm_source=licenses&utm_medium=revoked'
                            );

                            $license_status = 'license-' . $class . '-notice';

                            break;

                        case 'missing' :

                            $class = 'error-msg';
                            $messages[] = sprintf(
                                    __('Invalid license. Please <a href="%1$s" target="_blank">visit your account page</a> and verify it.', 'ocean-extra'), 'https://oceanwp.org/your-account?utm_campaign=admin&utm_source=licenses&utm_medium=missing'
                            );

                            $license_status = 'license-' . $class . '-notice';

                            break;

                        case 'invalid' :
                        case 'site_inactive' :

                            $class = 'error-msg';
                            $messages[] = sprintf(
                                    __('Your %1$s is not active for this URL. Please <a href="%2$s" target="_blank">visit your account page</a> to manage your license key URLs.', 'ocean-extra'), $this->item_name, 'https://oceanwp.org/your-account?utm_campaign=admin&utm_source=licenses&utm_medium=invalid'
                            );

                            $license_status = 'license-' . $class . '-notice';

                            break;

                        case 'item_name_mismatch' :

                            $class = 'error-msg';
                            $messages[] = sprintf(__('This appears to be an invalid license key for %1$s.', 'ocean-extra'), $this->item_name);

                            $license_status = 'license-' . $class . '-notice';

                            break;

                        case 'no_activations_left':

                            $class = 'error-msg';
                            $messages[] = sprintf(__('Your license key has reached its activation limit. <a href="%1$s">View possible upgrades</a> now.', 'ocean-extra'), 'https://oceanwp.org/your-account/');

                            $license_status = 'license-' . $class . '-notice';

                            break;

                        case 'license_not_activable':

                            $class = 'error-msg';
                            $messages[] = __('The key you entered belongs to a bundle, please use the product specific license key.', 'ocean-extra');

                            $license_status = 'license-' . $class . '-notice';
                            break;

                        default :

                            $class = 'error-msg';
                            $error = !empty($license_details[$this->item_shortname]->error) ? $license_details[$this->item_shortname]->error : __('unknown_error', 'ocean-extra');
                            $messages[] = sprintf(__('There was an error with this license key: %1$s. Please <a href="%2$s">contact our support team</a>.', 'ocean-extra'), $error, 'https://oceanwp.org/support');

                            $license_status = 'license-' . $class . '-notice';
                            break;
                    }
                } else {

                    switch ($license_details[$this->item_shortname]->license) {

                        case 'valid' :
                        default:

                            $class = 'valid-msg';

                            $now = current_time('timestamp');
                            $expiration = strtotime($expire_date, current_time('timestamp'));

                            if ('lifetime' === $expire_date) {

                                $messages[] = __('License key never expires.', 'ocean-extra');

                                $license_status = 'license-lifetime-notice';
                            } elseif ($expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 )) {

                                $messages[] = sprintf(
                                        __('Your license key expires soon! It expires on %1$s. Renew your license key for auto updates.', 'ocean-extra'), date_i18n(get_option('date_format'), strtotime($expire_date, current_time('timestamp')))
                                );

                                $license_status = 'license-expires-soon-notice';
                            } else {

                                $messages[] = sprintf(
                                        __('Your license key expires on %1$s.', 'ocean-extra'), date_i18n(get_option('date_format'), strtotime($expire_date, current_time('timestamp')))
                                );

                                $license_status = 'license-expiration-date-notice';
                            }

                            break;
                    }
                }
            } else {
                $class = 'empty-msg';

                $messages[] = sprintf(
                        __('To receive updates, please enter your valid %1$s license key.', 'ocean-extra'), $this->item_name
                );

                $license_status = null;
            }

            $class .= ' ' . $this->sanitize_html_class($class);

            $html = '<tr>';
            $html .= '<th>';
            $html .= '<label for="' . $this->item_shortname . '_license_key">';
            $html .= '' . sprintf(__('%1$s License Key', 'ocean-extra'), $this->item_name) . '';
            $html .= '</label>';
            $html .= '</th>';

            $html .= '<td>';
            if (!empty($license_key) && 'valid' == get_option($this->item_shortname . '_license_active')) {
                $html .= '<input type="text" class="regular-text" id="' . $this->item_shortname . '_license_key" name="oceanwp_options[licenses][' . $this->item_shortname . '_license_key]" value="' . esc_attr(self::get_hidden_license_key($license_key)) . '" readonly />';
            } else {
                $html .= '<input type="text" class="regular-text" id="' . $this->item_shortname . '_license_key" name="oceanwp_options[licenses][' . $this->item_shortname . '_license_key]" value="" />';
            }

            if ('valid' == get_option($this->item_shortname . '_license_active')) {
                $html .= '<input type="submit" class="button-secondary" name="oceanwp_' . $this->item_shortname . '_license_key_deactivate" value="' . __('Deactivate License', 'ocean-extra') . '">';
            }

            if (!empty($messages)) {
                foreach ($messages as $message) {

                    $html .= '<div class="oceanwp-license-data oceanwp-license-' . $class . ' ' . $license_status . '">';
                    $html .= '<p>' . $message . '</p>';
                    $html .= '</div>';
                }
            }

            $html .= '</td>';
            $html .= '</tr>';

            echo $html;
        }

        /**
         * Activate the license key
         *
         * @access  public
         * @return  void
         */
        public function oceanwp_activate_license() {
            if (!current_user_can('manage_options') ||
            ! isset($_REQUEST['_wpnonce']) ||
            (isset($_REQUEST['_wpnonce'])&&!wp_verify_nonce($_REQUEST['_wpnonce'], 'oceanwp_options-options')))
                return;

            if (!isset($_POST['oceanwp_options']) || !isset($_POST['oceanwp_licensekey_activateall'])) {
                return;
            }

            if (!isset($_POST['oceanwp_options']['licenses'][$this->item_shortname . '_license_key'])) {
                return;
            }

            if (strpos($_POST['oceanwp_options']['licenses'][$this->item_shortname . '_license_key'], "XXX") !== FALSE && $this->license_key) {
                $_POST['oceanwp_options']['licenses'][$this->item_shortname . '_license_key'] = $this->license_key;
            }

            $license = sanitize_text_field(wp_unslash($_POST['oceanwp_options']['licenses'][$this->item_shortname . '_license_key']));

            if (trim($license) == '') {

                //Remove license data and update it
                $this->oceanwp_delete_response($this->item_shortname);
                return;
            }

            // Data to send to the API
            $api_params = array(
                'edd_action' => 'activate_license',
                'license' => $license,
                'item_name' => isset($this->item_name) ? $this->item_name : false,
                'url' => home_url()
            );

            // Call the API
            $response = wp_remote_post(
                    $this->api_url, array(
                'timeout' => 15,
                'sslverify' => false,
                'body' => $api_params
                    )
            );

            // Make sure there are no errors
            if (is_wp_error($response)) {
                return;
            }

            // Tell WordPress to look for updates
            set_site_transient('update_plugins', null);

            // Decode license data
            $license_data = json_decode(wp_remote_retrieve_body($response));

            $this->update_oceanwp_option($this->item_shortname . '_license_active', $license_data->license);

            //Check license response data exists and update
            if (!empty($license_data)) {
                $this->oceanwp_update_response($this->item_shortname, $license_data);
            }

            if (!(bool) $license_data->success) {
                set_transient('edd_license_error', $license_data, 1000);
            } else {
                delete_transient('edd_license_error');
            }
        }

        /**
         * Deactivate the license key
         *
         * @access  public
         * @return  void
         */
        public function oceanwp_deactivate_license() {
            if (!current_user_can('manage_options') ||
            ! isset($_REQUEST['_wpnonce']) ||
            (isset($_REQUEST['_wpnonce'])&&!wp_verify_nonce($_REQUEST['_wpnonce'], 'oceanwp_options-options')))
                return;
            if (!isset($_POST['oceanwp_options'])) {
                return;
            }

            if (!isset($_POST['oceanwp_options']['licenses'][$this->item_shortname . '_license_key'])) {
                return;
            }

            // Run on deactivate button press
            if (isset($_POST['oceanwp_' . $this->item_shortname . '_license_key_deactivate'])) {

                // Data to send to the API
                $api_params = array(
                    'edd_action' => 'deactivate_license',
                    'license' => $this->license_key,
                    'item_name' => urlencode($this->item_name),
                    'url' => home_url()
                );

                // Call the API
                $response = wp_remote_post(
                        $this->api_url, array(
                    'timeout' => 15,
                    'sslverify' => false,
                    'body' => $api_params
                        )
                );

                // Make sure there are no errors
                if (is_wp_error($response)) {
                    return;
                }

                // Decode the license data
                $license_data = json_decode(wp_remote_retrieve_body($response));

                $this->delete_oceanwp_option($this->item_shortname . '_license_active');

                if (!(bool) $license_data->success) {
                    set_transient('edd_license_error', $license_data, 1000);
                } else {
                    delete_transient('edd_license_error');

                    //Remove license data and update it
                    $this->oceanwp_delete_response($this->item_shortname);
                }
            }
        }

        /**
         * Auto updater
         *
         * @access  private
         * @return  void
         */
        public function oceanwp_auto_updater() {
            if (!current_user_can('manage_options') )
                return;
            if ('valid' !== get_option($this->item_shortname . '_license_active')) {
                return;
            }

            $args = array(
                'version' => $this->version,
                'license' => $this->license_key,
                'author' => $this->author
            );

            if (!empty($this->item_id)) {
                $args['item_id'] = $this->item_id;
            } else {
                $args['item_name'] = $this->item_name;
            }

            // require filter applies
            add_filter('pre_set_site_transient_update_plugins', array($this, 'oceanwp_check_update'));
            add_filter('plugins_api', array($this, 'oceanwp_plugins_api_filter'), 10, 3);
            add_action('after_plugin_row_' . $this->name, array($this, 'oceanwp_show_update_notification'), 10, 2);
        }

        /**
         * Check for Updates at the defined API endpoint and modify the update array.
         *
         * This function dives into the update API just when WordPress creates its update array,
         * then adds a custom API call and injects the custom plugin data retrieved from the API.
         * It is reassembled from parts of the native WordPress plugin update code.
         * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
         *
         * @uses api_request()
         *
         * @param array   $_transient_data Update array build by WordPress.
         * @return array Modified update array with custom plugin data.
         */
        public function oceanwp_check_update($_transient_data) {

            global $pagenow;

            if (!is_object($_transient_data)) {
                $_transient_data = new stdClass;
            }

            if ('plugins.php' == $pagenow && is_multisite()) {
                return $_transient_data;
            }

            if (empty($_transient_data->response) || empty($_transient_data->response[$this->name])) {

                $version_info = $this->api_request('plugin_latest_version', array('slug' => $this->slug));

                if (false !== $version_info && is_object($version_info) && isset($version_info->new_version)) {

                    if (version_compare($this->version, $version_info->new_version, '<')) {

                        if (empty($version_info->plugin)) {
                            $version_info->plugin = $this->name;
                        }
                        $_transient_data->response[$this->name] = $version_info;
                    }
                    $_transient_data->last_checked = time();
                    $_transient_data->checked[$this->name] = $this->version;
                }
            }
            return $_transient_data;
        }

        /**
         * Updates information on the "View version x.x details" page with custom data.
         *
         * @uses api_request()
         *
         * @param mixed   $_data
         * @param string  $_action
         * @param object  $_args
         * @return object $_data
         */
        public function oceanwp_plugins_api_filter($_data, $_action = '', $_args = null) {

            if ($_action != 'plugin_information') {
                return $_data;
            }

            if (!isset($_args->slug) || ( $_args->slug != $this->slug )) {
                return $_data;
            }

            $to_send = array(
                'slug' => $this->slug,
                'is_ssl' => is_ssl(),
                'fields' => array(
                    'banners' => false, // These will be supported soon hopefully
                    'reviews' => false
                )
            );

            $api_response = $this->api_request('plugin_information', $to_send);

            if (false !== $api_response) {
                $_data = $api_response;
            }

            return $_data;
        }

        /**
         * show update nofication row -- needed for multisite subsites, because WP won't tell you otherwise!
         *
         * @param string  $file
         * @param array   $plugin
         */
        public function oceanwp_show_update_notification() {

            if (!current_user_can('update_plugins')) {
                return;
            }

            if (!is_multisite()) {
                return;
            }

            // Remove our filter on the site transient
            remove_filter('pre_set_site_transient_update_plugins', array($this, 'oceanwp_check_update'), 10);

            $update_cache = get_site_transient('update_plugins');

            if (!is_object($update_cache) || empty($update_cache->response) || empty($update_cache->response[$this->name])) {

                $cache_key = md5('edd_plugin_' . sanitize_key($this->name) . '_version_info');
                $version_info = get_transient($cache_key);

                if (false === $version_info) {
                    $version_info = $this->api_request('plugin_latest_version', array('slug' => $this->slug));
                    set_transient($cache_key, $version_info, 3600);
                }

                if (!is_object($version_info)) {
                    return;
                }

                if (version_compare($this->version, $version_info->new_version, '<')) {
                    $update_cache->response[$this->name] = $version_info;
                }

                $update_cache->last_checked = time();
                $update_cache->checked[$this->name] = $this->version;

                set_site_transient('update_plugins', $update_cache);
            } else {

                $version_info = $update_cache->response[$this->name];
            }

            // Restore our filter
            add_filter('pre_set_site_transient_update_plugins', array($this, 'oceanwp_check_update'));

            if (!empty($update_cache->response[$this->name]) && version_compare($this->version, $version_info->new_version, '<')) {

                // build a plugin list row, with update notification
                $wp_list_table = _get_list_table('WP_Plugins_List_Table');
                echo '<tr class="plugin-update-tr"><td colspan="' . esc_attr($wp_list_table->get_column_count()) . '" class="plugin-update colspanchange"><div class="update-message">';

                $changelog_link = wp_nonce_url(self_admin_url('index.php?edd_sl_action=view_plugin_changelog&plugin=' . esc_attr($this->name) . '&slug=' . esc_url($this->slug) . '&TB_iframe=true&width=772&height=911'),'changelog_link_nonce');
                
                if (empty($version_info->download_link)) {
                    printf(
                            __('There is a new version of %1$s available. <a target="_blank" class="thickbox" href="%2$s">View version %3$s details</a>.', 'ocean-extra'), esc_html($version_info->name), esc_url($changelog_link), esc_html($version_info->new_version)
                    );
                } else {
                    printf(
                            __('There is a new version of %1$s available. <a target="_blank" class="thickbox" href="%2$s">View version %3$s details</a> or <a href="%4$s">update now</a>.', 'ocean-extra'), esc_html($version_info->name), esc_url($changelog_link), esc_html($version_info->new_version), esc_url(wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=') . esc_attr($this->name), 'upgrade-plugin_' . esc_attr($this->name)))
                    );
                }

                echo '</div></td></tr>';
            }
        }

        /**
         * Calls the API and, if successfull, returns the object delivered by the API.
         *
         * @uses get_bloginfo()
         * @uses wp_remote_post()
         * @uses is_wp_error()
         *
         * @param string  $_action The requested action.
         * @param array   $_data   Parameters for the API action.
         * @return false|object
         */
        private function api_request($_action, $_data) {

            global $wp_version;

            $data = array_merge($this->api_data, $_data);

            if ($data['slug'] != $this->slug)
                return;

            if (empty($this->license_key))
                return;

            if ($this->api_url == home_url()) {
                return false; // Don't allow a plugin to ping itself
            }

            $api_params = array(
                'edd_action' => 'get_version',
                'license' => $this->license_key,
                'item_name' => isset($this->item_name) ? $this->item_name : false,
                'item_id' => isset($this->item_id) ? $this->item_id : false,
                'slug' => $data['slug'],
                'author' => $this->author,
                'url' => home_url()
            );

            $request = wp_remote_post($this->api_url, array('timeout' => 15, 'sslverify' => false, 'body' => $api_params));

            if (!is_wp_error($request)) {
                $request = json_decode(wp_remote_retrieve_body($request));
            }

            if ($request && isset($request->sections)) {
                $request->sections = maybe_unserialize($request->sections);
            } else {
                $request = false;
            }

            return $request;
        }

        /**
         * Show for changelog
         *
         * @access  private
         * @return  void
         */
        public function oceanwp_show_changelog() {
            if (!current_user_can('manage_options') ||
            ! isset($_REQUEST['_wpnonce']) ||
            (isset($_REQUEST['_wpnonce'])&&!wp_verify_nonce($_REQUEST['_wpnonce'], 'changelog_link_nonce')))
                return;
        
            if (empty($_REQUEST['edd_sl_action']) || 'view_plugin_changelog' != $_REQUEST['edd_sl_action']) {
                return;
            }

            if (empty($_REQUEST['plugin'])) {
                return;
            }

            if (empty($_REQUEST['slug'])) {
                return;
            }

            if (!current_user_can('update_plugins')) {
                wp_die(esc_html__('You do not have permission to install plugin updates', 'ocean-extra'), esc_html__('Error', 'ocean-extra'), array('response' => 403));
            }

            $response = $this->api_request('plugin_latest_version', array('slug' => esc_url_raw(wp_unslash($_REQUEST['slug']))));

            if ($response && isset($response->sections['changelog'])) {
                echo '<div style="background:#fff;padding:10px;">' . esc_attr($response->sections['changelog']) . '</div>';
            }
            exit;
        }

        /**
         * Update response
         *
         * @access  public
         * @return  void
         */
        public function oceanwp_update_response($item_shortname, $license_data) {

            //Build license data and update it
            $license_details = get_option('edd_license_details');
            $license_details = !empty($license_details) ? $license_details : array();
            $license_details[$item_shortname] = $license_data;
            $this->update_oceanwp_option('edd_license_details', $license_details);
        }

        /**
         * Delete response
         *
         * @access  public
         * @return  void
         */
        public function oceanwp_delete_response($item_shortname) {

            //Remove license data and update it
            $license_details = get_option('edd_license_details');
            $license_details = !empty($license_details) ? $license_details : array();
            if (!empty($license_details[$item_shortname])) {
                unset($license_details[$item_shortname]);
                $this->update_oceanwp_option('edd_license_details', $license_details);
            }
        }

    }

}