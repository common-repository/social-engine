<?php

require_once( SCLEGN_PATH . '/vendor/autoload.php' );
use Abraham\TwitterOAuth\TwitterOAuth;
class Meow_SCLEGN_Services_Twitter
{
  public function __construct() {
    add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 10 );
  }

  public function plugins_loaded() {

    add_filter( 'sclegn_modules', function( $modules ) {
      if ( !in_array( 'twitter', $modules ) ) {
        $modules[] = [ 
          'type' => 'twitter', 
          'instance' => $this
        ];
      }
      return $modules;
    }, 10, 1 );

    add_filter( 'sclegn_accounts', function( $accounts ) {
      $options = get_option( Meow_SCLEGN_Core::get_plugin_option_name(), null );
      $services = $options['services'] ?? [];
      foreach ( $services as &$service ) {
        if ( $service['type'] === 'twitter' ) {
          $accounts[] = [ 
            'type' => 'twitter',
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

  private function create_connection( $settings ) {

    // App Key === API Key === Consumer API Key === Consumer Key === Customer Key === oauth_consumer_key
    // App Key Secret === API Secret Key === Consumer Secret === Consumer Key === Customer Key === oauth_consumer_secret

    if ( empty( $settings['api_key'] ) ) {
      throw new Exception("Twitter/X: The API Key is missing.");
    }
    if ( empty( $settings['api_secret_key'] ) ) {
      throw new Exception("Twitter/X: The API Secret Key is missing.");
    }
    if ( empty( $settings['access_token'] ) ) {
      throw new Exception("Twitter/X: The access token is missing.");
    }
    if ( empty( $settings['access_token_secret'] ) ) {
      throw new Exception("Twitter/X: The access token secret is missing.");
    }
    $connection = new TwitterOAuth( $settings['api_key'], $settings['api_secret_key'], 
      $settings['access_token'], $settings['access_token_secret'] );
    return $connection;
  }

  private function handle_errors( $connection, $statuses ) {
    // API returns 201 (CREATED) when the tweet is posted
    $http_code = $connection->getLastHttpCode();
    $success = $http_code === 201 || $http_code === 200; // (201 = created)
    if ( !$success ) {
      $message = "Unknown error from Twitter";
      error_log( "Twitter API returned: " . $http_code );
      error_log( "Twitter API response: " . json_encode( $statuses ) );
      if ( is_object( $statuses ) && isset( $statuses->reason ) ) {
        $message = "Error from Twitter: " . $statuses->reason;
        if ( isset( $statuses->detail ) ) {
          $message .= " (" . $statuses->detail . ")";
        }
      }
      else if ( $http_code === 403 ) {
        $message = "Twitter/X: Not Authorized.";
      }
      error_log( $message );
      throw new Exception( $message );
    }
  }

  public function post( $social_post, $settings )
  {
    $media_ids = [];
    if ( isset( $social_post['thumbnail_paths'] ) && count( $social_post['thumbnail_paths'] ) > 0 ) {
      foreach ( $social_post['thumbnail_paths'] as $thumbnail_path ) {
        $media = $this->upload( $thumbnail_path, $settings );
        $media_ids[] = $media->media_id_string;
      }
    }

    $parameters = [
      'text' => $social_post['post_content'],
    ];

    if( $media_ids !== [] ) {
      $parameters['media']['media_ids']= $media_ids;
    }

    $connection = $this->create_connection($settings);
    $connection->setApiVersion( '2' );
    $twitter_res = $connection->post( 'tweets', $parameters, true );
    $this->handle_errors( $connection, $twitter_res );
    return [
      'posted_id' => $twitter_res->id_str,
      'post_date' => get_date_from_gmt( $twitter_res->created_at, 'Y-m-d H:i:s' ),
    ];
  }

  public function upload( $media, $settings ) {
    $connection = $this->create_connection( $settings );
    $twitter_res = $connection->upload( 'media/upload', [ 'media' => $media ] );
    $this->handle_errors( $connection, $twitter_res );
    return $twitter_res;
  }

  public function request_service( $settings, $action, $params ) {
    if ( $action === "test" ) {
      $connection = $this->create_connection( $settings );
      $content = $connection->get("account/verify_credentials");
      if ( $content && !empty( $content->errors ) ) {
        throw new Exception( $content->errors[0]->message, $content->errors[0]->code );
      }
      if ( $content && !empty( $content->name ) ) {
        return $content->name;
      }
    }
    return false;
  }

  public function stats( $posted_id, $settings, $account_id ) {
    $connection = $this->create_connection( $settings );
    $content = $connection->get('statuses/show/' . $posted_id );
    if ( $connection->getLastHttpCode() !== 200 ) {
      throw new Exception( $content->errors[0]->message, $content->errors[0]->code );
    }
    return [
      'likes' => $content->favorite_count,
    ];
  }
}