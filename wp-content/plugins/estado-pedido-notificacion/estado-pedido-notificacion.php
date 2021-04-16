<?php

/**
 *
 * Plugin Name: 			Estados de pedido con notificación
 * Description: 			Permite añadir nuevos estados de pedido personalizados y asignarles una notificación tanto para el cliente como para el administrador
 * Plugin URI: 				https://www.enriquejros.com/plugins/estados-pedido-notificacion-woocommerce/
 * Author: 					Enrique J. Ros
 * Author URI: 				https://www.enriquejros.com/
 * Version: 				3.3.0
 * License: 				Copyright 2018 - 2021 Enrique J. Ros (email: enrique@enriquejros.com)
 * Text Domain: 			estados-pedido
 * Domain Path: 			/lang/
 * Requires at least:		5.0
 * Tested up to:			5.7
 * Requires PHP: 			7.0
 * WC requires at least:	3.0
 * WC tested up to: 		5.1
 *
 * @author 					Enrique J. Ros
 * @link 					https://www.enriquejros.com
 * @since 					1.0.0
 * @package 				EstadosPedido
 *
 */

defined ('ABSPATH') or exit;

define ('VERSION_PLUGIN_ESTADOS_PEDIDO', '3.3.0');
define ('ASSETS_ESTADOS_PEDIDO', plugins_url ('assets/', __FILE__));
define ('URL_LICENCIA_ESTADOS_PEDIDO', 'https://www.enriquejros.com/checkout/?edd_action=add_to_cart&download_id=18186');

if (!class_exists ('Plugin_Estados_Pedido_Personalizados')) :

	Class Plugin_Estados_Pedido_Personalizados {

		private static $instancia;

		private function __construct () {

			$this->nombre   = __('Estados de pedido con notificación', 'estados-pedido');
			$this->campos   = 'Campos_Estados_Pedido';
			$this->domain   = 'estados-pedido';
			$this->archivos = ['cpt', 'estados', 'dashboard', 'eddsl', 'desactivacion', 'updates', 'email', 'auto'];
			$this->clases   = ['Estados_Pedido_CPT', 'Estados_Pedido_Personalizados', 'Dashboard_Estados_Pedido', 'Estados_Pedido_Desactivacion', 'Emails_Estados_Pedido', 'Automatizaciones_Estados_Pedido'];
			$this->dirname  = dirname (__FILE__);
			$this->basename = plugin_basename ( __FILE__ );

			$this->carga_archivos();
			$this->actualizaciones();
			$this->carga_traducciones();

			$this->gestor   = 'edit.php?post_type=' . Estados_Pedido_CPT::CPT;

			register_activation_hook (__FILE__, function () {
				set_transient ('estados-pedido-activado', true, 5);
				}, 10);

			add_action ('init', [$this, 'arranca_plugin'], 10);
			add_action ('admin_init', [$this, 'comprueba_acf'], 10);
			add_action ('admin_notices' , [$this, 'mostrar_avisos'], 10);

			add_filter ('plugin_action_links', [$this, 'enlaces_accion'], 10, 2);
			add_filter ('plugin_row_meta', [$this, 'enlace_changelog'], 10, 2);
			}

		public function __clone () {

			_doing_it_wrong (__FUNCTION__, sprintf ('No puedes clonar instancias de %s.', get_class ($this)), '1.4.1');
			}

		private function carga_archivos () {

			foreach ($this->archivos as $archivo)
				require (sprintf ('%s/src/%s.php', $this->dirname, $archivo));

			if ($this->campos) {

				if (!class_exists ('acf')) {

					/**
					 * Para deshabilitar la constante ACF_LITE definida por el plugin:
					 *
					 * add_filter ('ejr_acf_lite', '__return_false');
					 *
					 */
					if (!defined ('ACF_LITE') && 'no' !== get_option ('acf_lite'))
						add_action ('after_setup_theme', function () {
							define ('ACF_LITE', apply_filters ('ejr_acf_lite', true));
							}, 10);

					require ($this->dirname . '/includes/acf-pro/acf.php');
					}
					
				require ($this->dirname . '/src/campos.php');
				array_push ($this->clases, $this->campos);
				}
			}

		public function arranca_plugin () {

			if ($this->woocommerce_activo())
				foreach ($this->clases as $clase)
					new $clase;
			}

		public function comprueba_acf () {

			if (is_plugin_active ('advanced-custom-fields/acf.php')) {

				deactivate_plugins ('advanced-custom-fields/acf.php');
				update_option ('acf_lite', 'no');
				defined ('ACF_LITE') or define ('ACF_LITE', false);

				add_action ('admin_notices', function () {
					?>
						<div class="notice notice-error is-dismissible">
							<p><?php printf (__('%s ya incluye Advanced Custom Fields PRO, por lo que no necesitas tener activada la versión gratuita de ACF. Mientras uses %s podrás utilizar las características PRO de ACF.', 'estados-pedido'), '<i>' . $this->nombre . '</i>', '<i>' . $this->nombre . '</i>'); ?></p>
						</div>
					<?php
					}, 10);
				}
			}

		private function woocommerce_activo () {

			if (!class_exists ('WooCommerce')) {

				add_action ('admin_notices', function () {
					?>
						<div class="notice notice-error is-dismissible">
							<p><?php printf (__('El plugin %s necesita que WooCommerce esté activado. Por favor, activa WooCommerce primero.', 'estados-pedido'), '<i>' . $this->nombre . '</i>'); ?></p>
						</div>
					<?php
					}, 10);

				return false;
				}

			return true;
			}

		public function mostrar_avisos () {

			if (class_exists ('WooCommerce') && get_transient ('estados-pedido-activado')) {

				if (!get_option ('estados_pedido_licencia')) {

					?>
						<div class="updated notice is-dismissible woocommerce-message">
							<p><?php printf (__('Gracias por usar %s. Puedes comenzar creando algún estado de pedido personalizado. No olvides activar tu licencia para tener acceso a soporte y actualizaciones.', 'estados-pedido'), '<i>' . $this->nombre . '</i>'); ?></p>
							<p><?php printf ('<a href="%s" class="button button-primary">%s</a>', $this->gestor, __('Gestionar estados de pedido', 'estados-pedido')); ?></p>
						</div>
					<?php
					}

				else {

					?>
						<div class="updated notice is-dismissible woocommerce-message">
							<p><?php printf (__('Gracias por usar %s. Puedes comenzar creando algún estado de pedido personalizado.', 'estados-pedido'), '<i>' . $this->nombre . '</i>'); ?></p>
							<p><?php printf ('<a href="%s" class="button button-primary">%s</a>', $this->gestor, __('Gestionar estados de pedido', 'estados-pedido')); ?></p>
						</div>
					<?php
					}
				}

			else if (class_exists ('WooCommerce')) { //Si no está el transient

				$datos_licencia = $this->estado_licencia();
				$pantalla       = get_current_screen()->id;

				if (isset ($_GET['post_type']) && Estados_Pedido_CPT::CPT == $_GET['post_type']) {

					if ($datos_licencia) {

						switch ($datos_licencia['estado']) {

							case 'valid':

								?>
									<div class="notice notice-success">
										<p><?php printf (__('Tu licencia está activada y es válida hasta el %s.', 'estados-pedido'), date_i18n (get_option ('date_format'), $datos_licencia['expira'])); ?></p>
									</div>
								<?php

								break;

							case 'vacio':

								?>
									<div class="error notice" id="estados-pedido-error-licencia">
										<p class="licencia-novalida-estados-pedido"><?php printf (__('Activa tu licencia para poder recibir soporte y actualizaciones. Si no tienes una clave de licencia válida, consíguela %saquí%s.', 'estados-pedido'), '<a target="_blank" href="' . URL_LICENCIA_ESTADOS_PEDIDO . '">', '</a>'); ?></p>
										<p class="licencia-novalida-estados-pedido"><input type="text" id="clave-licencia-estados-pedido"> <input type="button" class="button button-primary" id="boton-licencia-estados-pedido" value="<?php _e('Activar licencia', 'estados-pedido'); ?>" onclick="validaLicenciaEstados();"></p>
										<p id="resultado-activacion-estados-pedido"></p>
									</div>
								<?php

								break;

							case 'invalid':
							case 'site_inactive':

								?>
									<div class="error notice" id="estados-pedido-error-licencia">
										<p class="licencia-novalida-estados-pedido"><?php printf (__('Activa tu licencia para poder recibir soporte y actualizaciones. Si no tienes una clave de licencia válida, consíguela %saquí%s.', 'estados-pedido'), '<a target="_blank" href="' . URL_LICENCIA_ESTADOS_PEDIDO . '">', '</a>'); ?></p>
										<p class="licencia-novalida-estados-pedido"><input type="text" id="clave-licencia-estados-pedido" value="<?php echo get_option ('estados_pedido_licencia'); ?>"> <input type="button" class="button button-primary" id="boton-licencia-estados-pedido" value="<?php _e('Activar licencia', 'estados-pedido'); ?>" onclick="validaLicenciaEstados();"></p>
										<p id="resultado-activacion-estados-pedido"></p>
									</div>
								<?php

								break;

							case 'expired':

								?>
									<div class="error notice" id="estados-pedido-error-licencia">
										<p class="licencia-novalida-estados-pedido"><?php printf (__('La licencia ha expirado y ya no es posible renovarla. Consigue una nueva licencia %saquí%s.', 'estados-pedido'), '<a target="_blank" href="' . URL_LICENCIA_ESTADOS_PEDIDO . '">', '</a>'); ?></p>
										<p class="licencia-novalida-estados-pedido"><input type="text" id="clave-licencia-estados-pedido" value="<?php echo get_option ('estados_pedido_licencia'); ?>"> <input type="button" class="button button-primary" id="boton-licencia-estados-pedido" value="<?php _e('Activar licencia', 'estados-pedido'); ?>" onclick="validaLicenciaEstados();"></p>
										<p id="resultado-activacion-estados-pedido"></p>
									</div>
								<?php

								break;
							}
						}
					}

				else if ('plugins' == $pantalla || 'dashboard' == $pantalla || Estados_Pedido_CPT::CPT == $pantalla) {

					if ('valid' != $datos_licencia['estado']) {

						?>
							<div class="error notice">
								<p><?php printf (__('%sActiva%s tu licencia de %s para poder recibir soporte y actualizaciones. Si no tienes una clave de licencia válida, consíguela %saquí%s.', 'estados-pedido'), '<a href="' . $this->gestor . '">', '</a>', '<i>' . $this->nombre . '</i>', '<a target="_blank" href="' . URL_LICENCIA_ESTADOS_PEDIDO . '">', '</a>'); ?></p>
							</div>
						<?php
						}
					}
				}
			}

		public function carga_traducciones () {

			$locale = function_exists ('determine_locale') ? determine_locale() : (is_admin() && function_exists ('get_user_locale') ? get_user_locale() : get_locale());
			$locale = apply_filters ('plugin_locale', $locale, $this->domain);

			unload_textdomain ($this->domain);
			load_textdomain ($this->domain, $this->dirname . '/lang/' . $this->domain . '-' . $locale . '.mo');
			load_plugin_textdomain ($this->domain, false, $this->dirname . '/lang');
			}

		public function enlaces_accion ($damelinks, $plugin) {

			if ($plugin == $this->basename) {
				
				$enlaces['ajustes'] = sprintf ('<a href="%s">%s</a>', $this->gestor, __('Gestionar estados', 'estados-pedido'));
				$enlaces['soporte'] = sprintf ('<a target="_blank" href="https://www.enriquejros.com/soporte/">%s</a>', __('Soporte', 'estados-pedido'));

				$damelinks = array_merge ($enlaces, $damelinks);
				}
			
			return $damelinks;
			}

		public function enlace_changelog ($enlaces, $plugin) {

			if ($plugin == $this->basename)
				array_push ($enlaces, sprintf ('<a target="_blank" href="https://www.enriquejros.com/changelog-estados-pedido/">%s</a>', __('Changelog', 'estados-pedido')));
			
			return $enlaces;
			}

		private function estado_licencia () {

			if ($clave = get_option ('estados_pedido_licencia')) {

				$licencia = new EDDSL_Estados_Pedido($clave);

				if (is_array ($datos_licencia = $licencia->comprueba_licencia()))
					return $datos_licencia;

				else
					return false;
				}

			else
				return ['estado' => 'vacio'];
			}

		public function actualizaciones () {

			new Updates_Estados_Pedido_Personalizados ('https://www.enriquejros.com', __FILE__,
				array(
					'version'	=> VERSION_PLUGIN_ESTADOS_PEDIDO,
					'license'	=> get_option ('estados_pedido_licencia'),
					'item_id'	=> 18186,
					'author'	=> 'Enrique J. Ros',
					'url'		=> home_url(),
					'beta'		=> false,
					)
				);
			}

		public static function instancia () {

			if (null === self::$instancia)
				self::$instancia = new self();

			return self::$instancia;
			}

		}

endif;

Plugin_Estados_Pedido_Personalizados::instancia();