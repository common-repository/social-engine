<?php

class Meow_SCLEGN_API {

    public $core;
    public $namespace = 'social-engine/api/v1';
    private $bearer_token = null;
    
    public function __construct( $core ) {
        $this->core = $core;
        add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
    }

    function rest_api_init() {
        $public_api = $this->core->get_option( 'public_api', false );
        if (!$public_api) { return; }

        $this->bearer_token = $this->core->get_option( 'api_token', null );
        if ( !empty( $this->bearer_token ) ) {
            add_filter( 'sclegn_allow_public_api', [ $this, 'auth_via_bearer_token' ], 10, 3 );
        }
        
        register_rest_route( $this->namespace, '/post', array(
            'methods' => 'POST',
            'callback' => array( $this, 'rest_api_post' ),
            'permission_callback' => function ( $request ) {
                return $this->core->can_access_public_api( 'post', $request );
            }
        ) );

        register_rest_route( $this->namespace, '/accounts', array(
            'methods' => 'GET',
            'callback' => array( $this, 'rest_api_accounts' ),
            'permission_callback' => function ( $request ) {
                return $this->core->can_access_public_api( 'accounts', $request );
            }
        ) );

        register_rest_route( $this->namespace, '/account', array(
            'methods' => 'GET',
            'callback' => array( $this, 'rest_api_account' ),
            'permission_callback' => function ( $request ) {
                return $this->core->can_access_public_api( 'account', $request );
            }
        ) );
    }

    #region Helpers
    private function get_default_account() {
        $accounts = $this->core->get_accounts();
        $default_accountID = $this->core->get_option( 'default_service', null );

        $accounts = array_filter( $accounts, function( $account ) use ( $default_accountID ) {
            return $account['accountId'] === $default_accountID;
        } );

        $account = array_shift( $accounts );

        $response = array(
            'name' => $account['name'],
            'type' => $account['type'],
            'serviceId' => $account['serviceId'],
            'accountId' => $account['accountId'],
        );

        return $response;
    }
    #endregion

    public function rest_api_post( $request ) {
        $params = $request->get_params();

        $post_date = new DateTime( 'now' );
        $post_content = $params['content'] ?? '';
        $thumbnail_ids = $params['images'] ?? [];

        $post_status = $params['status'] ?? 'publish';
        
        $service_id = $params['service_id'] ?? null;
        $account_id = $params['account_id'] ?? null;
        $service_type = $params['service_type'] ?? null;

        $use_default_account = $params['use_default_account'] ?? true;
        if ($use_default_account) {
            $default_account = $this->get_default_account();
            $service_id = $default_account['serviceId'];
            $account_id = $default_account['accountId'];
            $service_type = $default_account['type'];
        }

        $post_id = $this->core->create_social_post( $post_date, $post_content, $post_status, $thumbnail_ids, $service_id, $account_id, $service_type );

        $response = array(
            'post_id' => $post_id
        );

        if ($post_status === 'publish') {
            wp_publish_post( $post_id );
        }

        return rest_ensure_response( $response );
    }

    public function rest_api_account( $request ) {
        return rest_ensure_response( $this->get_default_account() );
    }

    public function rest_api_accounts( $request ) {
        $params = $request->get_params();

        $name = $params['name'] ?? null;
        $type = $params['type'] ?? null;

        $accounts = $this->core->get_accounts();
         
        $response = array();
        foreach ( $accounts as $account ) {
            if (
                ($name === null || $account['name'] === $name) &&
                ($type === null || $account['type'] === $type))
            {
                $response[] = array(
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'serviceId' => $account['serviceId'],
                    'accountId' => $account['accountId'],
                );
            }
        }

        return rest_ensure_response( $response );
    }

    #region Auth

	public function auth_via_bearer_token( $allow, $feature, $extra ) {
		if ( !empty( $extra ) && !empty( $extra->get_header( 'Authorization' ) ) ) {    
			$token = $extra->get_header( 'Authorization' );
			$token = str_replace( 'Bearer ', '', $token );
			if ( $token === $this->bearer_token ) {
				$admin = $this->get_admin_user();
				wp_set_current_user( $admin->ID, $admin->user_login );
				return true;
			}
		}
		return $allow;
	}

	function can_access_public_api( $feature, $extra ) {
		$logged_in = is_user_logged_in();
		return apply_filters( 'sclegn_allow_public_api', $logged_in, $feature, $extra );
	}

	function get_admin_user() {
		$admin = get_users( [ 'role' => 'administrator' ] );
		if ( !empty( $admin ) ) {
			return $admin[0];
		}
		return null;
	}

	#endregion


}