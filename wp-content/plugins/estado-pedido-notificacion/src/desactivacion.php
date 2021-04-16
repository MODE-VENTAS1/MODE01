<?php

/**
 * Aviso en la desactivación del plugin
 * copyright Enrique J. Ros - enrique@enriquejros.com
 *
 * @author 			Enrique J. Ros
 * @link 			https://www.enriquejros.com
 * @since 			3.0.0
 * @package 		EstadosPedido
 *
 */

defined ('ABSPATH') or exit;

if (!class_exists ('Estados_Pedido_Desactivacion')) :

	Class Estados_Pedido_Desactivacion {

		public function __construct () {

			add_action ('admin_footer', [$this, 'textos_script_desactivacion'], 10);
			add_action ('admin_enqueue_scripts', [$this, 'script_aviso_desactivacion'], 10);
			}

		/**
		 * Establece una cabecera con los textos para el aviso de desactivación, para que sea traducible
		 * copyright Enrique J. Ros - enrique@enriquejros.com
		 *
		 * @since 2.2.0
		 *
		 */
		public function textos_script_desactivacion () {

			if (!$this->comprueba_pantalla())
				return;

			$textos = array(
				'pregunta'	=> __('¿Estás seguro?', 'estados-pedido'),
				'frase'		=> __('Desactivar el plugin hará que los pedidos que estén en un estado creado con él no puedan verse. Comprueba primero que no queda ningún pedido en un estado personalizado y si es así cámbialo de estado.', 'estados-pedido'),
				'si'		=> __('Yes'),
				'no'		=> __('No'),
				'exito'		=> __('Desactivando plugin...', 'estados-pedido'),
				'completo'	=> __('Plugin desactivado, espera la recarga de la página...', 'estados-pedido'),
				);

			printf ('<meta name="boton-desactivar-plugin" id="meta-boton-desactivar-plugin" data-pregunta="%s" data-frase="%s" data-si="%s" data-no="%s" data-exito="%s" data-completo="%s">', $textos['pregunta'], $textos['frase'], $textos['si'], $textos['no'], $textos['exito'], $textos['completo']);
			}

		public function script_aviso_desactivacion () {

			if (!$this->comprueba_pantalla())
				return;

			wp_enqueue_style ('sweetalert', ASSETS_ESTADOS_PEDIDO . 'css/sweetalert.min.css');
			wp_enqueue_script ('sweetalert', ASSETS_ESTADOS_PEDIDO . 'js/sweetalert.min.js');
			wp_enqueue_script ('estados-pedido-desactivacion', ASSETS_ESTADOS_PEDIDO . 'js/desactivacion.min.js', ['sweetalert']);
			}

		/**
		 * Devuelve true si estamos en la pantalla de plugins y false en caso contrario
		 * copyright Enrique J. Ros - enrique@enriquejros.com
		 *
		 * @since 	2.2.0
		 * @return 	bool
		 *
		 */
		private function comprueba_pantalla () {

			$pantalla = get_current_screen();

			return ('plugins' == $pantalla->base) ? true : false;
			}

		}

endif;