<?php

/**
 * Envío de las notificaciones
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * https://woocommerce.github.io/code-reference/classes/WC-Email.html
 *
 * @author 			Enrique J. Ros
 * @link			https://www.enriquejros.com
 * @since			3.0.0
 * @package			EstadosPedido
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Notificacion_Estados_Pedido')) :

	Class Notificacion_Estados_Pedido extends WC_Email {

		public function __construct ($estado, $notificaciones) {

			$this->estado         = $estado;
			$this->notificaciones = $notificaciones;

			$this->id             = 'estado_personalizado_' . $this->estado->post_name;
			$this->enabled        = $this->enabled && is_array ($this->notificaciones);
			$this->plain          = false; //En texto plano
			$this->customer_email = in_array ('cliente', $this->notificaciones);
			$this->title          = sprintf ('%s %s', __('Order', 'woocommerce'), $this->estado->post_title ? mb_strtolower ($this->estado->post_title, 'UTF-8') : __('en estado Personalizado #') . $this->estado->ID);
			$this->description    = sprintf (__('Notificación del estado de pedido %s', 'estados-pedido'), $this->estado->post_title ? : __('personalizado #') . $this->estado->ID);
			$this->subject        = get_field ('asunto', $this->estado->ID);
			$this->heading        = get_field ('heading', $this->estado->ID) ? : $this->subject;

			if (in_array ('admin', $this->notificaciones)) {

				if ($emails_admin = get_field ('email_admin', $this->estado->ID))
					$this->recipient = str_replace (';', ',', $emails_admin);

				else
					$this->recipient = get_option ('admin_email');
				}

			parent::__construct();
			}

		public function trigger ($id_pedido, $manual = false) {

			if (!$this->is_enabled() && !$manual) //Si se he forzado manualmente lo enviamos aunque esté deshabilitado
				return;

			$this->id_pedido = $id_pedido;
			$this->object    = wc_get_order ($this->id_pedido);

			$this->customer_email and
				$this->send($this->object->get_billing_email(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->adjunta_archivos());

			if ($this->recipient)
				foreach (explode (',', $this->recipient) as $email_admin)
					$this->send($email_admin, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->adjunta_archivos());
			}

		public function get_subject () {

			return $this->establece_variables($this->subject);
			}

		public function get_content_html () {

			ob_start();
			do_action ('woocommerce_email_header', $this->heading, $this);
			echo $this->plantilla = get_field ('plantilla', $this->estado->ID);
			do_action ('woocommerce_email_footer', $this);

			return $this->establece_variables(ob_get_clean());
			}

		public function get_content_plain () {

			$this->plain = true;

			ob_start();

			$linea = '';

			for ($i = 0; $i < floor (strlen ($this->establece_variables($this->heading)) / 2); $i++)
				$linea .= '=-';

			echo $linea . "=\n";
			echo $this->heading . "\n";
			echo $linea . "=\n";

			echo $this->plantilla = get_field ('plantilla', $this->estado->ID);

			// Compatibilidad con el plugin de seguimiento de envío
			if (class_exists ('Agencias_Seguimiento_Pedidos') && is_array ($seguimiento = get_field ('seguimiento', $this->estado->ID)) && in_array ('incluir', $seguimiento) && !strpos ($this->plantilla, '%%seguimiento%%'))
				echo $this->seguimiento();

			return html_entity_decode (strip_tags (str_replace (['<br>', '<p>', '€', '&euro;'], ["\n", "\n\n", 'EUR', 'EUR'], $this->establece_variables(ob_get_clean()))));
			}

		private function adjunta_archivos () {

			$array_adjuntos  = $this->get_attachments();

			if ($adjuntos = get_field ('adjuntos', $this->estado->ID))
				foreach ($adjuntos as $adjunto)
					$array_adjuntos[] = $adjunto['adjunto'];

			return $array_adjuntos;
			}

		private function establece_variables ($texto) {

			$variables = array(
				'%%cliente%%'			=> $this->object->get_billing_first_name(),
				'%%pedido%%'  			=> $this->devuelve_num_pedido($this->id_pedido),
				'%%email%%'   			=> $this->object->get_billing_email(),
				'%%tabla%%'	  			=> $this->crea_tabla_pedido(),
				'%%total%%'   			=> html_entity_decode (strip_tags (wc_price ($this->object->get_total()))),
				'%%datos_facturacion%%'	=> $this->crea_bloque_datos('_billing'),
				'%%datos_envio%%'		=> $this->crea_bloque_datos('_shipping'),
				'%%metodo_envio%%'		=> $this->object->get_shipping_method(),
				'%%metodo_pago%%'		=> get_post_meta ($this->id_pedido, '_payment_method_title', true),
				);

			if (class_exists ('Agencias_Seguimiento_Pedidos') && $this->crea_info_seguimiento()) {

				$array_seguimiento = array(
					'%%agencia%%'	  => $this->agencia,
					'%%codigo%%'	  => $this->codigo,
					'%%fecha_envio%%' => $this->f_envio,
					'%%url_envio%%'   => $this->url_envio,
					'%%boton_envio%%' => $this->b_envio,
					);

				if (strpos ($texto, '%%seguimiento%%')) { //Retrocompatibilidad con 3.2.0

					$plantilla_seguimiento = get_field ('plantillaseguimiento', $this->estado->ID);

					foreach ($array_seguimiento as $var_seguimiento => $dato_seguimiento)
						$plantilla_seguimiento = str_replace ($var_seguimiento, $dato_seguimiento, $plantilla_seguimiento);

					$texto = str_replace ('%%seguimiento%%', $plantilla_seguimiento, $texto);
					}

				else
					$variables = array_merge ($variables, $array_seguimiento);
				}

			/**
			 * Para añadir nuevas variables:
			 *
			 * add_filter ('estados_pedido_variables_email', function ($variables, $pedido, $texto_plano) {
			 *
			 * 		$id_pedido = $pedido->get_id();
			 * 		$variables['%%nueva_variable%%']  = $texto_plano ? $valor_variable_texto_plano : $valor_variable_html;
			 * 		$variables['%%nueva_variable2%%'] = $valor_variable2;
			 *
			 * 		return $variables;
			 * 		}, 10, 2);
			 *
			 */
			foreach (apply_filters ('estados_pedido_variables_email', $variables, $this->object, $this->plain) as $variable => $valor)
				$texto = str_replace ($variable, $valor, $texto);

			return $texto;
			}

		/**
		 * Devuelve la tabla resumen del pedido
		 *
		 * @since 3.0.0
		 *
		 */
		private function crea_tabla_pedido () {

			$fecha = date_i18n (get_option ('date_format'), strtotime ($this->object->get_date_created()));
			$args  = apply_filters ('woocommerce_email_order_items_args',
				array(
					'order'					=> $this->object,
					'items'					=> $this->object->get_items(),
					'show_download_links'	=> $this->object->is_download_permitted() && $this->customer_email,
					'show_sku'				=> false,
					'show_image'			=> false,
					'image_size'			=> [32, 32],
					'plain_text'			=> $this->plain,
					'sent_to_admin'			=> in_array ('admin', $this->notificaciones),
					)
				);

			ob_start();
			do_action ('woocommerce_email_before_order_table', $this->object, in_array ('admin', $this->notificaciones), $this->plain, $this);

			if ($this->plain) :

				printf ('[%s #%s] (%s)', strtoupper (__('Order', 'woocommerce')), $this->devuelve_num_pedido($this->id_pedido), strtoupper ($fecha));

				echo "\n\n";
				echo wc_get_email_order_items ($this->object, $args);
				echo "==========\n";

				printf ('%s %s%s', __('Subtotal:', 'woocommerce'), $this->object->get_subtotal_to_display(), "\n");

				if ('0' !== get_post_meta ($this->id_pedido, '_cart_discount', true))
					printf ('%s %s%s', __('Discount:', 'woocommerce'), $this->object->get_discount_to_display(), "\n");

				printf ('%s %s%s', __('Shipping:', 'woocommerce'), $this->object->get_shipping_to_display(), "\n");

				if ('incl' != get_option ('woocommerce_tax_display_cart'))
					foreach ($this->object->get_taxes() as $impuesto)
						printf ('%s %s%s', $impuesto['label'], wc_price ($impuesto['tax_amount'] + $impuesto['shipping_tax_amount']), "\n");

				printf ('%s %s%s', __('Payment method:', 'woocommerce'), $this->object->get_payment_method_title(), "\n");
				printf ('%s %s%s', __('Total:', 'woocommerce'), $this->object->get_formatted_order_total(), "\n");

				echo "\n----------------------------------------\n";

			else :

				?>

					<h2 style='color: #96588a; display: block; font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif; font-size: 18px; font-weight: bold; line-height: 130%; margin: 0 0 18px; text-align: left;'><?php printf ('[%s #%s] (%s)', __('Order', 'woocommerce'), $this->devuelve_num_pedido($this->id_pedido), $fecha); ?></h2>

					<div style="margin-bottom: 40px;">

						<table class="td" cellspacing="0" cellpadding="6" border="1" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
							<thead>
								<tr>
									<th class="td" scope="col" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;"><?php _e('Product', 'woocommerce'); ?></th>
									<th class="td" scope="col" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;"><?php _e('Quantity', 'woocommerce'); ?></th>
									<th class="td" scope="col" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;"><?php _e('Price', 'woocommerce'); ?></th>
								</tr>
							</thead>

							<tbody>

								<?php echo wc_get_email_order_items ($this->object, $args); ?>

							</tbody>

							<tfoot>
								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;"><?php _e('Subtotal:', 'woocommerce'); ?></th>
									<td class="td" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left; border-top-width: 4px;"><?php echo $this->object->get_subtotal_to_display(); ?></td>
								</tr>

								<?php if ('0' !== get_post_meta ($this->id_pedido, '_cart_discount', true)) : ?>

									<tr>
										<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left"><?php _e('Discount:', 'woocommerce'); ?></th>
										<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left">-<?php echo $this->object->get_discount_to_display(); ?></td>
									</tr>

								<?php endif; ?>

								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;"><?php _e('Shipping:', 'woocommerce'); ?></th>
									<td class="td" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;"><?php echo $this->object->get_shipping_to_display(); ?></td>
								</tr>

								<?php if ('incl' != get_option ('woocommerce_tax_display_cart')) : ?>

									<?php foreach ($this->object->get_taxes() as $impuesto) : ?>

										<tr>
											<th class="td" scope="row" colspan="2" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left"><?php echo $impuesto['label']; ?>:</th>
											<td class="td" style="color: #636363;border: 1px solid #e5e5e5;vertical-align: middle;padding: 12px;text-align: left"><?php echo wc_price ($impuesto['tax_amount'] + $impuesto['shipping_tax_amount']); ?></td>
										</tr>

									<?php endforeach; ?>

								<?php endif; ?>
								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;"><?php _e('Payment method:', 'woocommerce'); ?></th>
									<td class="td" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;"><?php echo $this->object->get_payment_method_title(); ?></td>
								</tr>
								<tr>
									<th class="td" scope="row" colspan="2" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;"><?php _e('Total:', 'woocommerce'); ?></th>
									<td class="td" style="color: #636363; border: 1px solid #e5e5e5; vertical-align: middle; padding: 12px; text-align: left;"><?php echo $this->object->get_formatted_order_total(); ?></td>
								</tr>
							</tfoot>
						</table>

					</div>

				<?php

			endif;

			do_action ('woocommerce_email_after_order_table', $this->object, in_array ('admin', $this->notificaciones), $this->plain, $this);

			return ob_get_clean();
			}

		/**
		 * Devuelve el bloque de datos de facturación/envio
		 *
		 * @since 	3.1.4
		 *
		 * @param 	string ('_billing' || _shipping')
		 * @return 	string
		 *
		 */
		private function crea_bloque_datos ($datos) {

			$meta    = get_post_meta ($this->id_pedido);
			$estados = WC()->countries->get_states();
			$bloque  = sprintf ('%s %s<br>', $meta[$datos . '_first_name'][0], $meta[$datos . '_last_name'][0]);

			if ('_billing' == $datos) {

				isset ($meta['_billing_company']) and
					$bloque .= sprintf ('%s<br>', $meta['_billing_company'][0]);

				isset ($meta['_billing_nif']) and
					$nif = $meta['_billing_nif'][0];

				isset ($meta['NIF']) and
					$nif = $meta['NIF'][0];

				isset ($nif) and
					$bloque .= sprintf ('%s<br>', $nif);
				}

			$bloque .= sprintf ('%s<br>', $meta[$datos . '_address_1'][0]);

			isset ($meta[$datos . '_address_2']) and
				$bloque .= sprintf ('%s<br>', $meta[$datos . '_address_2'][0]);

			$bloque .= sprintf ('%s %s<br>', $meta[$datos . '_postcode'][0], $meta[$datos . '_city'][0]);

			/**
			 * Si queremos que se muestre el país en los datos de facturación/envío:
			 *
			 * add_filter ('estados_pedido_datos_pais', '__return_true');
			 *
			 */
			$bloque .= apply_filters ('estados_pedido_datos_pais', false, $this->estado->ID) ? sprintf ('%s (%s)<br>', $estados[$meta[$datos . '_country'][0]][$meta[$datos . '_state'][0]], WC()->countries->countries[$meta[$datos . '_country'][0]]) : sprintf ('%s<br>', $estados[$meta[$datos . '_country'][0]][$meta[$datos . '_state'][0]]);

			if ('_billing' == $datos) {

				isset ($meta['_billing_phone']) and
					$bloque .= $this->plain ? sprintf ("%s\n", $meta['_billing_phone'][0]) : sprintf ('<a href="tel:%s">%s</a><br>', $meta['_billing_phone'][0], $meta['_billing_phone'][0]);

				$bloque .= sprintf ('%s<br>', $meta['_billing_email'][0]);
				}

			return $this->plain ? str_replace ('<br>', "\n", $bloque) : $bloque;
			}

		/**
		 * Devuelve la información de seguimiento del envío
		 *
		 * @since 	3.1.2 
		 *
		 */
		private function crea_info_seguimiento () {

			if (!$opciones = get_post_meta ($this->id_pedido, 'seguimiento', true))
				return false;

			if (!isset ($opciones['transportista']) && !strlen ($opciones['codigo']))
				return false;

			if (isset ($this->agencia) || isset ($this->codigo)) //Evitemos correr el código más de una vez
				return true;

			$obj_envio = new Agencias_Seguimiento_Pedidos;
			$agencia   = $obj_envio->get_agencias($opciones['transportista']);

			$this->agencia   = isset ($agencia['nombre']) ? $agencia['nombre'] : false;
			$this->codigo    = $opciones['codigo'];
			$this->f_envio   = date_i18n (get_option ('date_format'), strtotime ($opciones['fecha']));
			$this->url_envio = $obj_envio->url_seguimiento($agencia, $opciones['codigo'], $this->id_pedido);
			$this->b_envio   = $this->plain ? $this->url_envio : sprintf ('<a target="_blank" href="%s" class="button" style="text-decoration:none">%s</a>', $this->url_envio, __('Ver el estado del envío', 'estados-pedido'));

			return true;
			}

		/**
		 * Integración con el plugin de seguimiento de envíos
		 * Método obsoleto desde la versión 3.1.2, se mantiene por razones de retrocompatibilidad
		 *
		 */
		private function seguimiento () {

			if (!$opciones = get_post_meta ($this->id_pedido, 'seguimiento', true))
				return '';

			if (!isset ($opciones['transportista']) || !strlen ($opciones['codigo'])) //No juntar los dos if
				return '';

			$obj_envio = new Agencias_Seguimiento_Pedidos;
			$agencia   = $obj_envio->get_agencias($opciones['transportista']);
			$nombre    = isset ($agencia['nombre']) ? $agencia['nombre'] : false;
			$url       = $obj_envio->url_seguimiento($agencia, $opciones['codigo'], $this->id_pedido);

			if ($this->plain) {

				$titulo      = __('INFORMACIÓN DE SEGUIMIENTO DEL ENVÍO', 'estados-pedido');
				$seguimiento = "\n" . $titulo . "\n";

				for ($i = 0; $i < strlen ($titulo); $i++)
					$seguimiento .= '=';

				$seguimiento .= "\n\n" . sprintf (__('Su pedido ha sido enviado a través de %s, con el código de seguimiento %s. Puede conocer el estado del envío a través del siguiente enlace: %s', 'estados-pedido'), $nombre, $opciones['codigo'], $url) . "\n";
				}

			else {

				$seguimiento   = sprintf ('<h2>%s</h2>', __('Información de seguimiento del envío', 'estados-pedido'));
				$seguimiento  .= '<p>' . sprintf (__('Su pedido ha sido enviado a través de %s, con el código de seguimiento %s. Puede conocer el estado del envío a través del siguiente enlace:', 'estados-pedido'), '<b>' . $nombre . '</b>', '<b>' . $opciones['codigo'] . '</b>') . '</p>';
				$seguimiento  .= sprintf ('<p><a target="_blank" href="%s" class="button" style="text-decoration:none">%s</a></p>', $url, __('Ver el estado del envío', 'estados-pedido'));
				}

			return $seguimiento;
			}

		/**
		 * Devuelve el número de pedido a partir del número de post
		 * Puede no coincidir si hay activo un plugin de números de pedido secuenciales
		 *
		 * @param 	int|string 		ID
		 * @return 	int|string 		ID
		 *
		 * @since 	2.5.0
		 *
		 */
		private function devuelve_num_pedido ($id_post) {

			if (class_exists ('WC_Seq_Order_Number')) //WooCommerce Sequential Order Numbers
				$meta = '_order_number';

			else if (function_exists ('YITH_Sequential_Order_Number_Premium_Init')) //YITH Sequential Order Number
				$meta = '_ywson_custom_number_order_complete';

			else
				return $id_post;

			return get_post_meta ($id_post, $meta, true);
			}

		}

endif;