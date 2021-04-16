<?php

/**
 * Funciones referentes a los estados de pedido personalizados
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 				Enrique J. Ros
 * @link 				https://www.enriquejros.com
 * @since 				1.0.0
 * @package 			EstadosPedido
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Estados_Pedido_Personalizados')) :

	Class Estados_Pedido_Personalizados {

		const ACCION = 'mark_ejr_';
		const ENVIO  = 'estado_personalizado_';

		public function __construct () {

			foreach ($this->estados = Estados_Pedido_CPT::pide_query() as $estado) {

				$titulo = strlen ($estado->post_title) ? $estado->post_title : sprintf (__('Estado personalizado #%s', 'estados-pedido'), $estado->ID);

				//Registramos los nuevos estados de pedido
				register_post_status ('wc-' . $estado->post_name,
					array(
						'label'						=> $titulo,
						'public'					=> true,
						'exclude_from_search'		=> false,
						'show_in_admin_all_list'	=> true,
						'show_in_admin_status_list'	=> true,
						'label_count'				=> _n_noop ($titulo . ' <span class="count">(%s)</span>', $titulo . ' <span class="count">(%s)</span>'),
						)
					);

				//Disparamos las notificaciones desde la metabox de acciones del pedido
				add_action ('woocommerce_order_action_notificaciones_' . $estado->post_name, function ($pedido) use ($estado) {

					$envio = WC()->mailer()->get_emails();

					if (null !== $envio[self::ENVIO . $estado->post_name]) //Mejor prevenir por si algún otro está filtrando mal
						$envio[self::ENVIO . $estado->post_name]->trigger($this->devuelve_post_id($pedido->get_id()), true); //El true es para permitir enviarlo manualmente aunque la notificación esté deshabilitada en los ajustes de correo electrónico de WooCommerce
					}, 10, 1);
				}

			//Añadimos los nuevos estados a la lista de estados de pedidos de Woo
			add_filter ('wc_order_statuses', [$this, 'add_lista_estados'], 10, 1);

			//Los hacemos editables en caso necesario
			add_filter ('wc_order_is_editable', [$this, 'estado_editable'], 10, 2);

			//Añadimos un botón de acción para cambiar al estado correspondiente
			add_filter ('woocommerce_admin_order_actions', [$this, 'boton_cambia_estado'], 10, 2);

			//Añadimos una opción en la metabox de acciones para enviar/reenviar las notificaciones
			add_filter ('woocommerce_order_actions', [$this, 'metabox_acciones'], 10, 2);

			//Vamos a ponerles unos iconos
			add_action ('wp_print_scripts', [$this, 'estilos_iconos'], 10);

			//Añadimos las acciones en lote
			add_filter ('bulk_actions-edit-shop_order', [$this, 'add_acciones_lote'], 10, 1);

			//Definimos los manejadores para las acciones en lote
			add_filter ('handle_bulk_actions-edit-shop_order', [$this, 'manejadores_acciones_lote'], 10, 3);

			//Añadimos un aviso para informar al usuario de los cambios en lote
			add_action ('admin_notices', [$this, 'aviso_acciones_lote'], 10, 1);

			//Incluimos los pedidos en estados personalizados en los informes de WooCommerce si es necesario
			add_filter ('woocommerce_reports_order_statuses', [$this, 'estado_informes'], 10, 1);

			//Añadimos hooks de acción para los distintos estados personalizados
			//Tenemos que activar manualmente la notificación de procesando si viene de un estado personalizado
			add_action ('woocommerce_order_status_changed', [$this, 'cambio_estado'], 10, 4);
			}

		public function add_lista_estados ($estados) {

			foreach ($this->estados as $estado)
				$estados['wc-' . $estado->post_name] = strlen ($estado->post_title) ? $estado->post_title : sprintf (__('Personalizado #%s', 'estados-pedido'), $estado->ID);

			return $estados;
			}

		/**
		 * Permite hacer que los pedidos en un estado personalizado se puedan editar
		 *
		 * @since 	3.1.3
		 *
		 */
		public function estado_editable ($editable, $pedido) {

			foreach ($this->estados as $estado)
				if (($caracteristicas = get_field ('informes', $estado->ID)) && is_array ($caracteristicas) && in_array ('editable', $caracteristicas) && $pedido->get_status() == $estado->post_name)
					$editable = true;

			return $editable;
			}

		public function boton_cambia_estado ($acciones, $pedido) {
			
			$id_pedido = $this->devuelve_post_id($pedido->get_order_number());

			if (!$pedido->has_status('processing') && !isset ($acciones['processing']))
				$acciones['processing'] = array(
					'url'		=> wp_nonce_url (admin_url ('admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $id_pedido), 'woocommerce-mark-order-status'),
					'name'		=> __('Processing', 'woocommerce'),
					'action'	=> 'processing',
					);

			if (!$pedido->has_status('completed') && !isset ($acciones['complete']))
				$acciones['complete'] = array(
					'url'		=> wp_nonce_url (admin_url ('admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $id_pedido), 'woocommerce-mark-order-status'),
					'name'		=> __('Complete', 'woocommerce'),
					'action'	=> 'complete',
					);

			foreach ($this->estados as $estado) {

				if (!$pedido->has_status($estado->post_name)) { //Sólo añadimos la acción a los pedidos que no están ya en ese estado

					$accion = version_compare (WC()->version, '3.3.0', "<" ) ? 'view ' . $estado->post_name : $estado->post_name;

					$acciones[$estado->post_name] = array(
						'url'		=> wp_nonce_url (admin_url ('admin-ajax.php?action=woocommerce_mark_order_status&status=' . $estado->post_name . '&order_id=' . $id_pedido), 'woocommerce-mark-order-status'),
						'name'		=> sprintf (__('Marcar como %s', 'estados-pedido'), mb_strtolower ($estado->post_title ? : sprintf (__('Estado personalizado #%s', 'estados-pedido'), $estado->ID)), 'UTF-8'),
						'action'	=> $accion,
						);
					}

				else //El estado en el que está el pedido
					$sigestado = get_field ('sigestado', $estado->ID);
				}

			if (isset ($sigestado) && strlen ($sigestado)) {

				'wc-' == substr ($sigestado, 0, 3) and
					$sigestado = substr ($sigestado, 3);

				foreach ($this->estados as $estado)
					if ($estado->post_name == $sigestado)
						$siguiente = $estado->post_name;

				switch ($sigestado) {

					case 'completed':
						$accion_sig = $acciones['complete'];
						$accion_key = 'complete';
						break;

					case 'processing':
						$accion_sig = $acciones['processing'];
						$accion_key = 'processing';
						break;

					default:
						$accion_sig = $acciones[$siguiente];
						$accion_key = $siguiente;
						break;
					}

				unset ($acciones[$accion_key]); //Quitamos el botón de acción correspondiente al siguiente estado
				array_unshift ($acciones, $accion_sig); //para ponerlo el primero
				}

			return $acciones;
			}

		public function metabox_acciones ($acciones) {

			$acciones_dos = $acciones_final = [];
			$pedido       = new WC_Order($_GET['post']);
			$status       = $pedido->get_status();

			foreach ($this->estados as $estado)
				if ($cuantas = count (get_field ('notificaciones', $estado->ID)))
					$acciones_dos['notificaciones_' . $estado->post_name] = ($status == $estado->post_name) ? sprintf (_n('Volver a enviar el aviso de %s', 'Volver a enviar los avisos de %s', $cuantas, 'estados-pedido'), $estado->post_title ? mb_strtolower ($estado->post_title, 'UTF-8') : __('estado personalizado #', 'estados-pedido') . $estado->ID) : sprintf (_n('Enviar el aviso de %s', 'Enviar los avisos de %s', $cuantas, 'estados-pedido'), $estado->post_title ? mb_strtolower ($estado->post_title, 'UTF-8') : __('estado personalizado #', 'estados-pedido') . $estado->ID);

			foreach ($acciones as $accion => $etiqueta) {

				$acciones_final[$accion] = $etiqueta;

				if ('send_order_details_admin' == $accion)
					foreach ($acciones_dos as $accion_dos => $etiqueta_dos)
						$acciones_final[$accion_dos] = $etiqueta_dos;
				}

			return $acciones_final;
			}

		public function estilos_iconos () {

			global $post_type;
			
			if ('shop_order' == $post_type && count ($this->estados)) {

				echo '<style type="text/css">';

				foreach ($this->estados as $estado) {

					$color = get_field ('color', $estado->ID);
					$campo = get_field ('icono', $estado->ID);
					$icono = isset ($campo) ? $campo['value'] : '159';

					if (version_compare (WC()->version, '3.3.0', "<" )) {

						?>
							.column-order_status mark.<?php echo $estado->post_name; ?>, .view.<?php echo $estado->post_name; ?>::after {
								content:"\f<?php echo $icono; ?>";
								background-color:<?php echo $color; ?>;
							}

							.view.<?php echo $estado->post_name; ?>::after, .wc-action-button-<?php echo $estado->post_name; ?>::after {
								width:18px;
								height:18px;
								margin:3px;
							}
						<?php

						}

					else {

						?>
							.wc-action-button-<?php echo $estado->post_name; ?>::after {
								content:"\f<?php echo $icono; ?>";
								color:<?php echo $color; ?>!important;
							}
								
							.status-<?php echo $estado->post_name; ?> {
								background-color:<?php echo $color; ?>;
								color:<?php echo get_field ('color_label', $estado->ID); ?>;
							}
						<?php

						}
					}

				echo '</style>';
				}
			}

		public function add_acciones_lote ($acciones) {

			foreach ($this->estados as $estado)
				$acciones[self::ACCION . $estado->post_name] = sprintf (__('Cambiar estado a %s', 'estados-pedido'), mb_strtolower ($estado->post_title ? : sprintf (__('Personalizado #%s', 'estados-pedido'), $estado->ID), 'UTF-8'));

			return $acciones;
			}

		/**
		 * Definimos las acciones a realizar en lote mediante el modo nativo de WordPress 4.7+
		 *
		 * @since 2.1.1
		 *
		 */
		public function manejadores_acciones_lote ($redirect, $accion, $ids) {

			foreach ($this->estados as $estado) {

				if (self::ACCION . $estado->post_name == $accion) {

					foreach ($ids as $id) {

						$pedido = new WC_Order($id);
						$pedido->update_status($estado->post_name, false, true);
						}

					$redirect = add_query_arg (self::ACCION . $estado->post_name, count ($ids), $redirect);
					}

				else
					$redirect = remove_query_arg (self::ACCION . $estado->post_name, $redirect);
				}

			return $redirect;
			}

		/**
		 * Añadimos un aviso para confirmar al usuario cuántos pedidos se han cambiado en lote
		 *
		 * @since 2.1.1
		 *
		 */
		public function aviso_acciones_lote () {

			foreach ($this->estados as $estado) {

				if (isset ($_GET[self::ACCION . $estado->post_name])) {

					$cantidad = $_GET[self::ACCION . $estado->post_name];

					?>
						<div class="updated">
							<p><?php printf (_n('Se ha marcado %s pedido como %s.', 'Se han marcado %s pedidos como %s.', $cantidad, 'estados-pedido'), $cantidad, mb_strtolower ($estado->post_title ? : sprintf (__('Personalizado #%s', 'estados-pedido'), $estado->ID), 'UTF-8')); ?></p>
						</div>
					<?php
					}
				}
			}

		public function estado_informes ($estados) {

			if ($estados) //Si no añadimos esto nos cargamos los refunds en los informes
				foreach ($this->estados as $estado)
					if (($incluir = get_field ('informes', $estado->ID)) && is_array ($incluir) && in_array ('incluir', $incluir))
						$estados[] = $estado->post_name;

			return $estados;
			}

		/**
		 * Creamos hooks de acción para el cambio a los diferentes estados personalizados
		 * Disparamos las notificaciones al pasar a un estado personalizado
		 * Lanzamos la notificación de procesando si viene de un estado de pedido personalizado
		 *
		 * @since 2.5.4
		 *
		 */
		public function cambio_estado ($id_pedido, $desde_estado, $a_estado, $pedido) {

			$id_convertida = $this->devuelve_post_id($id_pedido);

			foreach ($this->estados as $estado) {

				if ($a_estado == $estado->post_name) {

					do_action (self::ENVIO . $estado->post_name, $id_convertida);
					$envio = WC()->mailer()->get_emails();

					if (null !== $envio[self::ENVIO . $estado->post_name])
						$envio[self::ENVIO . $estado->post_name]->trigger($id_convertida);
					}

				if ($pedido->has_status('processing') && $desde_estado == $estado->post_name) {

					$envio = WC()->mailer()->get_emails();
					$envio['WC_Email_Customer_Processing_Order']->trigger($id_convertida);
					}
				}
			}

		/**
		 * Devuelve el número de post a partir del número de pedido
		 * Puede no coincidir si hay activo un plugin de números de pedido secuenciales
		 *
		 * @since 	2.3.0
		 *
		 * @param 	int|string 		ID
		 * @return 	int|string 		ID
		 *
		 */
		private function devuelve_post_id ($id_pedido) {

			if (class_exists ('WC_Seq_Order_Number')) //WooCommerce Sequential Order Numbers
				$meta = '_order_number';

			else if (function_exists ('YITH_Sequential_Order_Number_Premium_Init')) //YITH Sequential Order Number
				$meta = '_ywson_custom_number_order_complete';

			else
				return $id_pedido;

			global $wpdb;

			$order_number = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '$meta' AND meta_value = '$id_pedido'", ARRAY_A);

			return isset ($order_number[0]['post_id']) ? $order_number[0]['post_id'] : $id_pedido;
			}

		}

endif;