<?php

require_once( SCLEGN_PATH . '/vendor/autoload.php' );

class Meow_SCLEGN_Services_Instagram
{
  public function __construct() {
    add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 10 );
  }

  public function plugins_loaded() {
    add_filter( 'sclegn_modules', function( $modules ) {
      if ( !in_array( 'instagram', $modules ) ) {
        $modules[] = [ 
          'type' => 'instagram', 
          'instance' => $this
        ];
      }
      return $modules;
    }, 10, 1 );
  }

  private function create_connection( $settings ) {
    // App Key === API Key === Consumer API Key === Consumer Key === Customer Key === oauth_consumer_key
    // App Key Secret === API Secret Key === Consumer Secret === Consumer Key === Customer Key === oauth_consumer_secret

    if ( empty( $settings['app_id'] ) ) {
      throw new Exception("Instagram: The App ID is missing.");
    }
    if ( empty( $settings['app_secret'] ) ) {
      throw new Exception("Instagram: The App Secret is missing.");
    }

    $token = null;
    if ( isset( $settings['long_lived_token'] ) ) {
      $token = $settings['long_lived_token'];
    }
    else if ( isset( $settings['short_lived_token'] ) ) {
      $token = $settings['short_lived_token'];
    }
    if (!$token) {
      throw new Exception("Instagram: There is no token set up.");
    }

    $connection = new Facebook\Facebook([
      'app_id' => $settings['app_id'],
      'app_secret' => $settings['app_secret'],
      'default_access_token' => $token, // I hope this doesn't create any issue
      'default_graph_version' => 'v2.10'
    ]);

    return $connection;
  }

  // @see: https://developers.facebook.com/docs/instagram-api/guides/content-publishing
  public function post( $social_post, $settings ) {
    $connection = $this->create_connection( $settings );

    if ( !isset( $social_post['thumbnail_paths'] ) || count( $social_post['thumbnail_paths'] ) === 0 ) {
      throw new Exception( 'Instagram: Need a thumbnail to post to Instagram.' );
    }

    foreach ( $social_post['thumbnail_paths'] as $thumbnail_path ) {
      $ext = pathinfo( $thumbnail_path, PATHINFO_EXTENSION );
      if ( $ext !== 'jpeg' && $ext !== 'jpg' ) {
        throw new Exception( 'Instagram: The thumbnail\'s format must be JPEG. The current format is ' . $ext . '.' );
      }
    }

    // Upload a photo/photos and create an IG Container beforehand.
    $container_id = count( $social_post['thumbnail_urls'] ) === 1
      ? $this->upload( $social_post['account_id'], $social_post['thumbnail_urls'][0], $social_post['post_content'], $connection )
      : $this->uploadAsCarousel( $social_post['account_id'], $social_post['thumbnail_urls'], $social_post['post_content'], $connection );

    try {
      // @see: https://developers.facebook.com/docs/instagram-api/reference/ig-user/media_publish#creating
      $res = $connection->post( "/{$social_post['account_id']}/media_publish?creation_id={$container_id}" );
    }
    catch ( FacebookResponseException $e ) {
      throw new Exception( $e->getMessage(), $e->getHttpStatusCode() );
    }
    catch ( Exception $e ) {
      throw new Exception( $e->getMessage() );
    }
    $post_date = new DateTime();
    return [
      'posted_id' => $res->getDecodedBody()['id'],
      'post_date' => get_date_from_gmt( $post_date->format( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' ),
    ];
  }

  // @see: https://developers.facebook.com/docs/instagram-api/reference/ig-user/media#creating
  public function upload( $account_id, $media, $caption, $connection ) {
    try {
      $caption = urlencode( $caption );
      $res = $connection->post( "/{$account_id}/media?image_url={$media}&caption={$caption}" );
      return $res->getDecodedBody()['id'];
    }
    catch ( FacebookResponseException $e ) {
      throw new Exception( $e->getMessage(), $e->getHttpStatusCode() );
    }
    catch ( Exception $e ) {
      throw new Exception( $e->getMessage() );
    }
  }

  // @see: https://developers.facebook.com/docs/instagram-api/guides/content-publishing#carousel-posts
  public function uploadAsCarousel( $account_id, $medias, $caption, $connection ) {
    try {
      $container_ids = [];
      foreach ( $medias as $media ) {
        $res = $connection->post( "/{$account_id}/media?image_url={$media}&is_carousel_item=true" );
        $container_ids[] = $res->getDecodedBody()['id'];
      }
      $children = urlencode( implode( ',', $container_ids ) );
      $caption = urlencode( $caption );
      $res = $connection->post( "/{$account_id}/media?caption={$caption}&media_type=CAROUSEL&children={$children}" );
      return $res->getDecodedBody()['id'];
    }
    catch ( FacebookResponseException $e ) {
      throw new Exception( $e->getMessage(), $e->getHttpStatusCode() );
    }
    catch ( Exception $e ) {
      throw new Exception( $e->getMessage() );
    }
  }

  public function stats( $posted_id, $settings, $account_id ) {
    throw new Exception( "Not supported yet." );
    // // Look for TokenID for this page
    // $page_access_token = null;
    // foreach ( $settings['accounts'] as $account ) {
    //   if ( $account_id === $account['id'] ) {
    //     $page_access_token = $account['access_token'];
    //     break;
    //   }
    // }

    // if ( empty( $page_access_token ) ) {
    //   throw new Exception( 'The Page Access Token could not be found in the Accounts.' );
    // }

    // $connection = $this->create_connection( $settings );
    // try {
    //   $response = $connection->get(
    //     '/' . $posted_id . '/likes?summary=total_count',
    //     $page_access_token
    //   );
    // } catch( FacebookResponseException $e ) {
    //   throw new Exception( $e->getMessage(), $e->getHttpStatusCode() );
    // } catch( FacebookSDKException $e ) {
    //   throw new Exception( $e->getMessage(), $e->getHttpStatusCode() );
    // }
    // return $response->getDecodedBody();
  }

}