<?php

class Meow_SCLEGN_UI {
	private $core = null;

	function __construct( $core ) {
		$this->core = $core;
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], 100 );
	}



	function admin_menu() {
		$access = $this->core->can_access_features();
		if ( $access ) {
			add_media_page( 'Social Engine Dashboard', __( 'Social Engine', 'social-engine' ), 'read', 
				'sclegn_dashboard', array( $this, 'cleaner_dashboard' ), 1 );
		}
	}

	function admin_bar_menu() {
		global $wp_admin_bar;
		$access = $this->core->can_access_features();
		if ( $access ) {
			$wp_admin_bar->add_menu( array(
				'id' => 'sclegn_dashboard',
				'title' => __( '<span style="margin-top: 2px;" class="ab-icon dashicons dashicons-share"></span> Social Engine', 'social-engine' ),
				'href' => admin_url( 'upload.php?page=sclegn_dashboard' )
			) );
		}
	}

	public function cleaner_dashboard() {
		echo wp_kses_post( '<div id="sclegn-dashboard"></div>' );
	}
}
