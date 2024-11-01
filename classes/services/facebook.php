<?php

require_once( SCLEGN_PATH . '/vendor/autoload.php' );

class Meow_SCLEGN_Services_Facebook
{
  public function __construct() {
    add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 10 );
  }

  public function plugins_loaded() {
    add_filter( 'sclegn_modules', function( $modules ) {
      if ( !in_array( 'facebook', $modules ) ) {
        $modules[] = [ 
          'type' => 'facebook', 
          'instance' => $this
        ];
      }
      return $modules;
    }, 10, 1 );

    add_filter( 'sclegn_accounts', function( $accounts ) {
      $options = get_option( Meow_SCLEGN_Core::get_plugin_option_name(), null );
      $services = $options['services'] ?? [];
      foreach ( $services as &$service ) {
        if ( $service['type'] === 'facebook' && isset( $service['accounts'] ) ) {
          $serviceId = $service['id'];
          foreach ( $service['accounts'] as &$serviceAccount ) {
            if ( isset( $serviceAccount['enabled'] ) && $serviceAccount['enabled'] ) {
              $accounts[] = [ 
                'type' => 'facebook',
                'name' => $serviceAccount['name'],
                'serviceId' => $serviceId,
                'accountId' => $serviceAccount['id'],
                'instance' => $this
              ];
            }
            $instaAccount = !empty( $serviceAccount['instagram'] ) ? $serviceAccount['instagram'] : null;
            if ( $instaAccount && isset( $instaAccount['enabled'] ) && $instaAccount['enabled'] ) {
              $accounts[] = [ 
                'type' => 'instagram',
                'name' => $instaAccount['name'],
                'serviceId' => $serviceId,
                'accountId' => $instaAccount['id'],
                'instance' => $this
              ];
            }
          }
        }
      }
      return $accounts;
    }, 10, 1 );
  }

  private function create_connection( $settings, $overrideToken = null ) {
    // App Key === API Key === Consumer API Key === Consumer Key === Customer Key === oauth_consumer_key
    // App Key Secret === API Secret Key === Consumer Secret === Consumer Key === Customer Key === oauth_consumer_secret

    if ( empty( $settings['app_id'] ) ) {
      throw new Exception("Facebook: The App ID is missing.");
    }
    if ( empty( $settings['app_secret'] ) ) {
      throw new Exception("Facebook: The App Secret is missing.");
    }

    $token = null;
    if ( !empty( $overrideToken ) ) {
      $token = $overrideToken;
    }
    else if ( isset( $settings['long_lived_token'] ) ) {
      $token = $settings['long_lived_token'];
    }
    else if ( isset( $settings['short_lived_token'] ) ) {
      $token = $settings['short_lived_token'];
    }
    if (!$token) {
      throw new Exception("Facebook: There is no token set up.");
    }

    $connection = new Facebook\Facebook([
      'app_id' => $settings['app_id'],
      'app_secret' => $settings['app_secret'],
      'default_access_token' => $token, // I hope this doesn't create any issue
      'default_graph_version' => 'v2.10'
    ]);

    return $connection;
  }

  // @see: https://developers.facebook.com/docs/graph-api/reference/page-post/#Creating
  public function post( $social_post, $settings ) {
    $attached_media = [];
    $page_access_token = null;

    // Look for TokenID for this page
    foreach ( $settings['accounts'] as $account ) {
      if ( $social_post['account_id'] === $account['id'] ) {
        $page_access_token = $account['access_token'];
        break;
      }
    }

    if ( empty( $page_access_token ) ) {
      throw new Exception( 'Facebook: The Page Access Token could not be found in the Accounts.' );
    }

    $connection = $this->create_connection( $settings, $page_access_token );

    // Upload Photos
    // @see: https://developers.facebook.com/docs/graph-api/reference/page/photos/#upload
    if ( isset( $social_post['thumbnail_paths'] ) && count( $social_post['thumbnail_paths'] ) > 0 ) {
      foreach ( $social_post['thumbnail_paths'] as $thumbnail_path ) {
        $media = $this->upload( $social_post['account_id'], $thumbnail_path, $connection  );
        $attached_media[] = [ 'media_fbid' => $media['id'] ];
      }
    }
    try {

      $link = $social_post['post_link'];

      if ( !filter_var( $link, FILTER_VALIDATE_URL ) ) {
        error_log( "[SOCIAL ENGINE] The link is not valid: $link. It will be ignored.");
        $link = null;
      }

      $res = $connection->post( "/{$social_post['account_id']}/feed", [
        'message' => $social_post['post_content'],
        'attached_media' => $attached_media,
        'link'=> $link,
      ] );
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

  public function upload( $page_id, $media, $connection ) {
    $source = $connection->fileToUpload($media);
    try {
      $res = $connection->post( "/{$page_id}/photos", [ 'source' => $source, 'published' => false ] );
      return $res->getGraphNode();
    }
    catch ( FacebookResponseException $e ) {
      throw new Exception( $e->getMessage(), $e->getHttpStatusCode() );
    }
    catch ( Exception $e ) {
      throw new Exception( $e->getMessage() );
    }
  }

  public function request_service( $settings, $action, $params ) {

    if ( $action === "long-live-token" ) {
      // https://www.daniloaz.com/en/how-to-get-a-permanent-token-to-access-a-facebook-page/
      $connection = $this->create_connection( $settings );
      $oAuth2Client = $connection->getOAuth2Client();
      $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken( $settings['short_lived_token'] );
      $expiresAt = $longLivedAccessToken->getExpiresAt();
      $expiresAt = $expiresAt ? $expiresAt->format("c") : $expiresAt;
      return array( 'token' => $longLivedAccessToken->getValue(), 'expiresAt' => $expiresAt );
    }

    if ( $action === "accounts" ) {
      $retrieveEnabledIds = function ($array) {
        $ids = [];
        foreach ( $array as $data ) {
          if ( $data['enabled'] ) {
            $ids[] = $data['id'];
          }
        }
        return $ids;
      };
      $settings['accounts'] = isset( $settings['accounts'] ) ? $settings['accounts'] : [];
      $currentEnabledFacebookIds = $retrieveEnabledIds( $settings['accounts'] );
      $currentEnabledInstagramIds = $retrieveEnabledIds( array_column( $settings['accounts'], 'instagram' ) );

      $connection = $this->create_connection( $settings );
      $res = $connection->get('/me/accounts');
      $resBody = $res->getDecodedBody();
      $pages = [];
      foreach ( $resBody['data'] as $page ) {
        $instagram = null;
        $pageId = $page['id'];
        $pageToken = $page['access_token'];
        $pageRes = $connection->get("/$pageId?fields=instagram_business_account", $pageToken );
        $pageBody = $pageRes->getDecodedBody();
        if ( isset( $pageBody['instagram_business_account'] ) ) {
          $instagramId = $pageBody['instagram_business_account']['id'];
          $pageRes = $connection->get("/$instagramId?fields=username", $pageToken );
          $pageBody = $pageRes->getDecodedBody();
          $instagram = array(
            'id' => $pageBody['id'],
            'name' => $pageBody['username'],
            'enabled' => in_array($pageBody['id'], $currentEnabledInstagramIds),
          );
        }
        array_push( $pages, array( 
          'id' => $pageId,
          'name' => $page['name'],
          'access_token' => $pageToken,
          'instagram' => $instagram,
          'enabled' => in_array($pageId, $currentEnabledFacebookIds),
        ) );
      }
      return $pages;
    }

    if ( $action === "test" ) {
      $connection = $this->create_connection( $settings );
      $response = $connection->get('/me?fields=id,name');
      //error_log( 'TEST: ' . print_r( $response, 1 ) );
      return true;
    }

    return false;
  }

  public function stats( $posted_id, $settings, $account_id ) {
    // Look for TokenID for this page
    $page_access_token = null;
    foreach ( $settings['accounts'] as $account ) {
      if ( $account_id === $account['id'] ) {
        $page_access_token = $account['access_token'];
        break;
      }
    }

    if ( empty( $page_access_token ) ) {
      throw new Exception( 'The Page Access Token could not be found in the Accounts.' );
    }

    $connection = $this->create_connection( $settings );
    try {
      $response = $connection->get(
        '/' . $posted_id . '/reactions?summary=total_count',
        $page_access_token
      );
    } catch( FacebookResponseException $e ) {
      throw new Exception( $e->getMessage(), $e->getHttpStatusCode() );
    } catch( FacebookSDKException $e ) {
      throw new Exception( $e->getMessage(), $e->getHttpStatusCode() );
    }
    $body = $response->getDecodedBody();

    return [
      'likes' => $body['summary']['total_count'],
    ];
  }

}