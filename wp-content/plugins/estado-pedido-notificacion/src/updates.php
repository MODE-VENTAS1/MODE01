<?php

/**
 * Actualizaciones
 *
 * @since 			3.0.0
 * @package 		EstadosPedido
 *
 */

defined ('ABSPATH') or exit;

class Updates_Estados_Pedido_Personalizados {

	private $api_url     = '';
	private $api_data    = [];
	private $name        = '';
	private $slug        = '';
	private $version     = '';
	private $wp_override = false;
	private $cache_key   = '';

	private $health_check_timeout = 5;

	public function __construct ($_api_url, $_plugin_file, $_api_data = null) {

		global $edd_plugin_data;

		$this->api_url     = trailingslashit ($_api_url);
		$this->api_data    = $_api_data;
		$this->name        = plugin_basename ($_plugin_file);
		$this->slug        = basename ($_plugin_file, '.php');
		$this->version     = $_api_data['version'];
		$this->wp_override = isset ($_api_data['wp_override']) ? (bool) $_api_data['wp_override'] : false;
		$this->beta        = !empty ($this->api_data['beta']) ? true : false;
		$this->cache_key   = 'edd_sl_' . md5 (serialize ($this->slug . $this->api_data['license'] . $this->beta));

		$edd_plugin_data[$this->slug] = $this->api_data;

		do_action ('post_edd_sl_plugin_updater_setup', $edd_plugin_data);

		$this->init();

	}

	public function init() {

		add_filter ('pre_set_site_transient_update_plugins', [$this, 'check_update']);
		add_filter ('plugins_api', [$this, 'plugins_api_filter'], 10, 3);
		remove_action ('after_plugin_row_' . $this->name, 'wp_plugin_update_row', 10);
		add_action ('after_plugin_row_' . $this->name, [$this, 'show_update_notification'], 10, 2);
		add_action ('admin_init', [$this, 'show_changelog']);

	}

	public function check_update( $_transient_data ) {

		global $pagenow;

		if ( ! is_object( $_transient_data ) ) {
			$_transient_data = new stdClass;
		}

		if ( 'plugins.php' == $pagenow && is_multisite() ) {
			return $_transient_data;
		}

		if ( ! empty( $_transient_data->response ) && ! empty( $_transient_data->response[ $this->name ] ) && false === $this->wp_override ) {
			return $_transient_data;
		}

		$version_info = $this->get_cached_version_info();

		if ( false === $version_info ) {
			$version_info = $this->api_request( 'plugin_latest_version', array( 'slug' => $this->slug, 'beta' => $this->beta ) );

			$this->set_version_info_cache( $version_info );

		}

		if ( false !== $version_info && is_object( $version_info ) && isset( $version_info->new_version ) ) {

			$no_update = false;
			if ( version_compare( $this->version, $version_info->new_version, '<' ) ) {

				$_transient_data->response[ $this->name ] = $version_info;

				$_transient_data->response[ $this->name ]->plugin = $this->name;

			} else {
				$no_update              = new stdClass();
				$no_update->id          = '';
				$no_update->slug        = $this->slug;
				$no_update->plugin      = $this->name;
				$no_update->new_version = $version_info->new_version;
				$no_update->url         = $version_info->homepage;
				$no_update->package     = $version_info->package;
				$no_update->icons       = $version_info->icons;
				$no_update->banners     = $version_info->banners;
				$no_update->banners_rtl = array();
			}

			$_transient_data->last_checked           = time();
			$_transient_data->checked[ $this->name ] = $this->version;

			if ( $no_update ) {
				$_transient_data->no_update[ $this->name ] = $no_update;
			}
		}

		return $_transient_data;
	}

	public function show_update_notification( $file, $plugin ) {

		if ( is_network_admin() ) {
			return;
		}

		if( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		if( ! is_multisite() ) {
			return;
		}

		if ( $this->name != $file ) {
			return;
		}

		remove_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ), 10 );

		$update_cache = get_site_transient( 'update_plugins' );

		$update_cache = is_object( $update_cache ) ? $update_cache : new stdClass();

		if ( empty( $update_cache->response ) || empty( $update_cache->response[ $this->name ] ) ) {

			$version_info = $this->get_cached_version_info();

			if ( false === $version_info ) {
				$version_info = $this->api_request( 'plugin_latest_version', array( 'slug' => $this->slug, 'beta' => $this->beta ) );

				if ( isset( $version_info->banners ) && ! is_array( $version_info->banners ) ) {
					$version_info->banners = $this->convert_object_to_array( $version_info->banners );
				}

				if ( isset( $version_info->sections ) && ! is_array( $version_info->sections ) ) {
					$version_info->sections = $this->convert_object_to_array( $version_info->sections );
				}

				if ( isset( $version_info->icons ) && ! is_array( $version_info->icons ) ) {
					$version_info->icons = $this->convert_object_to_array( $version_info->icons );
				}

				if ( isset( $version_info->contributors ) && ! is_array( $version_info->contributors ) ) {
					$version_info->contributors = $this->convert_object_to_array( $version_info->contributors );
				}

				$this->set_version_info_cache( $version_info );
			}

			if ( ! is_object( $version_info ) ) {
				return;
			}

			$no_update = false;
			if ( version_compare( $this->version, $version_info->new_version, '<' ) ) {

				$update_cache->response[ $this->name ] = $version_info;

			} else {
				$no_update              = new stdClass();
				$no_update->id          = '';
				$no_update->slug        = $this->slug;
				$no_update->plugin      = $this->name;
				$no_update->new_version = $version_info->new_version;
				$no_update->url         = $version_info->homepage;
				$no_update->package     = $version_info->package;
				$no_update->icons       = $version_info->icons;
				$no_update->banners     = $version_info->banners;
				$no_update->banners_rtl = array();
			}

			$update_cache->last_checked           = time();
			$update_cache->checked[ $this->name ] = $this->version;
			if ( $no_update ) {
				$update_cache->no_update[ $this->name ] = $no_update;
			}

			set_site_transient( 'update_plugins', $update_cache );

		} else {

			$version_info = $update_cache->response[ $this->name ];

		}

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );

		if ( ! empty( $update_cache->response[ $this->name ] ) && version_compare( $this->version, $version_info->new_version, '<' ) ) {

			$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
			# <tr class="plugin-update-tr"><td colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange">
			echo '<tr class="plugin-update-tr" id="' . $this->slug . '-update" data-slug="' . $this->slug . '" data-plugin="' . $this->slug . '/' . $file . '">';
			echo '<td colspan="3" class="plugin-update colspanchange">';
			echo '<div class="update-message notice inline notice-warning notice-alt">';

			$changelog_link = 'https://www.enriquejros.com/changelog-estados-pedido/';

			if ( empty( $version_info->download_link ) ) {
				printf(
					__( 'Hay disponible una nueva versión de %1$s. %2$sVer los detalles de la versión %3$s%4$s.', 'estados-pedido' ),
					esc_html( $version_info->name ),
					'<a target="_blank" class="thickbox" href="' . esc_url( $changelog_link ) . '">',
					esc_html( $version_info->new_version ),
					'</a>'
				);
			} else {
				printf(
					__( 'Hay disponible una nueva versión de %1$s. %2$sVer los detalles de la versión %3$s%4$s o %5$sactualizar ahora%6$s.', 'easy-digital-downloads' ),
					esc_html( $version_info->name ),
					'<a target="_blank" class="thickbox" href="' . esc_url( $changelog_link ) . '">',
					esc_html( $version_info->new_version ),
					'</a>',
					'<a href="' . esc_url( wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $this->name, 'upgrade-plugin_' . $this->name ) ) .'">',
					'</a>'
				);
			}

			do_action( "in_plugin_update_message-{$file}", $plugin, $version_info );

			echo '</div></td></tr>';
		}
	}

	public function plugins_api_filter( $_data, $_action = '', $_args = null ) {

		if ( $_action != 'plugin_information' ) {

			return $_data;

		}

		if ( ! isset( $_args->slug ) || ( $_args->slug != $this->slug ) ) {

			return $_data;

		}

		$to_send = array(
			'slug'   => $this->slug,
			'is_ssl' => is_ssl(),
			'fields' => array(
				'banners' => array(),
				'reviews' => false,
				'icons'   => array(),
			)
		);

		$edd_api_request_transient = $this->get_cached_version_info();

		if ( empty( $edd_api_request_transient ) ) {

			$api_response = $this->api_request( 'plugin_information', $to_send );

			$this->set_version_info_cache( $api_response );

			if ( false !== $api_response ) {
				$_data = $api_response;
			}

		} else {
			$_data = $edd_api_request_transient;
		}

		if ( isset( $_data->sections ) && ! is_array( $_data->sections ) ) {
			$_data->sections = $this->convert_object_to_array( $_data->sections );
		}

		if ( isset( $_data->banners ) && ! is_array( $_data->banners ) ) {
			$_data->banners = $this->convert_object_to_array( $_data->banners );
		}

		if ( isset( $_data->icons ) && ! is_array( $_data->icons ) ) {
			$_data->icons = $this->convert_object_to_array( $_data->icons );
		}

		if ( isset( $_data->contributors ) && ! is_array( $_data->contributors ) ) {
			$_data->contributors = $this->convert_object_to_array( $_data->contributors );
		}

		if( ! isset( $_data->plugin ) ) {
			$_data->plugin = $this->name;
		}

		return $_data;
	}

	private function convert_object_to_array( $data ) {
		$new_data = array();
		foreach ( $data as $key => $value ) {
			$new_data[ $key ] = is_object( $value ) ? $this->convert_object_to_array( $value ) : $value;
		}

		return $new_data;
	}

	public function http_request_args( $args, $url ) {

		$verify_ssl = $this->verify_ssl();
		if ( strpos( $url, 'https://' ) !== false && strpos( $url, 'edd_action=package_download' ) ) {
			$args['sslverify'] = $verify_ssl;
		}
		return $args;

	}

	private function api_request( $_action, $_data ) {

		global $wp_version, $edd_plugin_url_available;

		$verify_ssl = $this->verify_ssl();

		$store_hash = md5( $this->api_url );
		if ( ! is_array( $edd_plugin_url_available ) || ! isset( $edd_plugin_url_available[ $store_hash ] ) ) {
			$test_url_parts = parse_url( $this->api_url );

			$scheme = ! empty( $test_url_parts['scheme'] ) ? $test_url_parts['scheme']     : 'http';
			$host   = ! empty( $test_url_parts['host'] )   ? $test_url_parts['host']       : '';
			$port   = ! empty( $test_url_parts['port'] )   ? ':' . $test_url_parts['port'] : '';

			if ( empty( $host ) ) {
				$edd_plugin_url_available[ $store_hash ] = false;
			} else {
				$test_url = $scheme . '://' . $host . $port;
				$response = wp_remote_get( $test_url, array( 'timeout' => $this->health_check_timeout, 'sslverify' => $verify_ssl ) );
				$edd_plugin_url_available[ $store_hash ] = is_wp_error( $response ) ? false : true;
			}
		}

		if ( false === $edd_plugin_url_available[ $store_hash ] ) {
			return;
		}

		$data = array_merge( $this->api_data, $_data );

		if ( $data['slug'] != $this->slug ) {
			return;
		}

		if( $this->api_url == trailingslashit ( home_url() ) ) {
			return false;
		}

		$api_params = array(
			'edd_action' => 'get_version',
			'license'    => ! empty( $data['license'] ) ? $data['license'] : '',
			'item_name'  => isset( $data['item_name'] ) ? $data['item_name'] : false,
			'item_id'    => isset( $data['item_id'] ) ? $data['item_id'] : false,
			'version'    => isset( $data['version'] ) ? $data['version'] : false,
			'slug'       => $data['slug'],
			'author'     => $data['author'],
			'url'        => home_url(),
			'beta'       => ! empty( $data['beta'] ),
		);

		$request    = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => $verify_ssl, 'body' => $api_params ) );

		if ( ! is_wp_error( $request ) ) {
			$request = json_decode( wp_remote_retrieve_body( $request ) );
		}

		if ( $request && isset( $request->sections ) ) {
			$request->sections = maybe_unserialize( $request->sections );
		} else {
			$request = false;
		}

		if ( $request && isset( $request->banners ) ) {
			$request->banners = maybe_unserialize( $request->banners );
		}

		if ( $request && isset( $request->icons ) ) {
			$request->icons = maybe_unserialize( $request->icons );
		}

		if( ! empty( $request->sections ) ) {
			foreach( $request->sections as $key => $section ) {
				$request->$key = (array) $section;
			}
		}

		return $request;
	}

	public function show_changelog() {

		global $edd_plugin_data;

		if( empty( $_REQUEST['edd_sl_action'] ) || 'view_plugin_changelog' != $_REQUEST['edd_sl_action'] ) {
			return;
		}

		if( empty( $_REQUEST['plugin'] ) ) {
			return;
		}

		if( empty( $_REQUEST['slug'] ) ) {
			return;
		}

		if( ! current_user_can( 'update_plugins' ) ) {
			wp_die( __( 'You do not have permission to install plugin updates', 'easy-digital-downloads' ), __( 'Error', 'easy-digital-downloads' ), array( 'response' => 403 ) );
		}

		$data         = $edd_plugin_data[ $_REQUEST['slug'] ];
		$version_info = $this->get_cached_version_info();

		if( false === $version_info ) {

			$api_params = array(
				'edd_action' => 'get_version',
				'item_name'  => isset( $data['item_name'] ) ? $data['item_name'] : false,
				'item_id'    => isset( $data['item_id'] ) ? $data['item_id'] : false,
				'slug'       => $_REQUEST['slug'],
				'author'     => $data['author'],
				'url'        => home_url(),
				'beta'       => ! empty( $data['beta'] )
			);

			$verify_ssl = $this->verify_ssl();
			$request    = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => $verify_ssl, 'body' => $api_params ) );

			if ( ! is_wp_error( $request ) ) {
				$version_info = json_decode( wp_remote_retrieve_body( $request ) );
			}

			if ( ! empty( $version_info ) && isset( $version_info->sections ) ) {
				$version_info->sections = maybe_unserialize( $version_info->sections );
			} else {
				$version_info = false;
			}

			if( ! empty( $version_info ) ) {
				foreach( $version_info->sections as $key => $section ) {
					$version_info->$key = (array) $section;
				}
			}

			$this->set_version_info_cache( $version_info );

			delete_option( md5( 'edd_plugin_' . sanitize_key( $_REQUEST['plugin'] ) . '_' . $this->beta . '_version_info' ) );
		}

		if ( isset( $version_info->sections ) ) {
			$sections = $this->convert_object_to_array( $version_info->sections );
			if ( ! empty( $sections['changelog'] ) ) {
				echo '<div style="background:#fff;padding:10px;">' . wp_kses_post( $sections['changelog'] ) . '</div>';
			}
		}

		exit;
	}

	public function get_cached_version_info( $cache_key = '' ) {

		if( empty( $cache_key ) ) {
			$cache_key = $this->cache_key;
		}

		$cache = get_option( $cache_key );

		if( empty( $cache['timeout'] ) || time() > $cache['timeout'] ) {
			return false; // Cache is expired
		}

		$cache['value'] = json_decode( $cache['value'] );
		if ( ! empty( $cache['value']->icons ) ) {
			$cache['value']->icons = (array) $cache['value']->icons;
		}

		return $cache['value'];

	}

	public function set_version_info_cache( $value = '', $cache_key = '' ) {

		if( empty( $cache_key ) ) {
			$cache_key = $this->cache_key;
		}

		$data = array(
			'timeout' => strtotime( '+3 hours', time() ),
			'value'   => json_encode( $value )
		);

		update_option( $cache_key, $data, 'no' );

		delete_option( 'edd_api_request_' . md5( serialize( $this->slug . $this->api_data['license'] . $this->beta ) ) );
	}

	private function verify_ssl() {
		return (bool) apply_filters( 'edd_sl_api_request_verify_ssl', true, $this );
	}

}