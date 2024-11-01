<?php
require_once( SCLEGN_PATH . '/classes/services/mastodon/client.php' );

class Meow_SCLEGN_Services_Mastodon
{
  public function __construct() {
    add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 10 );
  }

  public function plugins_loaded() {

    add_filter( 'sclegn_modules', function( $modules ) {
      if ( !in_array( 'mastodon', $modules ) ) {
        $modules[] = [
          'type' => 'mastodon',
          'instance' => $this
        ];
      }
      return $modules;
    }, 10, 1 );

    add_filter( 'sclegn_accounts', function( $accounts ) {
      $options = get_option( Meow_SCLEGN_Core::get_plugin_option_name(), null );
      $services = $options['services'] ?? [];
      foreach ( $services as &$service ) {
        if ( $service['type'] === 'mastodon' ) {
          $accounts[] = [
            'type' => 'mastodon',
            'name' => $service['name'],
            'serviceId' => $service['id'],
            'accountId' => $service['id'],
            'instance' => $this
          ];
        }
      }
      return $accounts;
    }, 10, 1 );
  }

  /**
   * Create connection for Mastodon
   * @param array $settings
   * @throws Exception
   * @return MastodonClient
   */
  private function create_connection( array $settings ) {

    if ( empty( $settings['website'] ) ) {
      throw new Exception("Mastodon: The website is missing.");
    }
    if ( empty( $settings['access_token'] ) ) {
      throw new Exception("Mastodon: The access token is missing.");
    }
    $connection = new MastodonClient( $settings['website'], $settings['access_token'] );
    return $connection;
  }

  public function post( $social_post, $settings ) {
    $media_ids = [];
    if ( isset( $social_post['thumbnail_paths'] ) && count( $social_post['thumbnail_paths'] ) > 0 ) {
      foreach ( $social_post['thumbnail_paths'] as $thumbnail_path ) {
        $media_ids[] = $this->upload( $thumbnail_path, $settings );
      }
    }
    $parameters = [
        'status' => $social_post['post_content'],
        'media_ids' => $media_ids,
    ];
    $connection = $this->create_connection( $settings );
    $status = $connection->post( 'api/v1/statuses', $parameters );
    return [
      'posted_id' => $status['id'],
      'post_date' => get_date_from_gmt( $status['created_at'], 'Y-m-d H:i:s' ),
    ];
  }

  public function upload( $media, $settings ) {
    $connection = $this->create_connection($settings);
    $result = $connection->upload( 'api/v2/media', $media );
    return $result['id'];
  }

  public function request_service( $settings, $action, $params ) {
    if ( $action === "test" ) {
      return $this->verify_credentials( $settings );
    }
    return false;
  }

  public function stats( $posted_id, $settings, $account_id ) {
    // $connection = $this->create_connection( $settings );
    // $content = $connection->get('statuses/show/' . $posted_id );
    // if ( $connection->getLastHttpCode() !== 200 ) {
    //   throw new Exception( $content->errors[0]->message, $content->errors[0]->code );
    // }
    // return [
    //   'likes' => $content->favorite_count,
    // ];
  }

  public function verify_credentials( $settings ): bool {
    $connection = $this->create_connection( $settings );
    $connection->get( 'api/v1/apps/verify_credentials' );
    return true;
  }
}