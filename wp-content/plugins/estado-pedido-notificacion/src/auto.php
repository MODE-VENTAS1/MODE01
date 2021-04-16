<?php

/**
 * Automatizaciones
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 				Enrique J. Ros
 * @link 				https://www.enriquejros.com
 * @since 				3.0.0
 * @package 			EstadosPedido
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Automatizaciones_Estados_Pedido')) :

	Class Automatizaciones_Estados_Pedido {

		const TIEMPO = 300;

		public function __construct () {

			add_action ('woocommerce_thankyou', [$this, 'cambia_estado_pedido'], 10, 1);
			}

		public function cambia_estado_pedido ($id_pedido) {

			if (!$id_pedido)
				return;

			$pedido   = wc_get_order ($id_pedido);
			$previo   = $pedido->get_status();
			$pasarela = $pedido->get_payment_method();
			$creado   = strtotime ($pedido->get_date_created());

			if ($creado + apply_filters ('estados_pedido_tiempo_auto', self::TIEMPO) > time()) { //Por seguridad, sólo los tres primeros minutos

				if (!$id_usuario = get_current_user_id())
					$rol = ['invitado'];

				else {

					$usuario = get_userdata ($id_usuario);
					$rol     = $usuario->roles;
					}

				foreach (Estados_Pedido_CPT::pide_query(true) as $estado) { //Llamamos a la query de forma que nos devuelva los estados de pedido ordenados según el orden en la pantalla de admin, así se les puede asignar la prioridad de las condiciones reordenándolos manualmente

					if ($previo != $estado->post_name && 'auto' !== get_post_meta ($id_pedido, 'estados_pedido_automatizacion', true)) {

						if (is_array ($pasarelas = get_field ('autopago', $estado->ID)) && in_array ($pasarela, $pasarelas)) {

							if ($pedido->update_status($estado->post_name, __('Automatización del estado de pedido personalizado:', 'estados-pedido'), false))
								update_post_meta ($id_pedido, 'estados_pedido_automatizacion', 'auto');
							}

						else if (is_array ($roles = get_field ('autorol', $estado->ID)) && array_intersect ($rol, $roles)) {

							if ($pedido->update_status($estado->post_name, __('Automatización del estado de pedido personalizado:', 'estados-pedido'), false))
								update_post_meta ($id_pedido, 'estados_pedido_automatizacion', 'auto');
							}
						}
					}
				}
			}

		}

endif;