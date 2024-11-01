<?php
class Meow_SCLEGN_Admin extends MeowCommon_Admin {

	public $core;

	public function __construct( $core ) {
		parent::__construct( SCLEGN_PREFIX, SCLEGN_ENTRY, SCLEGN_DOMAIN, class_exists( 'MeowPro_SCLEGN_Core' ) );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'app_menu' ) );

			// Load the scripts only if they are needed by the current screen
			$page = isset( $_GET["page"] ) ? $_GET["page"] : null;
			$is_sclegn_screen = in_array( $page, [ 'sclegn_settings', 'sclegn_dashboard' ] );
			$is_meowapps_dashboard = $page === 'meowapps-main-menu';

			global $pagenow;
			$is_page_all_posts = $pagenow === 'edit.php' && (!isset($_GET['post_type']) || $_GET['post_type'] === 'post');
			$is_page_all_custom_posts = $pagenow === 'edit.php' && isset($_GET['post_type']);

			if ( $is_meowapps_dashboard || $is_sclegn_screen || $is_page_all_posts || $is_page_all_custom_posts ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			}
		}
		$this->core = $core;
	}

	function admin_enqueue_scripts() {
		// Load the scripts
		$physical_file = SCLEGN_PATH . '/app/index.js';
		$cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : SCLEGN_VERSION;
		wp_register_script( 'sclegn_social_engine-vendor', SCLEGN_URL . 'app/vendor.js',
			['wp-element', 'wp-i18n'], $cache_buster
		);
		wp_register_script( 'sclegn_social_engine', SCLEGN_URL . 'app/index.js',
			['sclegn_social_engine-vendor', 'wp-i18n'], $cache_buster
		);
		wp_set_script_translations( 'sclegn_social_engine', 'social-engine' );
		wp_enqueue_script('sclegn_social_engine' );

		// Load the fonts
		wp_register_style( 'meow-neko-ui-lato-font', '//fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap');
		wp_enqueue_style( 'meow-neko-ui-lato-font' );

		// Localize and options
		wp_localize_script( 'sclegn_social_engine', 'sclegn_social_engine', [
			'api_url' => rest_url( 'social-engine/v1' ),
			'rest_url' => rest_url(),
			'plugin_url' => SCLEGN_URL,
			'prefix' => SCLEGN_PREFIX,
			'domain' => SCLEGN_DOMAIN,
			'is_pro' => class_exists( 'MeowPro_SCLEGN_Core' ),
			'is_registered' => !!$this->is_registered(),
			'rest_nonce' => wp_create_nonce( 'wp_rest' ),
			'post_types' => $this->core->make_post_type_list( $this->core->get_post_types() ),
			'options' => $this->core->get_all_options(),
		]);
	}

	function is_registered() {
		return apply_filters( SCLEGN_PREFIX . '_meowapps_is_registered', false, SCLEGN_PREFIX );
	}

	function app_menu() {
		add_submenu_page( 'meowapps-main-menu', 'Social Engine', 'Social Engine', 'manage_options',
			'sclegn_settings', array( $this, 'admin_settings' ) );
	}

	function admin_settings() {
		echo wp_kses_post( '<div id="sclegn-admin-settings"></div>' );
	}
}

?>