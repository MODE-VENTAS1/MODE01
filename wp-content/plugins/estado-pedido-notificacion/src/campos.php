<?php

/**
 * Campos personalizados
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link 			https://www.enriquejros.com
 * @since 			1.0.0
 * @package 		EstadosPedido
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Campos_Estados_Pedido')) :

	Class Campos_Estados_Pedido {

		public function __construct () {

			//Si no se puede cargar ACF da error y sale
			if (!function_exists ('acf_add_local_field_group') && !function_exists ('register_field_group')) {

				add_action ('admin_notices', [$this, 'error_acf'], 10);
				return;
				}

			$this->inserta_campos();

			add_filter ('acf/settings/remove_wp_meta_box', '__return_false');
			}

		public function error_acf () {

			?>

				<div class="notice notice-error is-dismissible">
					<p><?php printf (__('El plugin %s no ha podido recuperar la lista de campos personalizados. Por favor, contacta con el %ssoporte%s.', 'estados-pedido'), '<i>' . __('Estados de pedido con notificación', 'estados-pedido') . '</i>', '<a target="_blank" href="https://www.enriquejros.com/soporte/">', '</a>'); ?></p>
				</div>

			<?php
			}

		public function inserta_campos () {

			$fields = array_merge (
				$this->campos_tab_aspecto(),
				$this->campos_tab_notificaciones(),
				$this->campos_tab_caracteristicas(),
				$this->campos_tab_automatizaciones()
				);

			$campos = array(
				'key' 		=> 'group_59e21e03d72ee',
				'title' 	=> sprintf ('<span style="text-align:left!important"><span class="dashicons dashicons-admin-tools"></span> %s</span>', __('Configuración del estado de pedido', 'estados-pedido')),
				'fields' 	=> $fields,
				'location'	=> array(
					array(
						array(
							'param' 	=> 'post_type',
							'operator' 	=> '==',
							'value' 	=> Estados_Pedido_CPT::CPT,
							),
						),
					),
				'menu_order' 			=> 0,
				'position' 				=> 'normal',
				'style' 				=> 'default',
				'label_placement' 		=> 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' 		=> '',
				'active' 				=> 1,
				'description' 			=> '',
				);

			function_exists ('acf_add_local_field_group') ? acf_add_local_field_group ($campos) : register_field_group ($campos);
			}

		private function campos_tab_aspecto () {

			$aspecto = array(

				//Pestaña "Aspecto"
				array(
					'key' 				=> 'field_508f351db2cb9',
					'label' 			=> __('Aspecto', 'estados-pedido'),
					'name' 				=> '',
					'type' 				=> 'tab',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'placement' 		=> 'top',
					'endpoint' 			=> 0,
					),

				//Campo de color del estado
				array(
					'key' 				=> 'field_59e21e12b35d0',
					'label' 			=> __('Color del estado de pedido', 'estados-pedido'),
					'name' 				=> 'color',
					'type' 				=> 'color_picker',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'default_value' 	=> '#2e4453',
					),
				);

			if (version_compare (WC()->version, '3.3', ">" ))

				//Campo de color del texto
				$aspecto[] = array(
					'key' 				=> 'field_59c23e12e36a0',
					'label' 			=> __('Color del texto de la etiqueta', 'estados-pedido'),
					'name' 				=> 'color_label',
					'type' 				=> 'color_picker',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'default_value' 	=> '#fff',
					);

			//Campo de icono
			$aspecto[] = array(
				'key' 				=> 'field_56e212fcf3ca0',
				'label' 			=> __('Icono', 'estados-pedido'),
				'name' 				=> 'icono',
				'type' 				=> 'radio',
				'instructions' 		=> '',
				'required' 			=> 0,
				'conditional_logic' => 0,
				'wrapper' 			=> array(
					'width' => '',
					'class' => '',
					'id' 	=> '',
					),
				'choices' 			=> $this->devuelve_iconos(),
				'allow_custom' 		=> 0,
				'save_custom' 		=> 0,
				'default_value' 	=> [],
				'layout' 			=> 'horizontal',
				'toggle' 			=> 0,
				'return_format' 	=> 'array',
				);

			return $aspecto;
			}

		private function campos_tab_notificaciones () {

			$notificaciones = array(

				//Pestaña "Notificaciones"
				array(
					'key' 				=> 'field_531f391eb2ca9',
					'label' 			=> __('Notificaciones', 'estados-pedido'),
					'name' 				=> '',
					'type' 				=> 'tab',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'placement' 		=> 'top',
					'endpoint' 			=> 0,
					),

				//Campo de notificaciones
				array(
					'key' 				=> 'field_59e232bcf0cf0',
					'label' 			=> __('Activar notificaciones para:', 'estados-pedido'),
					'name' 				=> 'notificaciones',
					'type' 				=> 'checkbox',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'choices' 			=> array(
						'admin' 	=> __('Administrador', 'estados-pedido'),
						'cliente' 	=> __('Cliente', 'estados-pedido'),
						),
					'allow_custom' 		=> 0,
					'save_custom' 		=> 0,
					'default_value' 	=> [],
					'layout' 			=> 'horizontal',
					'toggle' 			=> 0,
					'return_format' 	=> 'value',
					),

				//Campo de direcciones de administración
				array(
					'key' 				=> 'field_56c212bad3cf5',
					'label' 			=> __('Dirección de administración', 'estados-pedido'),
					'name' 				=> 'email_admin',
					'type' 				=> 'text',
					'instructions' 		=> __('Dirección de correo electrónico a la que se enviará la notificación al administrador. Admite varias direcciones separadas por punto y coma (;).', 'estados-pedido'),
					'required' 			=> 0,
					'conditional_logic' => array(
						array(
							array(
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'admin',
								),
							),
						),
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'allow_custom' 		=> 0,
					'save_custom' 		=> 0,
					'default_value' 	=> get_option ('admin_email'),
					'layout' 			=> 'horizontal',
					'toggle' 			=> 0,
					'return_format' 	=> 'value',
					),

				//Campo de asunto
				array(
					'key' 				=> 'field_59e234508e037',
					'label' 			=> __('Asunto', 'estados-pedido'),
					'name'				=> 'asunto',
					'type' 				=> 'text',
					'instructions' 		=> __('Asunto del correo electrónico. Admite variables.', 'estados-pedido'),
					'required' 			=> 1,
					'conditional_logic' => array(
						array(
							array(
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'admin',
								),
							),
						array(
							array(
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'cliente',
								),
							),
						),
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'default_value' 	=> '',
					'placeholder' 		=> '',
					'prepend' 			=> '',
					'append' 			=> '',
					'maxlength' 		=> '',
					),

				//Campo de encabezado
				array(
					'key' 				=> 'field_54b234038f037',
					'label' 			=> __('Encabezado', 'estados-pedido'),
					'name'				=> 'heading',
					'type' 				=> 'text',
					'instructions' 		=> __('Si no se establece ninguno se usará el asunto del correo electrónico. Admite variables.', 'estados-pedido'),
					'required' 			=> 0,
					'conditional_logic' => array(
						array(
							array(
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'admin',
								),
							),
						array(
							array(
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'cliente',
								),
							),
						),
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'default_value' 	=> '',
					'placeholder' 		=> '',
					'prepend' 			=> '',
					'append' 			=> '',
					'maxlength' 		=> '',
					),

				//Campo de plantilla
				array(
					'key' 				=> 'field_59e233517c3e9',
					'label' 			=> __('Plantilla', 'estados-pedido'),
					'name' 				=> 'plantilla',
					'type' 				=> 'wysiwyg',
					'instructions' 		=> class_exists ('Plugin_Seguimiento_Pedidos') ? '<p>' . __('Puedes utilizar las siguientes variables tanto en el cuerpo del mensaje como en el asunto y el encabezado:', 'estados-pedido') . '</p><table><tr><td width="50%" valign="top">' . __('<code>%%cliente%%</code> para el nombre del cliente<br><code>%%pedido%%</code> para el número de pedido<br><code>%%email%%</code> para la dirección de email del cliente<br><code>%%tabla%%</code> para la tabla resumen del pedido<br><code>%%total%%</code> para el total del pedido<br><code>%%datos_facturacion%%</code> para los datos de facturación<br><code>%%datos_envio%%</code> para la dirección de envío<br><code>%%metodo_pago%%</code> para el método de pago utilizado', 'estados-pedido') . '</td><td width="50%" valign="top">' . __('<code>%%metodo_envio%%</code> para el método de envío seleccionado<br><code>%%agencia%%</code> para el nombre de la agencia de transportes<br><code>%%codigo%%</code> para el código de seguimiento<br><code>%%fecha_envio%%</code> para la fecha de envío<br><code>%%url_envio%%</code> para la URL con la información de seguimiento<br><code>%%boton_envio%%</code> para crear un botón que lleve a la información de seguimiento', 'estados-pedido') . '</td></tr></table>' : '<p>' . __('Puedes utilizar las siguientes variables tanto en el cuerpo del mensaje como en el asunto y el encabezado:', 'estados-pedido') . '</p><table><tr><td width="50%" valign="top">' . __('<code>%%cliente%%</code> para el nombre del cliente<br><code>%%pedido%%</code> para el número de pedido<br><code>%%email%%</code> para la dirección de email del cliente<br><code>%%tabla%%</code> para la tabla resumen del pedido<br><code>%%total%%</code> para el total del pedido', 'estados-pedido') . '</td><td width="50%" valign="top">' . __('%%</code>datos_facturacion%%</code> para los datos de facturación<br><code>%%datos_envio%%</code> para la dirección de envío<br><code>%%metodo_pago%%</code> para el método de pago utilizado<br><code>%%metodo_envio%%</code> para el método de envío seleccionado', 'estados-pedido') . '</td></tr></table>',
					'required' 			=> 1,
					'conditional_logic' => array(
						array(
							array(
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'admin',
								),
							),
						array(
							array(
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'cliente',
								),
							),
						),
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'default_value' => '',
					'placeholder' 	=> '',
					'maxlength' 	=> '',
					'rows' 			=> '',
					'new_lines' 	=> 'wpautop',
					),

				//Campos de adjuntos
				array(
					'key' 				=> 'field_5ccc4c18b5b1b',
					'label' 			=> __('Adjuntar archivos', 'estados-pedido'),
					'name' 				=> 'adjuntos',
					'type' 				=> 'repeater',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => array(
						array(
							array(
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'admin',
								),
							),
						array(
							array(
								'field' 	=> 'field_59e232bcf0cf0',
								'operator' 	=> '==',
								'value' 	=> 'cliente',
								),
							),
						),
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'collapsed' 	=> 'field_5ccc4c2cb5b1c',
					'min' 			=> 1,
					'max' 			=> 0,
					'layout' 		=> 'table',
					'button_label' 	=> '',
					'sub_fields' 	=> array(
						array(
							'key' 				=> 'field_5ccc4c2cb5b1c',
							'label' 			=> __('Adjunto', 'estados-pedido'),
							'name' 				=> 'adjunto',
							'type' 				=> 'file',
							'instructions' 		=> '',
							'required' 			=> 0,
							'conditional_logic' => 0,
							'wrapper' 			=> array(
								'width' => '',
								'class' => '',
								'id' 	=> '',
								),
							'return_format' 	=> 'url',
							'library' 			=> 'all',
							'min_size' 			=> '',
							'max_size' 			=> '',
							'mime_types' 		=> '',
							)
						)
					)
				);

			return $notificaciones;
			}

		private function campos_tab_caracteristicas () {

			$caracteristicas = array(

				//Pestaña "Características"
				array(
					'key' 				=> 'field_541e36a1b2ca9',
					'label' 			=> __('Características', 'estados-pedido'),
					'name' 				=> '',
					'type' 				=> 'tab',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'placement' 		=> 'top',
					'endpoint' 			=> 0,
					),

				//Campo de siguiente estado
				array(
					'key' 				=> 'field_5c62a17632be7',
					'label' 			=> __('Siguiente estado en el flujo de pedidos', 'estados-pedido'),
					'name' 				=> 'sigestado',
					'type' 				=> 'select',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'choices' 			=> $this->devuelve_estados(), //wc_get_order_statuses(),
					'default_value' 	=> [],
					'allow_null' 		=> 1,
					'multiple' 			=> 0,
					'ui' 				=> 1,
					'ajax' 				=> 0,
					'return_format' 	=> 'value',
					'placeholder' 		=> '',
					),

				//Checkboxes de características
				array(
					'key' 				=> 'field_59e232bcf1cf0',
					'name' 				=> 'informes',
					'type' 				=> 'checkbox',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'choices' 			=> array(
						'editable'	=> __('Hacer que los pedidos en este estado se puedan editar.', 'estados-pedido'),
						'incluir'	=> __('Incluir este estado de pedido en los informes de WooCommerce.', 'estados-pedido'),
						'dashboard'	=> __('Mostrar un resumen de este estado de pedido en el widget de escritorio de WooCommerce.', 'estados-pedido'),
						),
					'allow_custom' 		=> 0,
					'save_custom' 		=> 0,
					'default_value' 	=> [],
					'layout' 			=> 'horizontal',
					'toggle' 			=> 0,
					'return_format' 	=> 'value',
					)
				);

			return $caracteristicas;
			}

		private function campos_tab_automatizaciones () {

			$automatizaciones = array(

				//Pestaña "Automatizaciones"
				array(
					'key' 				=> 'field_501b39a6b2ca9',
					'label' 			=> __('Automatizaciones', 'estados-pedido'),
					'name' 				=> '',
					'type' 				=> 'tab',
					'instructions' 		=> '',
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'placement' 		=> 'top',
					'endpoint' 			=> 0,
					),

				//Campo de método de pago OR
				array(
					'key' 				=> 'field_5f32a17132fa3',
					'label' 			=> __('Método de pago', 'estados-pedido'),
					'name' 				=> 'autopago',
					'type' 				=> 'select',
					'instructions' 		=> __('Poner automáticamente en este estado los pedidos pagados mediante los métodos seleccionados.', 'estados-pedido'),
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'choices' 			=> $this->devuelve_pasarelas(),
					'default_value' 	=> [],
					'allow_null' 		=> 1,
					'multiple' 			=> 1,
					'ui' 				=> 1,
					'ajax' 				=> 0,
					'return_format' 	=> 'value',
					'placeholder' 		=> '',
					),

				//Campo de rol OR
				array(
					'key' 				=> 'field_5e30c17931fa3',
					'label' 			=> __('Rol de usuario', 'estados-pedido'),
					'name' 				=> 'autorol',
					'type' 				=> 'select',
					'instructions' 		=> __('Poner automáticamente en este estado los pedidos de usuarios pertenecientes a los roles seleccionados.', 'estados-pedido'),
					'required' 			=> 0,
					'conditional_logic' => 0,
					'wrapper' 			=> array(
						'width' => '',
						'class' => '',
						'id' 	=> '',
						),
					'choices' 			=> $this->devuelve_roles(),
					'default_value' 	=> [],
					'allow_null' 		=> 1,
					'multiple' 			=> 1,
					'ui' 				=> 1,
					'ajax' 				=> 0,
					'return_format' 	=> 'value',
					'placeholder' 		=> '',
					),
				);

			return $automatizaciones;
			}

		private function devuelve_estados () {

			$estados = [];

			foreach (Estados_Pedido_CPT::pide_query() as $estado)
				if (!isset ($_GET['post']) || $estado->ID != $_GET['post']) //Excluimos el estado que estamos editando actualmente
					$estados[$estado->post_name] = $estado->post_title ? : __('Personalizado #', 'estados-pedido') . $estado->ID;

			$estados['wc-processing'] = __('Processing', 'woocommerce');
			$estados['wc-completed']  = __('Completed', 'woocommerce');

			return $estados;
			}

		private function devuelve_pasarelas () {

			$pasarelas = [];

			foreach (WC()->payment_gateways->get_available_payment_gateways() as $key => $pasarela)
				if ('yes' == $pasarela->enabled)
					$pasarelas[$key] = $pasarela->title;

			return $pasarelas;
			}

		/**
		 * Devuelve la lista de roles de usuario
		 *
		 * @since 	3.3.0
		 * @return 	array
		 *
		 */
		private function devuelve_roles () {

			global $wp_roles;

			isset ($wp_roles) or
				$wp_roles = new WP_Roles();

			$lista = [];

			foreach ($wp_roles->get_names() as $rol => $nombre)
				$lista[esc_attr($rol)] = translate_user_role ($nombre);

			if ('yes' == get_option ('woocommerce_enable_guest_checkout')) //Si se permiten pedidos de invitados
				$lista['invitado'] = __('Invitado', 'estados-pedido');

			return $lista;
			}

		public static function devuelve_iconos () {

			return array(
				'159' => '<span class="dashicons dashicons-marker"></span>',
				'11d' => '<span class="dashicons dashicons-admin-site-alt"></span>',
				'12a' => '<span class="dashicons dashicons-yes-alt"></span>',
				'100' => '<span class="dashicons dashicons-admin-appearance"></span>',
				'102' => '<span class="dashicons dashicons-admin-home"></span>',
				'103' => '<span class="dashicons dashicons-admin-links"></span>',
				'106' => '<span class="dashicons dashicons-admin-plugins"></span>',
				'107' => '<span class="dashicons dashicons-admin-tools"></span>',
				'108' => '<span class="dashicons dashicons-admin-settings"></span>',
				'109' => '<span class="dashicons dashicons-admin-post"></span>',
				'110' => '<span class="dashicons dashicons-admin-users"></span>',
				'111' => '<span class="dashicons dashicons-admin-generic"></span>',
				'119' => '<span class="dashicons dashicons-welcome-write-blog"></span>',
				'129' => '<span class="dashicons dashicons-camera-alt"></span>',
				'155' => '<span class="dashicons dashicons-star-filled"></span>',
				'160' => '<span class="dashicons dashicons-lock"></span>',
				'174' => '<span class="dashicons dashicons-cart"></span>',
				'177' => '<span class="dashicons dashicons-visibility"></span>',
				'179' => '<span class="dashicons dashicons-search"></span>',
				'182' => '<span class="dashicons dashicons-trash"></span>',
				'223' => '<span class="dashicons dashicons-editor-help"></span>',
				'226' => '<span class="dashicons dashicons-dashboard"></span>',
				'227' => '<span class="dashicons dashicons-flag"></span>',
				'230' => '<span class="dashicons dashicons-location"></span>',
				'231' => '<span class="dashicons dashicons-location-alt"></span>',
				'242' => '<span class="dashicons dashicons-share-alt2"></span>',
				'308' => '<span class="dashicons dashicons-hammer"></span>',
				'312' => '<span class="dashicons dashicons-products"></span>',
				'316' => '<span class="dashicons dashicons-download"></span>',
				'317' => '<span class="dashicons dashicons-upload"></span>',
				'323' => '<span class="dashicons dashicons-tag"></span>',
				'331' => '<span class="dashicons dashicons-book-alt"></span>',
				'334' => '<span class="dashicons dashicons-shield-alt"></span>',
				'348' => '<span class="dashicons dashicons-info"></span>',
				'464' => '<span class="dashicons dashicons-edit"></span>',
				'466' => '<span class="dashicons dashicons-email-alt"></span>',
				'473' => '<span class="dashicons dashicons-testimonial"></span>',
				'480' => '<span class="dashicons dashicons-archive"></span>',
				'481' => '<span class="dashicons dashicons-clipboard"></span>',
				'508' => '<span class="dashicons dashicons-calendar-alt"></span>',
				'513' => '<span class="dashicons dashicons-store"></span>',
				'524' => '<span class="dashicons dashicons-tickets-alt"></span>',
				'525' => '<span class="dashicons dashicons-phone"></span>',
				'529' => '<span class="dashicons dashicons-thumbs-up"></span>',
				'530' => '<span class="dashicons dashicons-thumbs-down"></span>',
				'531' => '<span class="dashicons dashicons-image-rotate"></span>',
				'534' => '<span class="dashicons dashicons-warning"></span>',
				'546' => '<span class="dashicons dashicons-paperclip"></span>',
				);
			}

		}

endif;