<?php
	/**
	 * Plugin Name:       VisaNetGT Pago en Linea
	 * Plugin URI:        https://shopshop.com.gt/
	 * Description:       Te permite recibir pagos con VisaCuotas de Visanet Guatemala
	 * Version:           1.0
	 * Author:            ShopShop
	 * Author URI:        https://wordpress.shopshop.com.gt
	 * */

    // This is the secret key for API authentication. You configured it in the settings menu of the license manager plugin.
	define('SHOPSOHP_PLUGIN_SECRET_KEY', '5e285904c5ed55.19784441'); //Rename this constant name so it is specific to your plugin or theme.

	// This is the URL where API query request will be sent to. This should be the URL of the site where you have installed the main license manager plugin. Get this value from the integration help page.
	define('SHOPSOHP_PLUGIN_SERVER_URL', 'https://wordpress.shopshop.com.gt'); //Rename this constant name so it is specific to your plugin or theme.

	// This is a value that will be recorded in the license manager data so you can identify licenses for this item/product.
	define('SHOPSOHP_PLUGIN_ITEM_REFERENCE', 'Payment ShopShop'); //Rename this constant name so it is specific to your plugin or theme.

    add_action( 'admin_menu', 'credenciales_id_menu' );

	// Crear WordPress admin menu
	if ( ! function_exists( "credenciales_id_menu" ) ) {
		function credenciales_id_menu() {

			$page_title = 'Mis Credenciales';
			$menu_title = 'Mis Credenciales';
			$capability = 'manage_options';
			$menu_slug  = 'configuracion-credenciales';
			$function   = 'credenciales_page';
			$icon_url   = 'dashicons-media-code';
			$position   = 4;

			add_menu_page( $page_title,
				$menu_title,
				$capability,
				$menu_slug,
				$function,
				$icon_url,
                $position );

            add_action( 'admin_init', 'update_credenciales' );
         }
    }
    
    // Crear funcion para agregar datos a la bd
	if ( ! function_exists( "update_credenciales" ) ) {
		function update_credenciales() {
            register_setting( 'credenciales_id-settings', 'credenciales_terminal' );
            update_option( 'credenciales_id-settings', 'credenciales_terminal' );

            register_setting( 'credenciales_id-settings', 'credenciales_merchant' );
            update_option( 'credenciales_id-settings', 'credenciales_merchant' );

            register_setting( 'credenciales_id-settings', 'credenciales_user' );
            update_option( 'credenciales_id-settings', 'credenciales_user' );

            register_setting( 'credenciales_id-settings', 'credenciales_password' );
            update_option( 'credenciales_id-settings', 'credenciales_password' );

            register_setting( 'credenciales_id-settings', 'tipo_transaccion' );
            update_option( 'credenciales_id-settings', 'tipo_transaccion' );

            register_setting( 'credenciales_id-settings', 'visa_vc00' );
            register_setting( 'credenciales_id-settings', 'visa_vc02' );
            register_setting( 'credenciales_id-settings', 'visa_vc03' );
			register_setting( 'credenciales_id-settings', 'visa_vc06' );
			register_setting( 'credenciales_id-settings', 'visa_vc09' );
			register_setting( 'credenciales_id-settings', 'visa_vc10' );
			register_setting( 'credenciales_id-settings', 'visa_vc12' );
			register_setting( 'credenciales_id-settings', 'visa_vc15' );
			register_setting( 'credenciales_id-settings', 'visa_vc18' );		

           
        }
    }

	if ( ! function_exists( 'get_option_vc_group' ) ) {
		function get_option_vc_group() {
			global $wpdb;
			$sql     = "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '%visa_vc%'";
			$results = $wpdb->get_results( $sql );

			return $results;
		}

	}

    // Crear pagina plugin
	if ( ! function_exists( "credenciales_page" ) ) {

		function credenciales_page() {
			?>
        <div class="warp">
                <h2>Ingresa la clave de activación</h2>
            <?php
                /*** License activate button was clicked ***/
            if (isset($_REQUEST['activate_license'])) {
                $license_key = $_REQUEST['license_key'];
                                            
                // API query parameters
                $api_params = array(
                    'slm_action' => 'slm_activate',
                    'secret_key' => SHOPSOHP_PLUGIN_SECRET_KEY,
                    'license_key' => $license_key,
                    'registered_domain' => $_SERVER['SERVER_NAME'],
                    'item_reference' => urlencode(SHOPSOHP_PLUGIN_ITEM_REFERENCE),
                );

                // Send query to the license manager server
                $query = esc_url_raw(add_query_arg($api_params, SHOPSOHP_PLUGIN_SERVER_URL));
                $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

                // Check for error in the response
                if (is_wp_error($response)){
                    echo "¡Error inesperado! La consulta regresó con un error.";
                }

                //var_dump($response);//uncomment it if you want to look at the full response

                // License data.
                $license_data = json_decode(wp_remote_retrieve_body($response));

                // TODO - Do something with it.
                //var_dump($license_data);//uncomment it to look at the data

                if($license_data->result == 'success'){//Success was returned for the license activation

                    //Uncomment the followng line to see the message that returned from the license server
                    echo '<h2> Licencia valida </h2>'.$license_data->message;

                    //Save the license key in the options table
                    update_option('license_key', $license_key);
                    ?>
                    <h1>Registra Tus Credenciales</h1>
                <form method="post" action="options.php">
            <?php
                    settings_fields( 'credenciales_id-settings' );
                    do_settings_sections( 'credenciales_id-settings' );

            ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th style="width:100px;" scope="row"><label for="terminal_id">TERMINAL:</label></th>
                            <td><input id="terminal_id" type="text" name="credenciales_terminal" class="input" value="<?php echo get_option( 'credenciales_terminal' ); ?>" required autocomplete="off" /></td>
                        </tr>
                    </table>
                    <br>
                    <table class="form-table">
                        <tr valign="top">
                            <th style="width:100px;" scope="row"><label for="merchant">MERCHANT:</label></th>
                            <td><input id="merchant" type="text" name="credenciales_merchant" value="<?php echo get_option( 'credenciales_merchant' ); ?>" required autocomplete="off" /></td>
                        </tr>
                    </table>
                    <br>
                    <table class="form-table">
                        <tr valign="top">
                            <th style="width:100px;"><label for="user">USUARIO:</label></th>
                            <td><input id="user" type="text" name="credenciales_user" value="<?php echo get_option( 'credenciales_user' ); ?>" required autocomplete="off"/></td>
                        </tr>
                    </table>
                    <br>
                    <table class="form-table">
                        <tr valign="top">
                            <th style="width:100px;" scope="row"><label for="password">PASSWORD:</label></th>
                            <td><input id="password" type="text" name="credenciales_password" value="<?php echo get_option( 'credenciales_password' ); ?>" required autocomplete="off"/></td>
                        </tr>
                    </table>
                    <br>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><label for="tipo_transaccion">Tipo transacción:</label></th>
                            <td>
                                <select name="tipo_transaccion" id="tipo_transaccion" required>
                                    <?php
                                    $tipoTransaccion = get_option( 'tipo_transaccion' );

                                    switch ( $tipoTransaccion ) {
                                        case 'cybersource':
                                            ?>
                                            <option value="cybersource" selected>Cybersource</option>
                                            <option value="epay">Epay</option>
                                            <?php
                                            break;
                                        case 'epay':
                                            ?>
                                            <option value="cybersource">Cybersource</option>
                                            <option value="epay" selected>Epay</option>
                                            <?php
                                            break;
                                        default:
                                            ?>
                                            <option value="cybersource">Cybersource</option>
                                            <option value="epay" selected>Epay</option>
                                            <?php
                                            break;
                                    }
                                    ?>
                                </select>
                        </tr>
                    </table>
                    <br>
                    <h2>
                        VisaCuotas | Contado
                    </h2>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><label for="contado">Selecciona las cuotas que deseas manejar</label></th>
                            <td>
                                <p>
                                    <label for="visa_contado">Contado</label>
                                    <?php if ( get_option( 'visa_vc00' ) ): ?>
                                        <input type="checkbox" id="visa_vc00" name="visa_vc00" value="<?php echo get_option( 'visa_vc00' ); ?>" checked="true" required>
                                    <?php else: ?>
                                        <input type="checkbox" id="visa_vc00" name="visa_vc00" value="NO" required>
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td>
                                <p>
                                    <label for="visa_vc02">2 Visacuotas</label>
                                    <?php if ( get_option( 'visa_vc02' ) ): ?>
                                        <input type="checkbox" id="visa_vc02" name="visa_vc02" value="<?php echo get_option( 'visa_vc02' ); ?>" checked="true">
                                    <?php else: ?>
                                        <input type="checkbox" id="visa_vc02" name="visa_vc02" value="VC02">
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td>
                                <p>
                                    <label for="visa_vc03">3 Visacuotas</label>
                                    <?php if ( get_option( 'visa_vc03' ) ): ?>
                                        <input type="checkbox" id="visa_vc03" name="visa_vc03" value="<?php echo get_option( 'visa_vc03' ); ?>" checked="true">
                                    <?php else: ?>
                                        <input type="checkbox" id="visa_vc03" name="visa_vc03" value="VC03">
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td>
                                <p>
                                    <label for="visa_vc06">6 cuotas</label>
                                    <?php if ( get_option( 'visa_vc06' ) ): ?>
                                        <input type="checkbox" id="visa_vc06" name="visa_vc06" value="<?php echo get_option( 'visa_vc06' ); ?>" checked="true">
                                    <?php else: ?>
                                        <input type="checkbox" id="visa_vc06" name="visa_vc06" value="VC06">
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td>
                                <p>
                                    <label for="visa_vc09">9 cuotas</label>
                                    <?php if ( get_option( 'visa_vc09' ) ): ?>
                                        <input type="checkbox" id="visa_vc09" name="visa_vc09" value="<?php echo get_option( 'visa_vc09' ); ?>" checked="true">
                                    <?php else: ?>
                                        <input type="checkbox" id="visa_vc09" name="visa_vc09" value="VC09">
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td>
                                <p>
                                    <label for="visa_vc10">10 cuotas</label>
                                    <?php if ( get_option( 'visa_vc10' ) ): ?>
                                        <input type="checkbox" id="visa_vc10" name="visa_vc10" value="<?php echo get_option( 'visa_vc10' ); ?>" checked="true">
                                    <?php else: ?>
                                        <input type="checkbox" id="visa_vc10" name="visa_vc10" value="VC10">
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td>
                                <p>
                                    <label for="visa_vc12">12 cuotas</label>
                                    <?php if ( get_option( 'visa_vc12' ) ): ?>
                                        <input type="checkbox" id="visa_vc12" name="visa_vc12" value="<?php echo get_option( 'visa_vc12' ); ?>" checked="true">
                                    <?php else: ?>
                                        <input type="checkbox" id="visa_vc12" name="visa_vc12" value="VC12">
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td>
                                <p>
                                    <label for="visa_vc15">15 cuotas</label>
                                    <?php if ( get_option( 'visa_vc15' ) ): ?>
                                        <input type="checkbox" id="visa_vc15" name="visa_vc15" value="<?php echo get_option( 'visa_vc15' ); ?>" checked="true">
                                    <?php else: ?>
                                        <input type="checkbox" id="visa_vc15" name="visa_vc15" value="VC15">
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td>
                                <p>
                                    <label for="visa_vc18">18 cuotas</label>
                                    <?php if ( get_option( 'visa_vc18' ) ): ?>
                                        <input type="checkbox" id="visa_vc18" name="visa_vc18" value="<?php echo get_option( 'visa_vc18' ); ?>" checked="true">
                                    <?php else: ?>
                                        <input type="checkbox" id="visa_vc18" name="visa_vc18" value="VC18">
                                    <?php endif; ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form> <?php
                }
                else{
                    //Show error to the user. Probably entered incorrect license key.

                    //Uncomment the followng line to see the message that returned from the license server
                    echo '<h2>El siguiente mensaje fue devuelto por el servidor:<h2> '.$license_data->message;
                }

            }
            /*** End of license activation ***/

            /*** License activate button was clicked ***/
            if (isset($_REQUEST['deactivate_license'])) {
                $license_key = $_REQUEST['license_key'];

                // API query parameters
                $api_params = array(
                    'slm_action' => 'slm_deactivate',
                    'secret_key' => SHOPSOHP_PLUGIN_SECRET_KEY,
                    'license_key' => $license_key,
                    'registered_domain' => $_SERVER['SERVER_NAME'],
                    'item_reference' => urlencode(SHOPSOHP_PLUGIN_ITEM_REFPLUGIN),
                );

                // Send query to the license manager server
                $query = esc_url_raw(add_query_arg($api_params, SHOPSOHP_PLUGIN_SERVER_URL));
                $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

                // Check for error in the response
                if (is_wp_error($response)){
                    echo "<h2¡Error inesperado! La consulta regresó con un error.</h2>";
                }

                //var_dump($response);//uncomment it if you want to look at the full response

                // License data.
                $license_data = json_decode(wp_remote_retrieve_body($response));

                // TODO - Do something with it.
                //var_dump($license_data);//uncomment it to look at the data

                if($license_data->result == 'success'){//Success was returned for the license activation

                    //Uncomment the followng line to see the message that returned from the license server
                    echo '<br /><h2>El siguiente mensaje fue devuelto por el servidor:</h2> '.$license_data->message;

                    //Remove the licensse key from the options table. It will need to be activated again.
                    update_option('license_key', '');
                }
                else{
                    //Show error to the user. Probably entered incorrect license key.

                    //Uncomment the followng line to see the message that returned from the license server
                    echo '<br /><h2>El siguiente mensaje fue devuelto por el servidor: </h2>'.$license_data->message;
                }

            }
            /*** End of sample license deactivation ***/

            ?>
            <p>Ingrese la clave de licencia de este producto para activarlo. Le dieron una clave de licencia cuando compró este artículo.</p>
            <form action="" method="post">
                <table class="form-table">
                    <tr>
                        <th style="width:100px;"><label for="license_key">Clave de licencia</label></th>
                        <td ><input class="regular-text" type="text" id="license_key" name="license_key"  value="<?php echo get_option('license_key'); ?>" ></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="activate_license" value="Activate" class="button-primary" />
                    <input type="submit" name="deactivate_license" value="Deactivate" class="button" />
                </p>
                <p>SI DESEA VERIFICAR CREDENCIALES O MODIFICAR, DESACTIVE LA LICENCIA (copiela ctrl+c) Y VUELVA A ACTIVARLA (peguela ctrl+v)</p>
                <h3>Solicita tu Licencia poniendote en contacto con nosotros </h3>
                <h3> email:  ventas@shopshop.com.gt    <br> Teléfono: +502 2212 9728 <br> <a href="https://shopshop.com.gt/">Visita nuesta Web</a> </h3>
            </form>
        </div>
                
     <?php
        
		}
    }
     // estamos diciendo a WC que exsite nutra clase
     function wc_PLUGIN_add_to_gateways( $gateways ) {
        $gateways[] = 'Shopshop_Gateway_PLUGIN';

        return $gateways;
    }

    add_filter( 'woocommerce_payment_gateways', 'wc_PLUGIN_add_to_gateways' );


    add_action( 'plugins_loaded', 'wc_PLUGIN_gateway_init', 11 );

    function wc_PLUGIN_gateway_init() {
        // hacemos que nuestra clase extienda de la clase WC_Payment_Gateway ya que contiene methods impotantes
        class Shopshop_Gateway_PLUGIN extends WC_Payment_Gateway {
            /**
             * Constructor for the gateway.
             */
            // en nuestro constructor necesitamos definir las siguientes variables
            public function __construct() {

                $this->id                 = 'PLUGIN_gateway';
                $this->icon               = apply_filters( 'woocommerce_PLUGIN_icon', '' );
                $this->has_fields         = true;
                $this->method_title       = __( 'VisaCuotas', 'payment-online' );
                $this->method_description = __( 'Te permite recibir pagos con VisaCuotas', 'payment-online' );

                // Load the settings. cargando compos de configuiracion
                $this->init_form_fields();
                $this->init_settings();

                // Define user set variables, estamos establesiendo los datos
                $this->title        = $this->get_option( 'title' );
                $this->description  = $this->get_option( 'description' );
                $this->instructions = $this->get_option( 'instructions', $this->description );

                // Actions Agregamos un enlace para guardar la configuración
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

                // Customer Emails
                add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
            }

            /**
             * Initialize Gateway Admin Area Settings Form Fields
             */
            // establesemos $this->form_fields

            public function init_form_fields() {

                $this->form_fields = apply_filters( 'wc_PLUGIN_form_fields', array(

                    'enabled' => array(
                        'title'   => __( 'Habilitar/deshabilitar', 'payment-online' ),
                        'type'    => 'text',
                        'label'   => __( 'Habilitar pago por Visacuotas', 'payment-online' ),
                        'default' => 'yes'
                    ),

                    'title' => array(
                        'title'       => __( 'Titulo', 'payment-online' ),
                        'type'        => 'text',
                        'description' => __( 'Esto controla el título del método de pago que el cliente ve durante el pago.', 'payment-online' ),
                        'default'     => __( 'Pago con tarjeta', 'payment-online' ),
                        'desc_tip'    => true,
                    ),

                    'description' => array(
                        'title'       => __( 'Descripción', 'payment-online' ),
                        'type'        => 'textarea',
                        'description' => __( 'Descripción del método de pago que el cliente verá en su pago.', 'payment-online' ),
                        'default'     => __( 'Envíe el pago al nombre de la tienda al momento del retiro o la entrega.', 'payment-online' ),
                        'desc_tip'    => true,
                    ),

                    'instructions' => array(
                        'title'       => __( 'Instrucciones', 'payment-online' ),
                        'type'        => 'textarea',
                        'description' => __( 'Instrucciones que se agregarán a la página de agradecimiento y correos electrónicos.', 'payment-online' ),
                        'default'     => '',
                        'desc_tip'    => true,
                    ),

                ) );
            }


            /**
             * Output for the order received page.
             */
            public function thankyou_page() {
                if ( $this->instructions ) {
                    echo wpautop( wptexturize( $this->instructions ) );
                }
            }

            /**
             * Add content to the WC emails.
             *
             * @access public
             *
             * @param WC_Order $order
             * @param bool $sent_to_admin
             * @param bool $plain_text
             */
            public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
                if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
                    echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
                }
            }

            /**
             * Process the payment and return the result
             *
             * @param int $order_id
             *
             * @return array
             */

            public function payment_fields() {
                if ( $this->supports( 'tokenization' ) && is_checkout() ) {
                    $this->tokenization_script();
                    $this->saved_payment_methods();
                    $this->form();
                    $this->save_payment_method_text();
                } else {
                    $this->form();
                }
            }

            /**
             * Output field name HTML
             *
             * Gateways which support tokenization do not require names - we don't want the data to post to the server.
             *
             * @param string $name
             *
             * @return string
             * @since  2.6.0
             */
            public function field_name( $name ) {
                return $this->supports( 'tokenization' ) ? '' : ' name="' . esc_attr( $this->id . '-' . $name ) . '" ';
            }

            /**
             * Outputs fields for entering credit card information.
             */

            public function form() {
                echo esc_attr( $this->description );

                wp_enqueue_script( 'wc-credit-card-form' );

                $fields      = array();
                $optionsVC   = get_option_vc_group();
                $optionsHTML = '';
                $nameVC      = [
                    'NO'   => 'Contado',
                    'VC02' => '2 Visacuotas',
                    'VC03' => '3 Visacuotas',
                    'VC06' => '6 Visacuotas',
                    'VC09' => '9 Visacuotas',
                    'VC10' => '10 Visacuotas',
                    'VC12' => '12 Visacuotas',
                    'VC15' => '15 Visacuotas',
                    'VC18' => '18 Visacuotas',

                ];
                foreach ( $optionsVC as $vc ) {
                    if ( $vc->option_value ) {
                        $nameInnerVC = $nameVC[ $vc->option_value ];
                        $optionsHTML .= "<option value='$vc->option_value'>$nameInnerVC</option>";
                    }
                }

                $vcq_field = '<p class="form-row form-row-wide"><br>
                <label for="' . esc_attr( $this->id ) . '-card-type">' . __( 'Elige tus VisaCuotas', 'woocommerce' ) . ' <span class="required">*</span></label>
                <select onchange="gt_alarma()" id="' . esc_attr( $this->id ) . '-vcq" class="wc-credit-card-form-card-type" ' . $this->field_name( 'vcq' ) . ' >
                ' . $optionsHTML . '
                </select>
                </p>';
                $cvc_field = '<p class="form-row form-row-last">
                <label for="' . esc_attr( $this->id ) . '-card-cvc">' . esc_html__( 'Código', 'woocommerce' ) . ' <span class="required">*</span></label>
                <input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" ' . $this->field_name( 'card-cvc' ) . ' style="width:100px" />
                </p>';

                $default_fields = array(
                'card-number-field' => '<p class="form-row form-row-wide"> <br>
                <label for="' . esc_attr( $this->id ) . '-card-number">' . esc_html__( 'Card number', 'woocommerce' ) . ' <span class="required">*</span></label>
                <input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' />
                </p>',
                'card-expiry-field' => '<p class="form-row form-row-first">
                <label for="' . esc_attr( $this->id ) . '-card-expiry">' . esc_html__( 'Vence', 'woocommerce' ) . ' <span class="required">*</span></label>
                <input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="' . esc_attr__( 'MM/YYYY', 'woocommerce' ) . '" ' . $this->field_name( 'card-expiry' ) . ' />
                </p>',
                );

                if ( ! $this->supports( 'credit_card_form_cvc_on_saved_method' ) ) {
                    $default_fields['card-cvc-field'] = $cvc_field;
                }
                $fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
                ?>

                <fieldset>
                    <?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
                    <?php

                    echo $vcq_field;
                    foreach ( $fields as $field ) {
                        echo $field;
                    }
                    ?>
                    <?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
                    <div class="clear"></div>
                </fieldset>
                <?php

                if ( $this->supports( 'credit_card_form_cvc_on_saved_method' ) ) {
                    echo '<fieldset>' . $cvc_field . '</fieldset>';

                }

            }

            function gt_alarma() {
                wc_add_notice( 'Exitosa', 'success' );
            }

            public function process_payment( $order_id ) {

                global $woocommerce;
                // almacenamos los datos de la compra en $order
                $order = wc_get_order( $order_id );
                // traemos la data del form
                $tmk_card     = woocommerce_clean( $_POST[ $this->id . '-card-number' ] );
                $tmk_card     = str_replace( ' ', '', $tmk_card );
                $tmk_card_cvc = woocommerce_clean( $_POST[ $this->id . '-card-cvc' ] );
                $year         = woocommerce_clean( $_POST[ $this->id . '-card-expiry' ] );
                $tmk_card_exp = $year[5] . $year[6] . $year[0] . $year[1];
                wc_add_notice( $_POST[ $this->id ], 'error' );
                $tmk_email          = $order->get_billing_email();
                $tmk_email          = str_replace( ' ', '', $tmk_email );
                $tmk_remote_address = WC_Geolocation::get_ip_address();
                // de donde sale get_billing_first_name y get_billing_last_name?
                $tmk_name        = $order->get_billing_first_name() . $order->get_billing_last_name();
                $tmk_name        = str_replace( ' ', '', $tmk_name );
                $tmk_order_total = $order->get_total();
                $tmk_order_total = $tmk_order_total * 100;
                $tmk_vcq         = woocommerce_clean( $_POST[ $this->id . '-vcq' ] );



                $typeTransacction = get_option('tipo_transaccion');

                switch ($typeTransacction){
                    case 'epay':
                        break;
                    case 'cybersource':
                        break;
                }

                //inicia consumo
                $test        = 0;
                $merchant    = get_option( 'credenciales_merchant' );
                $serverAddr  = $_SERVER['SERVER_ADDR'];
                $remoteAddr  = $_SERVER['REMOTE_ADDR'];
                $terminal    = get_option( 'credenciales_terminal' );
                $user        = get_option( 'credenciales_user' );
                $password    = get_option( 'credenciales_password' );
                $messageType = '0200';
                $wsShopShop  = "https://ws.shopshop.com.gt/api_gtm3.php?MERCHANT=$merchant&NOMBRE=$tmk_name&EMAIL=$tmk_email&TARJETA=$tmk_card&CVV=$tmk_card_cvc&VENCE=$tmk_card_exp&MONTO=$tmk_order_total&VISACUOTAS=$tmk_vcq&REMOTEADDR=$tmk_remote_address&SERVERADDR=$serverAddr&TERMINAL=$terminal&TEST=$test&USER=$user&PASSWORD=$password&messageType=$messageType";
                //  Crea un nuevo recurso cURL
                $curl = curl_init();
                curl_setopt_array( $curl, array(
                    CURLOPT_URL            => $wsShopShop,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING       => "",
                    CURLOPT_MAXREDIRS      => 10,
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST  => "GET",


                ) );

                $response = curl_exec( $curl );
                $err      = curl_error( $curl );

                curl_close( $curl );

                if ( $err ) {
                    $response = 'RESULT=ERR&NUMREF=ERR&NUMAUTO=ERR';
                }

                $autorizacion = $response;
                $respuesta    = $autorizacion[7] . $autorizacion[8];
                //termina consumo

                // validamos que el pago se completo
                if ( $respuesta == '00' ) {
                    wc_add_notice( "Compra Exitosa: $autorizacion", 'success' );
                } else {
                    wc_add_notice( __( 'Error: ', 'woothemes' ) . 'Operación rechazada', 'error' );
                   // wc_add_notice( $autorizacion, 'error' );
                  //  wc_add_notice( $wsShopShop, 'error' );

                    return;
                }

                // Mark as on-hold (we're awaiting the payment)
                $order->update_status( 'on-hold', __( 'Awaiting PLUGIN payment', 'payment-online' ) );

                // Reduce stock levels
                $order->reduce_order_stock();

                // Remove cart
                WC()->cart->empty_cart();

                // Return thankyou redirect
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order )
                );


            }


        } // end Shopshop_Gateway_PLUGIN class
    }