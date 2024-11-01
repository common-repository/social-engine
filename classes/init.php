<?php

if ( class_exists( 'MeowPro_SCLEGN_Core' ) && class_exists( 'Meow_SCLEGN_Core' ) ) {
	function sclegn_thanks_admin_notices() {

    echo wp_kses_post( '<div class="error"><p>' . __( 'Thanks for installing the Pro version of Social Engine :) However, the free version is still enabled. Please disable or uninstall it.', 'media-cleaner' ) . '</p></div>' );
	}
	add_action( 'admin_notices', 'sclegn_thanks_admin_notices' );
	return;
}

spl_autoload_register(function ( $class ) {
  $necessary = true;
  $file = null;
  if ( strpos( $class, 'Meow_SCLEGN_Services_' ) !== false ) {
    $file = SCLEGN_PATH . '/classes/services/' . str_replace( 'meow_sclegn_services_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'Meow_SCLEGN' ) !== false ) {
    $file = SCLEGN_PATH . '/classes/' . str_replace( 'meow_sclegn_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'MeowCommon_' ) !== false ) {
    $file = SCLEGN_PATH . '/common/' . str_replace( 'meowcommon_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'MeowCommonPro_' ) !== false ) {
    $necessary = false;
    $file = SCLEGN_PATH . '/common/premium/' . str_replace( 'meowcommonpro_', '', strtolower( $class ) ) . '.php';
  }
  else if ( strpos( $class, 'MeowPro_SCLEGN' ) !== false ) {
    $necessary = false;
    $file = SCLEGN_PATH . '/premium/' . str_replace( 'meowpro_sclegn_', '', strtolower( $class ) ) . '.php';
  }

  if ( $file ) {
    if ( !$necessary && !file_exists( $file ) ) {
      return;
    }
    require( $file );
  }
});

//require_once( SCLEGN_PATH . '/classes/api.php');
require_once( SCLEGN_PATH . '/common/helpers.php');

// In admin or Rest API request (REQUEST URI begins with '/wp-json/')
//if ( is_admin() || MeowCommon_Helpers::is_rest() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
global $sclegn_core;
$sclegn_core = new Meow_SCLEGN_Core();
//}

?>