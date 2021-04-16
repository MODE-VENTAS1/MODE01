<?php

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists('ACF') ) :

class ACF {
	
	var $version = '5.9.5';
	
	var $settings = [];
	
	var $data = [];
	
	var $instances = [];
	
	function __construct() {
	}
	
	function initialize() {
		
		$this->define( 'ACF', true );
		$this->define( 'ACF_PATH', plugin_dir_path( __FILE__ ) );
		$this->define( 'ACF_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'ACF_VERSION', $this->version );
		$this->define( 'ACF_MAJOR_VERSION', 5 );
		
		$this->settings = array(
			'name'						=> __('Advanced Custom Fields', 'acf'),
			'slug'						=> dirname( ACF_BASENAME ),
			'version'					=> ACF_VERSION,
			'basename'					=> ACF_BASENAME,
			'path'						=> ACF_PATH,
			'file'						=> __FILE__,
			'url'						=> plugin_dir_url( __FILE__ ),
			'show_admin'				=> true,
			'show_updates'				=> true,
			'stripslashes'				=> false,
			'local'						=> true,
			'json'						=> true,
			'save_json'					=> '',
			'load_json'					=> [],
			'default_language'			=> '',
			'current_language'			=> '',
			'capability'				=> 'manage_options',
			'uploader'					=> 'wp',
			'autoload'					=> false,
			'l10n'						=> true,
			'l10n_textdomain'			=> '',
			'google_api_key'			=> '',
			'google_api_client'			=> '',
			'enqueue_google_maps'		=> true,
			'enqueue_select2'			=> true,
			'enqueue_datepicker'		=> true,
			'enqueue_datetimepicker'	=> true,
			'select2_version'			=> 4,
			'row_index_offset'			=> 1,
			'remove_wp_meta_box'		=> true
		);
		
		include_once( ACF_PATH . 'includes/acf-utility-functions.php');
		
		acf_include('includes/api/api-helpers.php');
		acf_include('includes/api/api-template.php');
		acf_include('includes/api/api-term.php');
		
		acf_include('includes/class-acf-data.php');
		acf_include('includes/fields/class-acf-field.php');
		acf_include('includes/locations/abstract-acf-legacy-location.php');
		acf_include('includes/locations/abstract-acf-location.php');
		
		acf_include('includes/acf-helper-functions.php');
		acf_include('includes/acf-hook-functions.php');
		acf_include('includes/acf-field-functions.php');
		acf_include('includes/acf-field-group-functions.php');
		acf_include('includes/acf-form-functions.php');
		acf_include('includes/acf-meta-functions.php');
		acf_include('includes/acf-post-functions.php');
		acf_include('includes/acf-user-functions.php');
		acf_include('includes/acf-value-functions.php');
		acf_include('includes/acf-input-functions.php');
		acf_include('includes/acf-wp-functions.php');
		
		acf_include('includes/fields.php');
		acf_include('includes/locations.php');
		acf_include('includes/assets.php');
		acf_include('includes/compatibility.php');
		acf_include('includes/deprecated.php');
		acf_include('includes/l10n.php');
		acf_include('includes/local-fields.php');
		acf_include('includes/local-meta.php');
		acf_include('includes/local-json.php');
		acf_include('includes/loop.php');
		acf_include('includes/media.php');
		acf_include('includes/revisions.php');
		acf_include('includes/updates.php');
		acf_include('includes/upgrades.php');
		acf_include('includes/validation.php');
		
		acf_include('includes/ajax/class-acf-ajax.php');
		acf_include('includes/ajax/class-acf-ajax-check-screen.php');
		acf_include('includes/ajax/class-acf-ajax-user-setting.php');
		acf_include('includes/ajax/class-acf-ajax-upgrade.php');
		acf_include('includes/ajax/class-acf-ajax-query.php');
		acf_include('includes/ajax/class-acf-ajax-query-users.php');
		acf_include('includes/ajax/class-acf-ajax-local-json-diff.php');
		
		acf_include('includes/forms/form-attachment.php');
		acf_include('includes/forms/form-comment.php');
		acf_include('includes/forms/form-customizer.php');
		acf_include('includes/forms/form-front.php');
		acf_include('includes/forms/form-nav-menu.php');
		acf_include('includes/forms/form-post.php');
		acf_include('includes/forms/form-gutenberg.php');
		acf_include('includes/forms/form-taxonomy.php');
		acf_include('includes/forms/form-user.php');
		acf_include('includes/forms/form-widget.php');
		
		if( is_admin() ) {
			acf_include('includes/admin/admin.php');
			acf_include('includes/admin/admin-field-group.php');
			acf_include('includes/admin/admin-field-groups.php');
			acf_include('includes/admin/admin-notices.php');
			acf_include('includes/admin/admin-tools.php');
			acf_include('includes/admin/admin-upgrade.php');
		}
		
		acf_include('includes/legacy/legacy-locations.php');
		
		acf_include('pro/acf-pro.php');
		
		if( defined('ACF_DEV') && ACF_DEV ) {
			acf_include('tests/tests.php');
		}
		
		add_action( 'init', array($this, 'init'), 5 );
		add_action( 'init', array($this, 'register_post_types'), 5 );
		add_action( 'init', array($this, 'register_post_status'), 5 );
		
		add_filter( 'posts_where', array($this, 'posts_where'), 10, 2 );
	}
	
	function init() {
		
		if( !did_action('plugins_loaded') ) {
			return;
		}
		
		if( acf_did('init') ) {
			return;
		}
		
		acf_update_setting( 'url', plugin_dir_url( __FILE__ ) );
		
		acf_load_textdomain();
		
		acf_include('includes/third-party.php');
		
		if( defined('ICL_SITEPRESS_VERSION') ) {
			acf_include('includes/wpml.php');
		}
		
		acf_include('includes/fields/class-acf-field-text.php');
		acf_include('includes/fields/class-acf-field-textarea.php');
		acf_include('includes/fields/class-acf-field-number.php');
		acf_include('includes/fields/class-acf-field-range.php');
		acf_include('includes/fields/class-acf-field-email.php');
		acf_include('includes/fields/class-acf-field-url.php');
		acf_include('includes/fields/class-acf-field-password.php');
		acf_include('includes/fields/class-acf-field-image.php');
		acf_include('includes/fields/class-acf-field-file.php');
		acf_include('includes/fields/class-acf-field-wysiwyg.php');
		acf_include('includes/fields/class-acf-field-oembed.php');
		acf_include('includes/fields/class-acf-field-select.php');
		acf_include('includes/fields/class-acf-field-checkbox.php');
		acf_include('includes/fields/class-acf-field-radio.php');
		acf_include('includes/fields/class-acf-field-button-group.php');
		acf_include('includes/fields/class-acf-field-true_false.php');
		acf_include('includes/fields/class-acf-field-link.php');
		acf_include('includes/fields/class-acf-field-post_object.php');
		acf_include('includes/fields/class-acf-field-page_link.php');
		acf_include('includes/fields/class-acf-field-relationship.php');
		acf_include('includes/fields/class-acf-field-taxonomy.php');
		acf_include('includes/fields/class-acf-field-user.php');
		acf_include('includes/fields/class-acf-field-google-map.php');
		acf_include('includes/fields/class-acf-field-date_picker.php');
		acf_include('includes/fields/class-acf-field-date_time_picker.php');
		acf_include('includes/fields/class-acf-field-time_picker.php');
		acf_include('includes/fields/class-acf-field-color_picker.php');
		acf_include('includes/fields/class-acf-field-message.php');
		acf_include('includes/fields/class-acf-field-accordion.php');
		acf_include('includes/fields/class-acf-field-tab.php');
		acf_include('includes/fields/class-acf-field-group.php');
		
		do_action( 'acf/include_field_types', ACF_MAJOR_VERSION );
		
		acf_include('includes/locations/class-acf-location-post-type.php');
		acf_include('includes/locations/class-acf-location-post-template.php');
		acf_include('includes/locations/class-acf-location-post-status.php');
		acf_include('includes/locations/class-acf-location-post-format.php');
		acf_include('includes/locations/class-acf-location-post-category.php');
		acf_include('includes/locations/class-acf-location-post-taxonomy.php');
		acf_include('includes/locations/class-acf-location-post.php');
		acf_include('includes/locations/class-acf-location-page-template.php');
		acf_include('includes/locations/class-acf-location-page-type.php');
		acf_include('includes/locations/class-acf-location-page-parent.php');
		acf_include('includes/locations/class-acf-location-page.php');
		acf_include('includes/locations/class-acf-location-current-user.php');
		acf_include('includes/locations/class-acf-location-current-user-role.php');
		acf_include('includes/locations/class-acf-location-user-form.php');
		acf_include('includes/locations/class-acf-location-user-role.php');
		acf_include('includes/locations/class-acf-location-taxonomy.php');
		acf_include('includes/locations/class-acf-location-attachment.php');
		acf_include('includes/locations/class-acf-location-comment.php');
		acf_include('includes/locations/class-acf-location-widget.php');
		acf_include('includes/locations/class-acf-location-nav-menu.php');
		acf_include('includes/locations/class-acf-location-nav-menu-item.php');
		
		do_action( 'acf/include_location_rules', ACF_MAJOR_VERSION );
		
		do_action( 'acf/include_fields', ACF_MAJOR_VERSION );
		
		do_action( 'acf/init', ACF_MAJOR_VERSION );
	}
	
	function register_post_types() {
		
		$cap = acf_get_setting('capability');
		
		register_post_type('acf-field-group', array(
			'labels'			=> array(
			    'name'					=> __( 'Field Groups', 'acf' ),
				'singular_name'			=> __( 'Field Group', 'acf' ),
			    'add_new'				=> __( 'Add New' , 'acf' ),
			    'add_new_item'			=> __( 'Add New Field Group' , 'acf' ),
			    'edit_item'				=> __( 'Edit Field Group' , 'acf' ),
			    'new_item'				=> __( 'New Field Group' , 'acf' ),
			    'view_item'				=> __( 'View Field Group', 'acf' ),
			    'search_items'			=> __( 'Search Field Groups', 'acf' ),
			    'not_found'				=> __( 'No Field Groups found', 'acf' ),
			    'not_found_in_trash'	=> __( 'No Field Groups found in Trash', 'acf' ), 
			),
			'public'			=> false,
			'hierarchical'		=> true,
			'show_ui'			=> true,
			'show_in_menu'		=> false,
			'_builtin'			=> false,
			'capability_type'	=> 'post',
			'capabilities'		=> array(
				'edit_post'			=> $cap,
				'delete_post'		=> $cap,
				'edit_posts'		=> $cap,
				'delete_posts'		=> $cap,
			),
			'supports' 			=> array('title'),
			'rewrite'			=> false,
			'query_var'			=> false,
		));
		
		
		register_post_type('acf-field', array(
			'labels'			=> array(
			    'name'					=> __( 'Fields', 'acf' ),
				'singular_name'			=> __( 'Field', 'acf' ),
			    'add_new'				=> __( 'Add New' , 'acf' ),
			    'add_new_item'			=> __( 'Add New Field' , 'acf' ),
			    'edit_item'				=> __( 'Edit Field' , 'acf' ),
			    'new_item'				=> __( 'New Field' , 'acf' ),
			    'view_item'				=> __( 'View Field', 'acf' ),
			    'search_items'			=> __( 'Search Fields', 'acf' ),
			    'not_found'				=> __( 'No Fields found', 'acf' ),
			    'not_found_in_trash'	=> __( 'No Fields found in Trash', 'acf' ), 
			),
			'public'			=> false,
			'hierarchical'		=> true,
			'show_ui'			=> false,
			'show_in_menu'		=> false,
			'_builtin'			=> false,
			'capability_type'	=> 'post',
			'capabilities'		=> array(
				'edit_post'			=> $cap,
				'delete_post'		=> $cap,
				'edit_posts'		=> $cap,
				'delete_posts'		=> $cap,
			),
			'supports' 			=> array('title'),
			'rewrite'			=> false,
			'query_var'			=> false,
		));
	}
	
	function register_post_status() {
		
		register_post_status('acf-disabled', array(
			'label'                     => _x( 'Disabled', 'post status', 'acf' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Disabled <span class="count">(%s)</span>', 'Disabled <span class="count">(%s)</span>', 'acf' ),
		));
	}
	
	function posts_where( $where, $wp_query ) {
		global $wpdb;
		
		if( $field_key = $wp_query->get('acf_field_key') ) {
			$where .= $wpdb->prepare(" AND {$wpdb->posts}.post_name = %s", $field_key );
	    }
	    
	    if( $field_name = $wp_query->get('acf_field_name') ) {
			$where .= $wpdb->prepare(" AND {$wpdb->posts}.post_excerpt = %s", $field_name );
	    }
	    
		if( $group_key = $wp_query->get('acf_group_key') ) {
			$where .= $wpdb->prepare(" AND {$wpdb->posts}.post_name = %s", $group_key );
	    }
	    
	    return $where;
	}
	
	function define( $name, $value = true ) {
		if( !defined($name) ) {
			define( $name, $value );
		}
	}
	
	function has_setting( $name ) {
		return isset($this->settings[ $name ]);
	}
	
	function get_setting( $name ) {
		return isset($this->settings[ $name ]) ? $this->settings[ $name ] : null;
	}
	
	function update_setting( $name, $value ) {
		$this->settings[ $name ] = $value;
		return true;
	}
	
	function get_data( $name ) {
		return isset($this->data[ $name ]) ? $this->data[ $name ] : null;
	}
	
	function set_data( $name, $value ) {
		$this->data[ $name ] = $value;
	}
	
	function get_instance( $class ) {
		$name = strtolower($class);
		return isset($this->instances[ $name ]) ? $this->instances[ $name ] : null;
	}
	
	function new_instance( $class ) {
		$instance = new $class();
		$name = strtolower($class);
		$this->instances[ $name ] = $instance;
		return $instance;
	}
	
	public function __isset( $key ) {
		return in_array( $key, array( 'locations', 'json' ) );
	}
	
	public function __get( $key ) {
		switch ( $key ) {
			case 'locations':
				return acf_get_instance( 'ACF_Legacy_Locations' );
			case 'json':
				return acf_get_instance( 'ACF_Local_JSON' );
		}
		return null;
	}
}

function acf() {
	global $acf;
	
	if( !isset($acf) ) {
		$acf = new ACF();
		$acf->initialize();
	}
	return $acf;
}

acf();

endif;