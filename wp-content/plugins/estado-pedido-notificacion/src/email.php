<?php

/**
 * Registro de correos electrónicos
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link			https://www.enriquejros.com
 * @since			3.0.0
 * @package			EstadosPedido
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Emails_Estados_Pedido')) :

	Class Emails_Estados_Pedido {

		public function __construct () {

			add_filter ('woocommerce_email_classes', [$this, 'registra_notificaciones'], 90, 1);
			}

		public function registra_notificaciones ($emails) {

			$correos = [];

			require_once dirname (__FILE__) . '/notificacion.php';

			foreach ($emails as $key => $email) {

				$correos[$key] = $email;

				if ('customer_completed_order' == $email->id)
					foreach (Estados_Pedido_CPT::pide_query() as $estado)
						if (is_array ($notificaciones = get_field ('notificaciones', $estado->ID))) //Si se ha activado alguna de las notificaciones
							$correos['estado_personalizado_' . $estado->post_name] = new Notificacion_Estados_Pedido($estado, $notificaciones);
				}

			return $correos;
			}

		}

endif;