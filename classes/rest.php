<?php

class Meow_SCLEGN_Rest
{
	private $core = null;
	private $namespace = 'social-engine/v1';
	private $admin = null;

	public function __construct( $core, $admin ) {
		if ( !( $core->can_access_features() || $core->can_access_settings() ) ) {
			return;
		} 
		$this->core = $core;
		$this->admin = $admin;
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	function rest_api_init() {
		try {

			// SETTINGS
			register_rest_route( $this->namespace, '/update_option', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_update_option' )
			) );
			register_rest_route( $this->namespace, '/request_service', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_request_service' )
			) );
			register_rest_route( $this->namespace, '/reset_all_data', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_reset_all_data' )
			) );

			// SETTINGS & DASHBOARD
			register_rest_route( $this->namespace, '/all_settings', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_all_settings' ),
			) );

			// DASHBOARD
			register_rest_route( $this->namespace, '/stats', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_get_stats' ),
				'args' => array(
					'serviceFilterBy' => array( 'required' => false, 'default' => 'all' ),
				),
			) );
			register_rest_route( $this->namespace, '/post_stats', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_get_post_stats' ),
				'args' => array(
					'postId' => array( 'required' => true ),
				),
			) );
			register_rest_route( $this->namespace, '/social_posts', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_social_posts' ),
				'args' => array(
					'limit' => array( 'required' => false, 'default' => 10 ),
					'skip' => array( 'required' => false, 'default' => 0 ),
					'filterBy' => array( 'required' => false, 'default' => 'all' ),
					'serviceFilterBy' => array( 'required' => false, 'default' => 'all' ),
					'orderBy' => array( 'required' => false, 'default' => 'post_date' ),
					'order' => array( 'required' => false, 'default' => 'desc' ),
					'search' => array( 'required' => false ),
					'offset' => array( 'required' => false ),
					'order' => array( 'required' => false ),
				)
			) );
			register_rest_route( $this->namespace, '/upload_image', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_upload_image' )
			) );
			register_rest_route( $this->namespace, '/create_social_post', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_create_social_post' )
			) );
			register_rest_route( $this->namespace, '/update_social_post', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_update_social_post' )
			) );
			register_rest_route( $this->namespace, '/delete_social_posts', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_delete_social_posts' )
			) );
			register_rest_route( $this->namespace, '/latest_photos', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_latest_photos' ),
				'args' => array(
					'search' => array( 'required' => false ),
					'offset' => array( 'required' => false, 'default' => 0 ),
					'except' => array( 'required' => false ),
				)
			) );
			register_rest_route( $this->namespace, '/recent_photos', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_recent_photos' ),
				'args' => array(
					'search' => array( 'required' => false )
				)
			) );
			register_rest_route( $this->namespace, '/publish_now', array(
				'methods' => 'POST',
				'permission_callback' => '__return_true',
				'callback' => array( $this, 'rest_publish_now' )
			) );
			register_rest_route( $this->namespace, '/is_moderator', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_is_moderator' ),
			) );
			register_rest_route( $this->namespace, '/published_post_ids', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_published_post_ids' ),
				'args' => array(
					'postType' => array( 'required' => true ),
				),
			) );
			register_rest_route( $this->namespace, '/revive_post', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_revive_post' )
			) );
			register_rest_route( $this->namespace, '/ai_suggestions', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_ai_suggestions' )
			) );
			register_rest_route( $this->namespace, '/ai_format_suggestion', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_ai_format_suggestion' )
			) );
		}
		catch (Exception $e) {
			var_dump($e);
		}
	}

	/**
	 * SETTINGS
	 */
	function rest_all_settings() {
		return new WP_REST_Response( [
			'success' => true,
			'data' => $this->core->get_all_options()
		], 200 );
	}

	function rest_update_option( $request ) {
		try {
			$params = $request->get_json_params();
			$value = $params['options'];
			list( $success, $message, $options ) = $this->core->update_options( $value );
			$message = __( $message, 'social-engine' );
			return new WP_REST_Response([ 'success' => $success, 'message' => $message, 'options' => $success ? $options : null ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
	}

	function rest_request_service( $request ) {
		$params = $request->get_json_params();
		$id = isset( $params['id'] ) ? $params['id'] : null;
		$action = isset( $params['action'] ) ? $params['action'] : 'test';
		$params = isset( $params['params'] ) ? $params['params'] : [];
		global $sclegn;
		try {
			$settings = $sclegn->get_service_settings( $id );
			$data = $sclegn->request_service( $settings, $action, $params );
			return new WP_REST_Response( [ 'success' => true, 'data' => $data ], 200 );
		}
		catch (\Exception $e) {
			return new WP_REST_Response( [ 'success' => false, 'message' => $e->getMessage() ], 200 );
		}
		catch (\Throwable $e) {
			return new WP_REST_Response( [ 'success' => false, 'message' => $e->getMessage() ], 200 );
		}
	}

	function rest_reset_all_data() {
		delete_option( Meow_SCLEGN_Core::get_plugin_option_name() );
		$args = [
			'post_type' => $this->core->get_social_post_type(),
			'numberposts' => -1,
			'post_status' => 'any',
		];
		$posts = get_posts( $args );
		try {
			foreach ($posts as $post) {
				$this->core->delete_social_post($post->ID);
			}
			return new WP_REST_Response([ 'success' => true ], 200 );
		}
		catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500 );
		}
	}

	/**
	 * DASHBOARD
	 */
	function rest_get_stats($request) {
		$serviceFilterBy = trim( $request->get_param('serviceFilterBy') );
		return new WP_REST_Response( [ 'success' => true, 'data' => array(
			'all' => $this->count_all( $serviceFilterBy ),
			'draft' => $this->count_draft( $serviceFilterBy ),
			'ready' => $this->count_ready( $serviceFilterBy ),
			'published' => $this->count_publish( $serviceFilterBy ),
		) ], 200 );
	}
	function rest_get_post_stats ($request) {
		$post_id = trim( $request->get_param('postId') );
		try {
			return new WP_REST_Response( [
				'success' => true,
				'data' => $this->core->post_stats($post_id)
			] );
		}
		catch ( \Exception $e ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => $e->getMessage() ], 200 );
		}
		catch ( \Throwable $e ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => $e->getMessage() ], 200 );
		}
	}
	function rest_social_posts( $request ) {
		$limit = trim( $request->get_param('limit') );
		$skip = trim( $request->get_param('skip') );
		$filterBy = trim( $request->get_param('filterBy') );
		$serviceFilterBy = trim( $request->get_param('serviceFilterBy') );
		$orderBy = trim( $request->get_param('orderBy') );
		$order = trim( $request->get_param('order') );
		$search = trim( $request->get_param('search') );

		$post_status = [];
		if ( $filterBy === 'all' ) {
			$post_status = ['draft', 'future'];
		}
		if ( $filterBy === 'ready' ) {
			$post_status = ['future'];
		}
		else if ( $filterBy === 'draft' ) {
			$post_status = ['draft'];
		}
		else if ( $filterBy === 'published' ) {
			$post_status = ['publish'];
		}

		$args = [
			'post_type' => $this->core->get_social_post_type(),
			'numberposts' => $limit,
			'post_status' => $post_status,
			'orderby' => 'ID',
			'order' => 'ASC',
		];
		if ($serviceFilterBy !== 'all') {
			$args = array_merge($args, [
				'meta_key' => 'sclegn_account_id',
				'meta_value' => $serviceFilterBy,
			]);
		}
		$posts = get_posts( $args );
		$data = [];
		$current_user = wp_get_current_user();
		foreach ($posts as $post) {
			$date = new DateTime($post->post_date);
			$group_date = $date->format('Y-m-d');
			$thumbnail_ids = json_decode(get_post_meta( $post->ID, 'sclegn_thumbnails', true )) ?? [];
			$thumbnail_urls = [];
			$zoom_urls = [];
			$thumbnails = [];
			foreach ($thumbnail_ids as $thumbnail_id) {
				$thumbnail_url = wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' );
				$zoom_url = wp_get_attachment_image_url( $thumbnail_id, 'large' );
				$medium_url = wp_get_attachment_image_url( $thumbnail_id, 'medium' );
				$title = get_the_title( $thumbnail_id );
				$mime = get_post_mime_type( $thumbnail_id );
				$thumbnail_urls[] = $thumbnail_url;
				$zoom_urls[] = $zoom_url;
				$thumbnails[] = [
					'id' => $thumbnail_id,
					'url' => $thumbnail_url,
					'zoom_url' => $zoom_url,
					'medium_url' => $medium_url,
					'title' => $title,
					'mime' => $mime,
				];
			}
			$data[$group_date][] = [
				'id' => $post->ID,
				'post_content' => $post->post_content,
				'post_link' => get_post_meta( $post->ID, 'sclegn_post_link', true),
				'thumbnail_ids' => $thumbnail_ids,
				'thumbnail_urls' => $thumbnail_urls,
				'thumbnails' => $thumbnails,
				'zoom_urls' => $zoom_urls,
				'post_date' => str_replace('-', '/', $post->post_date),
				'service_type' => get_post_meta( $post->ID, 'sclegn_service_type', true),
				'account_id' => get_post_meta( $post->ID, 'sclegn_account_id', true),
				'service_id' => get_post_meta( $post->ID, 'sclegn_service_id', true),
				'post_status' => $post->post_status,
				'is_author' => $post->post_author == $current_user->ID,
			];
		}
		// Sort by date (ASC)
		ksort($data);

		return new WP_REST_Response( [
			'success' => true,
			'data' => $data,
			'total' => count($posts),
		], 200 );
	}

	function rest_upload_image() {
		// it needs to load these files to use media_handle_upload().
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		$attachment_id = media_handle_upload( 'file', 0, [], [
			'test_form' => false,
			'mimes' => $this->core->get_allowed_thumbnail_mimes(),
		] );
		if ( is_wp_error( $attachment_id ) ) {
			$errors = $attachment_id->get_error_messages();
			return new WP_REST_Response([
				'success' => false,
				'message' => implode(',', $errors),
			], 500 );
		}
		return new WP_REST_Response([
			'success' => true,
			'data' => [
				'thumbnail_id' => $attachment_id,
				'thumbnail_url' => wp_get_attachment_thumb_url( $attachment_id ),
				'zoom_url' => wp_get_attachment_image_url( $attachment_id, 'large' ),
				'mime' => get_post_mime_type( $attachment_id ),
			]
		], 200 );
	}

	function rest_create_social_post( $request ) {
		$params = $request->get_json_params();
		$thumbnail_ids = isset( $params['thumbnail_ids']) ? $params['thumbnail_ids'] : null;
		$post_content = isset( $params['post_content']) ? $params['post_content'] : 'This post was sent using Social Engine on WordPress ! Learn more at : https://meowapps.com/ 😼';
		$service_id = isset( $params['service_id']) ? $params['service_id'] : null;
		$account_id = isset( $params['account_id']) ? $params['account_id'] : null;
		$post_link = isset( $params['post_link']) ? $params['post_link'] : null;
		$service_type = isset( $params['service_type']) ? $params['service_type'] : null;
		$post_status = isset( $params['post_status']) ? $params['post_status'] : 'draft';
		$post_date = isset( $params['post_date']) ? new DateTime($params['post_date']) : new DateTime();

		try {
			$post_id = $this->core->create_social_post( $post_date, $post_content, $post_status, $thumbnail_ids, $service_id, $account_id, $service_type, $post_link );
			return new WP_REST_Response([
				'success' => true,
				'data' => [
					'post_id' => $post_id
				]
			], 200 );
		}
		catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500 );
		}
	}

	function rest_update_social_post( $request ) {
		$params = $request->get_json_params();
		$post_id = isset( $params['id']) ? $params['id'] : null;

		if (!$post_id) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'The post id to update is missing.',
			], 400 );
		}

		$thumbnail_ids = isset( $params['thumbnail_ids']) ? $params['thumbnail_ids'] : null;
		$post_content = isset( $params['post_content']) ? $params['post_content'] : 'This post was updated using Social Engine on WordPress ! Learn more at : https://meowapps.com/ 😼';
		$service_id = isset( $params['service_id']) ? $params['service_id'] : null;
		$account_id = isset( $params['account_id']) ? $params['account_id'] : null;
		$service_type = isset( $params['service_type']) ? $params['service_type'] : null;
		$post_status = isset( $params['post_status']) ? $params['post_status'] : null;
		$post_date = isset( $params['post_date']) ? new DateTime($params['post_date']) : new DateTime();

		if ( in_array( $post_status, ['future', 'publish'] ) ) {
			if ( !$this->core->can_access_moderation_features() ) {
				return new WP_REST_Response([
					'success' => false,
					'message' => "Only moderator of Social Engine can do this.",
				], 500 );
			}
		}

		try {
			$this->core->update_social_post( $post_id, $post_date, $post_content, $post_status, $thumbnail_ids, $service_id, $account_id, $service_type );
			return new WP_REST_Response([ 'success' => true ], 200 );
		}
		catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500 );
		}
	}

	function rest_delete_social_posts( $request ) {
		$params = $request->get_json_params();
		$post_ids = isset( $params['ids']) ? (array)$params['ids'] : null;

		if (!$post_ids) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'The post id(s) to update is missing.',
			], 400 );
		}

		try {
			$this->core->delete_social_posts($post_ids);
			return new WP_REST_Response([ 'success' => true ], 200 );
		}
		catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500 );
		}
	}

	function rest_latest_photos($request) {
		$search = trim( $request->get_param('search') );
		$offset = trim( $request->get_param('offset') );
		$except = json_decode(trim( $request->get_param('except') ), true);
		$unusedImages = trim( $request->get_param('unusedImages') );

		global $wpdb;
		$searchPlaceholder = $search ? '%' . $search . '%' : '';
		$where_search_clause = $search ? $wpdb->prepare(
			"AND (p.post_title LIKE %s OR p.post_content LIKE %s OR p.post_name LIKE %s) ",
			$searchPlaceholder,
			$searchPlaceholder,
			$searchPlaceholder
		) : '';
		$where_search_clause .= $except && count($except) ? $wpdb->prepare(
			"AND p.ID NOT IN (" . implode(', ', array_fill(0, count($except), '%s')). ")", $except
		) : '';
		$join_clause = '';
		if ( $unusedImages ) {
			$join_clause = "LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'sclegn_posts' ";
			$where_search_clause .= "AND pm.meta_id IS NULL";
		}
		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, p.post_mime_type 
				FROM $wpdb->posts p 
				$join_clause
				WHERE p.post_type='attachment' 
				AND p.post_status='inherit' 
				$where_search_clause 
				ORDER BY p.post_modified DESC 
				LIMIT %d, 23", $offset
			), OBJECT
		);
		$posts_count = (int)$wpdb->get_var(
			"SELECT COUNT(*)
			FROM $wpdb->posts p 
			$join_clause
			WHERE p.post_type='attachment' 
			AND p.post_status='inherit' 
			$where_search_clause"
		);

		$data = [];
		foreach ( $posts as $post ) {
			$file_url = get_attached_file( $post->ID );
			if ( file_exists( $file_url ) ) {
				$data[] = [
					'id' => $post->ID,
					'thumbnail_url' => wp_get_attachment_image_url($post->ID, 'thumbnail'),
					'zoom_url' => wp_get_attachment_image_url($post->ID, 'large'),
					'title' => $post->post_title,
					'filename' => basename( $file_url ),
					'size' => size_format( filesize( $file_url ) ),
					'mime' => $post->post_mime_type,
				];
			}
		}
		return new WP_REST_Response( [
			'success' => true,
			'data' => $data,
			'total' => $posts_count
		], 200 );
	}

	function rest_recent_photos( $request ) {
		$search = trim( $request->get_param('search') );
		$limit = 3;

		$results = $this->core->get_recent_photos($limit, $search);

		$photo_count = 0;
		$fill_photos = false;
		$data = [];
		$thumbnail_ids = [];
		$post_ids = [];
		if (count($results)) {
			foreach ($results as $record) {
				$post_ids[] = $record['ID'];
				$thumbnail_ids = array_merge($thumbnail_ids, json_decode($record['meta_value']) ?? []);

				if (count($thumbnail_ids) >= $limit) {
					$thumbnail_ids = array_slice($thumbnail_ids, 0, $limit);
					$fill_photos = true;
					break;
				}
			}
		}

		// Find more if the thumbnails are less than the $limit.
		if (!$fill_photos) {
			$limit = $limit - $photo_count;
			$results = $this->core->get_recent_photos($limit, null, $post_ids);
			foreach ($results as $record) {
				$thumbnail_ids = array_merge($thumbnail_ids, json_decode($record['meta_value']) ?? []);

				if (count($thumbnail_ids) >= $limit) {
					break;
				}
			}
		}

		foreach ($thumbnail_ids as $thumbnail_id) {
			$data[] = [
				'id' => $thumbnail_id,
				'thumbnail_url' => wp_get_attachment_image_url($thumbnail_id, 'thumbnail'),
			];
		}

		return new WP_REST_Response( [
			'success' => true,
			'data' => $data,
		], 200 );
	}

	function rest_publish_now( $request ) {
		$params = $request->get_json_params();
		$post_id = isset( $params['post_id']) ? $params['post_id'] : null;

		if (!$post_id) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'The post id to post is missing.',
			], 400 );
		}

		$thumbnail_ids = json_decode( get_post_meta( $post_id, 'sclegn_thumbnails', true ) ) ?? [];
		$allowd_mimes = array_unique( array_values( $this->core->get_allowed_thumbnail_mimes() ) );
		foreach ( $thumbnail_ids as $thumbnail_id ) {
			if ( !in_array( get_post_mime_type( $thumbnail_id ), $allowd_mimes, true ) ) {
				return new WP_REST_Response([
					'success' => false,
					'message' => 'The thumbnail\'s format must be JPEG.',
				], 400 );
			}
		}

		try {
			wp_publish_post( $post_id );
			//$sclegn->post($post_id);
			return new WP_REST_Response( [ 'success' => true ], 200 );
		}
		catch (\Exception $e) {
			return new WP_REST_Response( [ 'success' => false, 'message' => $e->getMessage() ], 200 );
		}
		catch (\Throwable $e) {
			return new WP_REST_Response( [ 'success' => false, 'message' => $e->getMessage() ], 200 );
		}
	}

	function rest_is_moderator() {
		return new WP_REST_Response( [
			'success' => true,
			'data' => $this->core->can_access_moderation_features()
		], 200 );
	}

	function rest_published_post_ids( $request ) {
		$post_type = trim( $request->get_param('postType') );

		if (!$post_type) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'The postType is missing.',
			], 400 );
		}

		return new WP_REST_Response( [
			'success' => true,
			'data' => $this->core->get_published_post_ids( $post_type ),
		] );
	}

	function rest_ai_format_suggestion( $request ) {
		$params = $request->get_json_params();

		$suggestion = $params['suggestion'];
		$currentCaption = $params['currentCaption'];

		if ( empty( $suggestion ) ) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'The suggestion is missing.',
			], 400 );
		}

		try {
			global $mwai;

			$prompt = "Using the following suggestions : \n\n $suggestion \n\n ";
			if ( $currentCaption != "" ) { $prompt .= "The current caption is: '$currentCaption'. "; }
			$prompt .= "Generate a caption for the social post. Reply only with the new post caption.";

			$formatted_suggestion = $mwai->simpleTextQuery( $prompt );
			$formatted_suggestion = trim( $formatted_suggestion );
			$formatted_suggestion = str_replace( '"', "", $formatted_suggestion );

			return new WP_REST_Response( [ 'success' => true, 'data' => $formatted_suggestion ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response( [ 'success' => false, 'error' => $e->getMessage() ] );
		}

	}

	function rest_ai_suggestions( $request ) {
		$params = $request->get_json_params();

		$media_ids = $params['selectedPhotos'];
		$service_type = $params['socialPostType'];
		$post_content = $params['postContent'];

		if ( empty( $media_ids ) ) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'The media urls are missing.',
			], 400 );
		}

		try {

			global $mwai;
			if ( empty( $mwai ) ) {
				return new WP_REST_Response([
					'success' => false,
					'message' => 'AI Engine is not available.',
				], 400 );
			}

			$suggestions = array();

			$prompt = "Analyze this image and generate consize small tips to make an engaging $service_type post about it.";
			if ( $post_content != "" ) { $prompt .= "\nThe post content is: '$post_content'."; }
			$prompt .= "\n\n";
			$prompt .= "Give keywords that would fit the image and generate a caption. Format response with HTML tags <ul>, <li> and <b> tags.";

			foreach ( $media_ids as $media_id ) {

				$thumbnail = wp_get_attachment_image_src( $media_id, 'medium' );
				$thumbnail_path = $thumbnail[0];
				$thumbnail_url = $thumbnail[0];

				if ( empty( $thumbnail_path ) || empty( $thumbnail_url ) ) {
					$media_path = get_attached_file( $media_id );
					$media_url = wp_get_attachment_url( $media_id );
					$thumbnail_path = $media_path;
					$thumbnail_url = $media_url;
				}

				$suggestions[] = $mwai->simpleVisionQuery( $prompt, $thumbnail_url, $thumbnail_path );
			}

			return new WP_REST_Response( [ 'success' => true, 'data' => $suggestions ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response( [ 'success' => false, 'error' => $e->getMessage() ] );
		}
	}

	function rest_revive_post( $request ) {
		$params = $request->get_json_params();
		$post_id = isset( $params['post_id']) ? $params['post_id'] : null;
		$account_id = isset( $params['account_id']) ? $params['account_id'] : null;
		$text_link = isset( $params['text_link']) ? $params['text_link'] : null;

		if ( !$post_id || !$account_id ) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'The post id or account id are missing.',
			], 400 );
		}

		try {
			$this->core->revive_as_social_post( $post_id, $account_id, $text_link );
			return new WP_REST_Response( [ 'success' => true ] );
		} catch ( Exception $e ) {
			return new WP_REST_Response( [ 'success' => false, 'error' => $e->getMessage() ] );
		}
	}

	/**
	 * Private
	 */
	function count_all( $serviceFilterBy ) {
		global $wpdb;
		$filter_sql = $this->get_count_filter_sql( $serviceFilterBy );
		$post_type = $this->core->get_social_post_type();
		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM $wpdb->posts p 
			$filter_sql
			WHERE post_type = %s 
			AND post_status IN ('draft', 'future')", $post_type
		);
		return (int)$wpdb->get_var( $sql );
	}

	function count_draft( $serviceFilterBy ) {
		global $wpdb;
		$filter_sql = $this->get_count_filter_sql( $serviceFilterBy );
		$post_type = $this->core->get_social_post_type();
		return (int)$wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $wpdb->posts p 
				$filter_sql
				WHERE post_type = %s 
				AND post_status = 'draft'", $post_type
			)
		);
	}

	function count_publish( $serviceFilterBy ) {
		global $wpdb;
		$filter_sql = $this->get_count_filter_sql( $serviceFilterBy );
		$post_type = $this->core->get_social_post_type();
		return (int)$wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $wpdb->posts p 
				$filter_sql
				WHERE post_type = %s 
				AND post_status = 'publish'", $post_type
			)
		);
	}

	function count_ready( $serviceFilterBy ) {
		global $wpdb;
		$filter_sql = $this->get_count_filter_sql( $serviceFilterBy );
		$post_type = $this->core->get_social_post_type();
		return (int)$wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $wpdb->posts p 
				$filter_sql
				WHERE post_type = %s 
				AND post_status = 'future'", $post_type
			)
		);
	}

	protected function get_count_filter_sql( $serviceFilterBy ) {
		global $wpdb;
		return $serviceFilterBy !== 'all'
			? $wpdb->prepare(
				"INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id 
				AND pm.meta_key = 'sclegn_account_id' AND pm.meta_value = %s ", $serviceFilterBy
			)
			: '';
	}
}
