<?php
/*
Plugin Name: Social Engine: Schedule Social Media Posts
Plugin URI: https://meowapps.com
Description: Schedule and automate posts across social networks. Unlimited features and extensibility. Works with X, Facebook, Instagram, Pinterest, LinkedIn.
Version: 0.7.0
Author: Jordy Meow
Author URI: https://jordymeow.com
Text Domain: social-engine

Dual licensed under the MIT and GPL licenses:
http://www.opensource.org/licenses/mit-license.php
http://www.gnu.org/licenses/gpl.html
*/

if ( !defined( 'SCLEGN_VERSION' ) ) {
  define( 'SCLEGN_VERSION', '0.7.0' );
  define( 'SCLEGN_PREFIX', 'sclegn' );
  define( 'SCLEGN_DOMAIN', 'social-engine' );
  define( 'SCLEGN_ENTRY', __FILE__ );
  define( 'SCLEGN_PATH', dirname( __FILE__ ) );
  define( 'SCLEGN_URL', plugin_dir_url( __FILE__ ) );
}

require_once( 'classes/init.php' );

?>
