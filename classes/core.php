<?php

class Meow_SCLEGN_Core
{
	public $admin = null;
	public $is_rest = false;
	public $is_cli = false;
	public $site_url = null;
	public $services = array(   );
	protected $social_post_type = 'social_post';

	private $default_template = "📣 Read our new article: {title} | {excerpt:10} \n\n 🔗 Read more at: {url}.";

	protected $allowed_thumbnail_mimes = [
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png' => 'image/png',
		'gif' => 'image/gif',
	];
	protected $day_ISO8601_numeric_representation = [
		'1' => 'monday',
		'2' => 'tuesday',
		'3' => 'wednesday',
		'4' => 'thursday',
		'5' => 'friday',
		'6' => 'saturday',
		'7' => 'sunday',
	];
	private static $plugin_option_name = 'sclegn_options';
	private $option_name = 'sclegn_options';

	public function __construct(  )
	{
		$this->site_url = get_site_url(  );
		$this->is_rest = MeowCommon_Helpers::is_rest(  );
		$this->is_cli = defined( 'WP_CLI' ) && WP_CLI;

		//Public API
		new Meow_SCLEGN_API( $this );

		// Actions
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );
		add_action( 'wp_after_insert_post', array( $this, 'after_insert_post' ), 10, 4 );
		add_filter( 'post_row_actions', array( $this, 'add_post_row_actions' ), 10, 2 );

		// Services
		new Meow_SCLEGN_Services_Twitter(  );
		new Meow_SCLEGN_Services_Facebook(  );
		new Meow_SCLEGN_Services_Instagram(  );
		new Meow_SCLEGN_Services_Mastodon(  );

		if ( class_exists( 'MeowPro_SCLEGN_Core' ) ) {
			new MeowPro_SCLEGN_Core( $this );
		}
	}

	public function plugins_loaded(  )
	{
		// Part of the core, settings and stuff
		$this->admin = new Meow_SCLEGN_Admin( $this );

		// Only for REST
		if ( $this->is_rest ) {
			new Meow_SCLEGN_Rest( $this, $this->admin );
		}

		// Dashboard
		if ( is_admin(  ) ) {
			new Meow_SCLEGN_UI( $this, $this->admin );
		}

		global $sclegn;
		$sclegn = $this;
	}


	public function get_services(  )
	{
		return $this->get_option( 'services', array(  ) );
	}

	public function get_modules(  )
	{
		return apply_filters( 'sclegn_modules', array(  ) );
	}

	public function get_accounts(  )
	{
		return apply_filters( 'sclegn_accounts', array(  ) );
	}

	public function get_service_settings( $id )
	{
		$services = $this->get_services(  );
		foreach ( $services as $service ) {
			if ( $service['id'] === $id ) {
				return $service;
			}
		}
		return null;
	}

	public function get_service_instance( $type )
	{
		$services = $this->get_modules(  );
		foreach ( $services as $service ) {
			if ( $service['type'] === $type ) {
				return $service['instance'];
			}
		}
		return null;
	}

	function transition_post_status( $newStatus, $oldStatus, $post )
	{
		//error_log(   "TRANSITION {$post->ID} (  {$post->post_type}  ): {$oldStatus} => {$newStatus}"   );

		// For Social Engine Posts (  the social post is actually posted when the status is changed to publish  )
		if ( 
			$oldStatus !== 'publish' && $oldStatus !== 'new' && $newStatus === 'publish'
			&& $post->post_type === $this->social_post_type
		 ) {
			$this->post( $post->ID );
		}

		// For WordPress Posts
		// Memo: At this point, the post's feature image has not been registered yet.
		// TODO: ...
	}

	function after_insert_post( $post_id, $post, $old_status, $post_before )
	{
		if ( 'publish' !== $post->post_status||( $post_before && 'publish' === $post_before->post_status )||wp_is_post_revision( $post_id ) || $post->post_type === $this->social_post_type ) {
			return;
		}
		
		error_log( "AFTER INSERT POST {$post_id} (  {$post->post_type}  ): {$old_status} => {$post->post_status}" );
		if ( $post ) {
			$this->auto_schedule( $post );
		}
	}

	function add_post_row_actions( $actions, $post )
	{
		if ( $post->post_type ) {
			$template = $this->get_option( 'template',  $this->default_template );
			if ( empty( $template ) ) {
				$template = $this->default_template;
			}

			$thumbnails = [];
			$thumbnail_ids = [];
			$thumbnail_urls = [];
			$thumbnail_id = get_post_thumbnail_id( $post->ID );
			if ( $thumbnail_id ) {
				$thumbnail_url = wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' );
				$zoom_url = wp_get_attachment_image_url( $thumbnail_id, 'large' );
				$medium_url = wp_get_attachment_image_url( $thumbnail_id, 'medium' );
				$thumbnails[] = [
					'id' => $thumbnail_id,
					'url' => $thumbnail_url,
					'zoom_url' => $zoom_url,
					'medium_url' => $medium_url,
					'title' => get_the_title( $thumbnail_id ),
				];
				$thumbnail_ids[] = $thumbnail_id;
				$thumbnail_urls[] = $thumbnail_url;
			}

			$content = $this->replace_template_variables( $template, $post );


			$thumbnails = wp_json_encode( $thumbnails );
			$thumbnail_ids = wp_json_encode( $thumbnail_ids );
			$thumbnail_urls = wp_json_encode( $thumbnail_urls );

			// Properly escape the content for use in JavaScript
			$content_js = esc_js( $content );
			$thumbnails_js = esc_js( $thumbnails );
			$thumbnail_ids_js = esc_js( $thumbnail_ids );
			$thumbnail_urls_js = esc_js( $thumbnail_urls );

			$actions['createSocialPost'] = '<a style="cursor:pointer;" class="sclegn-create-social-post" 
				onclick="window.sclegnProps = { 
					postContent: \'' . $content_js . '\', 
					postThumbnails: \'' . $thumbnails_js . '\',
					postThumbnailIds: \'' . $thumbnail_ids_js . '\',
					postThumbnailUrls: \'' . $thumbnail_urls_js . '\'
				}; window.dispatchEvent(  new CustomEvent(  \'sclegnPropsChange\', { detail: window.sclegnProps }  )  );">Create Social Post</a>';
		}
		return $actions;
	}

	function replace_template_variables( $template, $post )
	{
		$replacements = [
			'{title}' => get_the_title( $post->ID ),
			'{url}' => get_permalink( $post->ID ),
			'{date}' => get_the_date( '', $post->ID ),
			'{tags}' => get_the_tag_list( '', ', ', '', $post->ID ),
		];

		// Handle excerpt with word limit
		preg_match( '/{excerpt:(\d+)}/', $template, $matches );
		if ( !empty( $matches ) ) {
			$word_limit = intval( $matches[1] );
			$excerpt = wp_trim_words( get_the_excerpt( $post->ID ), $word_limit );
			$replacements[$matches[0]] = $excerpt;
		} else {
			$replacements['{excerpt}'] = get_the_excerpt( $post->ID );
		}

		// Handle ACF fields
		preg_match_all( '/{acf:([^}]+)}/', $template, $acf_matches );
		if ( !empty( $acf_matches[1] ) ) {
			foreach ( $acf_matches[1] as $acf_field ) {
				$acf_value = get_field( $acf_field, $post->ID );
				if ( is_array( $acf_value ) ) {
					// If the ACF field is an array, implode it into a comma-separated string
					$acf_value = implode( ', ', array_filter( $acf_value ) );
				} elseif ( is_object( $acf_value ) ) {
					// If it's an object (  like for relationship fields  ), try to get a meaningful string representation
					$acf_value = ( method_exists( $acf_value, '__toString' ) ) ? ( string )$acf_value : '';
				}
				$replacements['{acf:' . $acf_field . '}'] = $acf_value ? $acf_value : '';
			}
		}


		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}

	public function request_service( $settings, $action, $params )
	{
		$instance = $this->get_service_instance( $settings['type'] );
		return $instance->request_service( $settings, $action, $params );
	}

	public function post( $post_id )
	{
		try {
			// Get the social post.
			$post = get_post( $post_id );
			$post_link = get_post_meta( $post_id, 'sclegn_post_link', true );
			$thumbnail_ids = json_decode( get_post_meta( $post->ID, 'sclegn_thumbnails', true ) ) ?? [];
			$thumbnail_paths = [];
			$thumbnail_urls = [];
			foreach ( $thumbnail_ids as $thumbnail_id ) {
				$thumbnail_path = realpath( get_attached_file( $thumbnail_id, true ) );
				$thumbnail_url = wp_get_attachment_image_url( $thumbnail_id, 'large' );
				if ( !$thumbnail_path || !$thumbnail_url ) {
					throw new Error( 'Cannot find the attached image. Please check if the image exists.' );
				}
				$thumbnail_paths[] = $thumbnail_path;
				$thumbnail_urls[] = $thumbnail_url;
			}
			$social_post = [
				'id' => $post->ID,
				'post_content' => $post->post_content,
				'post_link' => $post_link,
				'thumbnail_ids' => $thumbnail_ids,
				'thumbnail_paths' => $thumbnail_paths,
				'thumbnail_urls' => $thumbnail_urls,
				'post_date' => $post->post_date,
				'service_type' => get_post_meta( $post->ID, 'sclegn_service_type', true ),
				'account_id' => get_post_meta( $post->ID, 'sclegn_account_id', true ),
				'service_id' => get_post_meta( $post->ID, 'sclegn_service_id', true ),
				'post_status' => $post->post_status,
			];
	
			// Publish the social post.
			$settings = $this->get_service_settings( $social_post['service_id'] );
			// The service type of the social post is the priority when there's a difference between the social post and settings.
			$type = $social_post['service_type'] !== $settings['type'] ? $social_post['service_type'] : $settings['type'];
			$instance = $this->get_service_instance( $type );
			$result = $instance->post( $social_post, $settings );
	
			// Store the ID returned by the service posted to.
			update_post_meta( $post_id, 'sclegn_posted_id', $result['posted_id'] );
	
			// Update post
			$postDate = new DateTime( $result['post_date'] );
			$post_title = 'Published on ' . $postDate->format( 'Y/m/d' ) . ' at ' .
				$postDate->format( 'H:i:s' ) . ' for ' . ucfirst( $social_post['service_type'] );
			$post_date = $result['post_date'];
			$update_post = [
				'ID' => $social_post['id'],
				'post_title' => $post_title,
				//'post_status' => 'publish',
				'post_date' => $post_date,
				'post_date_gmt' => get_gmt_from_date( $post_date ),
			];
			$updated_post_id = wp_update_post( $update_post, true );
			if ( is_wp_error( $updated_post_id ) ) {
				throw new Error( implode( ',', $updated_post_id->get_error_messages() ) );
			}
		} catch ( \Exception $e ) {
			// Log the error message.
			error_log( "The social post ID: {$post_id} failed to post. Error: {$e->getMessage()}" );
			// Delete the social post associated with this ID.
			wp_delete_post( $post_id, true ); // 'true' to force delete
			// Exit the function gracefully.
			return;
		}
	}

	protected function get_day( $schedule_post_date )
	{
		$day_ISO8601 = $schedule_post_date->format( 'N' );
		return $this->day_ISO8601_numeric_representation[$day_ISO8601];
	}

	protected function is_time_slot_available( $account_id, $schedule_post_date )
	{
		$args = [
			'post_type' => $this->social_post_type,
			'numberposts' => 1,
			'post_status' => ['draft', 'future'],
			'meta_key' => 'sclegn_account_id',
			'meta_value' => $account_id,
			'date_query' => [
				[
					'year'  => $schedule_post_date->format( 'Y' ),
					'month' => $schedule_post_date->format( 'm' ),
					'day'   => $schedule_post_date->format( 'd' ),
					'hour'  => $schedule_post_date->format( 'H' ),
					'minute' => $schedule_post_date->format( 'i' ),
				],
			],
		];
		$posts = get_posts( $args );
		return empty( $posts );
	}

	protected function is_time_future( $post_date )
	{
		$now = new DateTime( current_time( 'Y-m-d H:i:s' ) );
		return $post_date >= $now;
	}

	protected function attempt_creating_auto_schedule_post( 
		$times,
		$post_date,
		$account,
		$post_content,
		$post_thumbnail_ids,
		$post_id
	 ) {
		foreach ( $times as $time ) {
			$post_date->setTime( $time['hours'], $time['minutes'] );
			if ( $this->is_time_future( $post_date ) && $this->is_time_slot_available( $account['accountId'], $post_date ) ) {

				$post_data = [
					'date'          => $post_date,
					'content'       => $post_content,
					'status'        => 'future',
					'thumbnail_ids' => $post_thumbnail_ids,
					'service_id'    => $account['serviceId'],
					'account_id'    => $account['accountId'],
					'account_type'  => $account['type'],
				];
				$post_data = apply_filters( 'sclegn_post_data', $post_data, $post_id );

				return $this->create_social_post( 
					$post_data['date'],
					$post_data['content'],
					$post_data['status'],
					$post_data['thumbnail_ids'],
					$post_data['service_id'],
					$post_data['account_id'],
					$post_data['account_type']
				 );
			}
		}
		return false;
	}

	public function auto_schedule( $post )
	{
		$defaults = $this->get_option( 'defaults', [] );
		$auto_schedule_posts = $this->get_option( 'auto_schedule_posts', null );
		if ( empty( $auto_schedule_posts ) ) {
			return;
		}
		$this->create_social_post_by_post( $post, $auto_schedule_posts, $defaults );
	}

	public function revive_as_social_post( $post_id, $account_id, $text_link )
	{
		$all_defaults = $this->get_option( 'defaults', [] );
		$defaults = [];
		foreach ( $all_defaults as $setting ) {
			if ( $setting['accountId'] === $account_id ) {
				$defaults[] = $setting;
				break;
			}
		}
		if ( !count( $defaults ) ) {
			throw new Exception( 'The Default data of the account does not exist. Please check the its Default setting is completed.' );
		}

		$post = get_post( $post_id );
		$auto_schedule_posts = $this->get_option( 'auto_schedule_posts', null );
		$this->create_social_post_by_post( $post, $auto_schedule_posts, $defaults, $text_link );
	}

	function can_access_public_api( $action, $request )
	{
		$logged_in = is_user_logged_in(  );
		return apply_filters( 'sclegn_allow_public_api', $logged_in, $action, $request );
	}

	protected function create_social_post_by_post( $post, $auto_schedule_posts, $defaults, $text_link = null )
	{
		// Check if a post related to this post already exists.
		$args = [
			'post_type'   => $this->social_post_type,
			'numberposts' => 1,
			'post_status' => ['draft', 'future'],
			'meta_key'    => 'sclegn_related_postId',
			'meta_value'  => $post->ID,
		];
		
		$posts = get_posts( $args );
		if ( ! empty( $posts ) ) {
			error_log( 'Post already exists for post ID: ' . $post->ID );
			return;
		}

		// Use the template and replace the variables.
		$template = $this->get_option( 'template',  $this->default_template );
		if ( empty( $template ) ) {
			$template = $this->default_template;
		}

		$schedule_post_content   = $this->replace_template_variables( $template, $post );
		$thumbnail_id            = get_post_thumbnail_id( $post->ID );
		error_log( 'Creating social with thumbnail ID: ' . $thumbnail_id );
		$schedule_post_thumbnail_ids = $thumbnail_id ? [$thumbnail_id] : null;

		$schedule_post_date = new DateTime( $post->post_date );
		if ( $auto_schedule_posts ) {

			if ( $auto_schedule_posts == 1 ) {
				$schedule_post_date->add( new DateInterval( 'PT1M' ) );
			} else {
				$schedule_post_date->add( new DateInterval( 'PT' . $auto_schedule_posts . 'S' ) );
			}

		}
		// If the post_date was in the past, set the date to now.
		$today = new DateTime( current_time( 'Y-m-d H:i:s' ) );
		if ( $schedule_post_date < $today ) {
			$schedule_post_date = clone $today; // Clone to avoid modifying $today
			if ( $auto_schedule_posts ) {
				$schedule_post_date->add( new DateInterval( 'PT' . $auto_schedule_posts . 'S' ) );
			}
		}
		$schedule_post_day = $this->get_day( $schedule_post_date );

		$accounts = [];
		foreach ( $this->get_accounts(  ) as $account ) {

			if ( empty( $schedule_post_thumbnail_ids ) && ( $account['type'] === 'instagram' || $account['type'] === 'pinterest' ) ) {
				error_log( 'Instagram and Pinterest requires a thumbnail for the post. Skipping account ID: ' . $account['accountId'] );
				continue;
			}

			$accounts[$account['accountId']] = [
				'accountId' => $account['accountId'],
				'serviceId' => $account['serviceId'],
				'type'      => $account['type'],
			];
		}

		$interval = new DateInterval( 'P1D' );

		// If $defaults is empty, schedule the post for today.
		if ( empty( $defaults ) ) {
			foreach ( $accounts as $account_id => $account ) {
				// Set default post time to now + 1 minute.
				$schedule_post_date = new DateTime( current_time( 'Y-m-d H:i:s' ) );
        		$schedule_post_date->add(new DateInterval('PT1M'));
				
				$schedule_post_day = $this->get_day( $schedule_post_date );

				$hours   = ( int ) $schedule_post_date->format( 'H' );
				$minutes = ( int ) $schedule_post_date->format( 'i' );

				$setting_post_times = [
					$schedule_post_day => [
						'times' => [
							[
								'hours'   => $hours,
								'minutes' => $minutes,
							],
						],
					],
				];

				while ( true ) {
					if ( isset( $setting_post_times[$schedule_post_day] ) ) {
						$id = $this->attempt_creating_auto_schedule_post( 
							$setting_post_times[$schedule_post_day]['times'],
							$schedule_post_date,
							$account,
							$schedule_post_content,
							$schedule_post_thumbnail_ids,
							$post->ID
						 );

						if ( !empty( $id ) ) {
							update_post_meta( $id, 'sclegn_related_postId', $post->ID );
							error_log( 'Post scheduled successfully with ID: ' . $id );
							if ( $auto_schedule_posts == 1 ) {
								error_log( 'Immidiate schedule with no Defaults, publishing now Post ID: ' . $id );
								$this->post( $id );
							}
							break;
						} else {
							error_log( 'Failed to schedule post for account ID: ' . $account_id );
						}
					} else {
						error_log( 'No valid post times for day: ' . $schedule_post_day );
					}

					$schedule_post_date->add( $interval );
					$schedule_post_day = $this->get_day( $schedule_post_date );
				}
			}
		} else {
			foreach ( $defaults as $setting ) {
				$account_id = $setting['accountId'];

				if ( ! isset( $setting['postTime'] ) ) {
					error_log( 'No post time settings for account ID: ' . $account_id );
					continue;
				}

				// Retrieve the valid post time settings.
				$setting_post_times = [];
				foreach ( $setting['postTime'] as $day_key => $list ) {
					if ( count( $list['times'] ) ) {
						$setting_post_times[$day_key] = $list;
					}
				}

				if ( empty( $setting_post_times ) ) {
					error_log( 'No valid post times for account ID: ' . $account_id );
					continue;
				}

				while ( true ) {
					if ( isset( $setting_post_times[$schedule_post_day] ) ) {
						$id = $this->attempt_creating_auto_schedule_post( 
							$setting_post_times[$schedule_post_day]['times'],
							$schedule_post_date,
							$accounts[$account_id],
							$schedule_post_content,
							$schedule_post_thumbnail_ids,
							$post->ID
						 );

						if ( ! empty( $id ) ) {
							update_post_meta( $id, 'sclegn_related_postId', $post->ID );
							error_log( 'Post scheduled successfully with ID: ' . $id );
							break;
						} else {
							error_log( 'Failed to schedule post for account ID: ' . $account_id );
						}
					} else {
						error_log( 'No valid post times for day: ' . $schedule_post_day );
					}

					$schedule_post_date->add( $interval );
					$schedule_post_day = $this->get_day( $schedule_post_date );
				}
			}
		}
	}

	public function post_stats( $post_id )
	{
		$transient = "sclegn_stats_{$post_id}";
		$stats = get_transient( $transient );
		if ( $stats ) {
			return $stats;
		}

		$service_type = get_post_meta( $post_id, 'sclegn_service_type', true );
		$service_id = get_post_meta( $post_id, 'sclegn_service_id', true );
		$posted_id = get_post_meta( $post_id, 'sclegn_posted_id', true );
		$account_id = get_post_meta( $post_id, 'sclegn_account_id', true );
		$settings = $this->get_service_settings( $service_id );

		// The service type of the social post is the priority
		// when the difference between the social post and settings.
		$type = $service_type !== $settings['type'] ? $service_type : $settings['type'];
		$instance = $this->get_service_instance( $type );

		try {
			$stats = $instance->stats( $posted_id, $settings, $account_id );
			set_transient( $transient, $stats, DAY_IN_SECONDS );
			return $stats;
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	public function init(  )
	{
		register_post_type( 
			'social_post',
			[
				'labels' => [
					'name'          => __( 'Social Posts', 'social_engine' ),
					'singular_name' => __( 'Social Post', 'social_engine' ),
				],
				'supports' => [
					'editor',
					'thumbnail',
					'custom-fields',
				],
				'public' => false,
				'show_in_rest' => false
			]
		 );
	}

	public function create_social_post( $date, $content, $status, $thumbnail_ids, $service_id, $account_id, $service_type, $post_link = null )
	{
		$post_date = $date->format( 'Y-m-d H:i:s' );

		$post = [
			'post_title' => 'Scheduled on ' . $date->format( 'Y/m/d' ) . ' at ' . $date->format( 'H:i:s' ) . ' for ' . ucfirst( $service_type ),
			'post_content' => $content,
			'post_date' => $post_date,
			'post_date_gmt' => get_gmt_from_date( $post_date ),
			'post_status' => $status,
			'post_type' => $this->social_post_type,
			'comment_status' => 'closed',
			'ping_status' => 'closed'
		];

		$post_id = wp_insert_post( $post, true );
		if ( is_wp_error( $post_id ) ) {
			throw new Error( implode( ',', $post_id->get_error_messages(  ) ) );
		}

		// Add the thumbnails
		if ( !empty( $thumbnail_ids ) && count( $thumbnail_ids ) ) {
			$formatted_thumbnail_ids = [];
			foreach ( $thumbnail_ids as $thumbnail_id ) {
				$formatted_thumbnail_ids[] = ( string ) $thumbnail_id;
				$this->attach_image_to_social_post( $thumbnail_id, $post_id );
			}
			update_post_meta( $post_id, 'sclegn_thumbnails', json_encode( $formatted_thumbnail_ids ) );
		}

		// Update the metadata
		update_post_meta( $post_id, 'sclegn_service_id', $service_id );
		update_post_meta( $post_id, 'sclegn_account_id', $account_id );
		update_post_meta( $post_id, 'sclegn_service_type', $service_type );
		update_post_meta( $post_id, 'sclegn_post_link', $post_link );

		return $post_id;
	}

	public function update_social_post( $id, $date, $content, $status, $thumbnail_ids, $service_id, $account_id, $service_type )
	{
		$post_date = $date->format( 'Y-m-d H:i:s' );
		$post = [
			'ID' => $id,
			'post_title' => 'Scheduled on ' . $date->format( 'Y/m/d' ) . ' at ' . $date->format( 'H:i:s' ) . ' for '
				. ucfirst( $service_type ),
			'post_date_gmt' => get_gmt_from_date( $post_date ),
			'post_type' => $this->social_post_type,
			'comment_status' => 'closed',
			'ping_status' => 'closed'
		];

		if ( !empty( $content ) ) {
			$post['post_content'] = $content;
		}
		if ( !empty( $post_date ) ) {
			$post['post_date'] = $post_date;
		}
		if ( !empty( $status ) ) {
			$post['post_status'] = $status;
		}

		$post_id = wp_update_post( $post, true );
		if ( is_wp_error( $post_id ) ) {
			throw new Error( implode( ',', $post_id->get_error_messages(  ) ) );
		}

		// Update the thumbnails
		$current_thumbnail_ids = json_decode( $this->get_post_meta_with_default_value( $post_id, 'sclegn_thumbnails', true, '[]' ) );
		$removed_thumbnail_ids = array_diff( $current_thumbnail_ids, $thumbnail_ids );
		foreach ( $removed_thumbnail_ids as $thumbnail_id ) {
			$this->unattach_image_from_social_post( $thumbnail_id, $post_id );
		}
		if ( count( $thumbnail_ids ) ) {
			$formatted_thumbnail_ids = [];
			foreach ( $thumbnail_ids as $thumbnail_id ) {
				$formatted_thumbnail_ids[] = ( string ) $thumbnail_id;
				$this->attach_image_to_social_post( $thumbnail_id, $post_id );
			}
			update_post_meta( $post_id, 'sclegn_thumbnails', json_encode( $formatted_thumbnail_ids ) );
		} else {
			delete_post_meta( $post_id, 'sclegn_thumbnails' );
		}

		// Update the metadata
		update_post_meta( $post_id, 'sclegn_service_id', $service_id );
		update_post_meta( $post_id, 'sclegn_account_id', $account_id );
		update_post_meta( $post_id, 'sclegn_service_type', $service_type );
	}

	public function delete_social_posts( $ids )
	{
		foreach ( $ids as $id ) {
			$thumbnail_ids = json_decode( $this->get_post_meta_with_default_value( $id, 'sclegn_thumbnails', true, '[]' ) );
			$result = wp_delete_post( $id );
			if ( !$result ) {
				throw new Error( 'Failed to delete the social post.' );
			}
			foreach ( $thumbnail_ids as $thumbnail_id ) {
				$this->unattach_image_from_social_post( $thumbnail_id, $id );
			}
		}
	}

	public function get_social_post_type(  )
	{
		return $this->social_post_type;
	}

	public function get_allowed_thumbnail_mimes(  )
	{
		return $this->allowed_thumbnail_mimes;
	}

	public function get_recent_photos( $limit = 3, $search = null, $except_post_ids = [] )
	{
		global $wpdb;
		$where_search_clause = $search ? $wpdb->prepare( "AND p.post_content LIKE %s", '%' . $search . '%' ) : '';

		$where_except_post_ids_clause = '';
		if ( count( $except_post_ids ) ) {
			$post_ids_placeholders = implode( ', ', array_fill( 0, count( $except_post_ids ), '%s' ) );
			$where_except_post_ids_clause = $wpdb->prepare( "AND p.ID NOT IN (  " . $post_ids_placeholders . "  )", $except_post_ids );
		}

		return $wpdb->get_results( 
			$wpdb->prepare( 
				"SELECT p.ID, pm1.meta_value 
				FROM $wpdb->posts p 
				INNER JOIN $wpdb->postmeta pm1 ON pm1.post_id = p.ID AND pm1.meta_key = 'sclegn_thumbnails' 
				WHERE p.post_type='social_post' 
				$where_search_clause 
				$where_except_post_ids_clause 
				ORDER BY p.post_modified DESC 
				LIMIT %d, %d",
				0,
				$limit
			 ),
			ARRAY_A
		 );
	}

	function get_published_post_ids( $post_type )
	{
		global $wpdb;
		$post_status = 'publish';
		return $wpdb->get_col( 
			$wpdb->prepare( 
				"SELECT p.ID
				FROM $wpdb->posts p
				INNER JOIN $wpdb->postmeta pm1 ON pm1.post_id = p.ID AND pm1.meta_key = '_thumbnail_id'
				WHERE p.post_type=%s
				AND p.post_status=%s
				AND p.post_excerpt <> ''",
				$post_type,
				$post_status
			 )
		 );
	}

	function get_post_types(  )
	{
		global $wpdb;
		return $wpdb->get_col( "SELECT DISTINCT post_type FROM $wpdb->posts" );
	}

	function make_post_type_list( $post_types )
	{
		$list = [];
		foreach ( $post_types as $post_type ) {
			if ( in_array( $post_type, ['revision', 'social_post', 'wp_global_styles', 'attachment'] ) ) {
				continue;
			}
			$pt_object = get_post_type_object( $post_type );
			if ( empty( $pt_object ) ) {
				continue;
			}
			if ( property_exists( $pt_object, 'label' ) ) {
				$label = $pt_object->label;
				if ( !empty( $label ) ) {
					$list[$post_type] = $label;
				}
			}
		}
		return $list;
	}

	function attach_image_to_social_post( $image_id, $social_post_id )
	{
		$social_post_ids = $this->get_post_meta_with_default_value( $image_id, 'sclegn_posts', true, [] );
		if ( in_array( $social_post_id, $social_post_ids ) )
			return;

		$new_social_post_ids = [...$social_post_ids, $social_post_id];
		update_post_meta( $image_id, 'sclegn_posts', $new_social_post_ids );
	}

	function unattach_image_from_social_post( $image_id, $social_post_id )
	{
		$social_post_ids = $this->get_post_meta_with_default_value( $image_id, 'sclegn_posts', true, [] );
		$new_social_post_ids = array_filter( $social_post_ids, fn( $value ) => $value !== $social_post_id );
		if ( empty( $new_social_post_ids ) ) {
			delete_post_meta( $image_id, 'sclegn_posts' );
			return;
		}
		update_post_meta( $image_id, 'sclegn_posts', $new_social_post_ids );
	}

	function register_sclegn_posts_post_meta(  )
	{
		global $wpdb;
		$post_ids = $wpdb->get_col( 
			$wpdb->prepare( 
				"SELECT p.ID
				FROM $wpdb->posts p
				INNER JOIN $wpdb->postmeta pm1 ON pm1.post_id = p.ID AND pm1.meta_key = 'sclegn_thumbnails'
				WHERE p.post_type = %s
				AND p.post_status IN (  'draft', 'publish', 'future'  )",
				$this->social_post_type
			 )
		 );
		foreach ( $post_ids as $post_id ) {
			$thumbnail_ids = json_decode( $this->get_post_meta_with_default_value( $post_id, 'sclegn_thumbnails', true, '[]' ) );
			if ( count( $thumbnail_ids ) === 0 ) {
				continue;
			}
			foreach ( $thumbnail_ids as $thumbnail_id ) {
				$this->attach_image_to_social_post( $thumbnail_id, $post_id );
			}
		}
	}

	function get_post_meta_with_default_value( $post_id, $meta_key, $single, $default )
	{
		$meta_value = get_post_meta( $post_id, $meta_key, $single );
		return !!$meta_value ? $meta_value : $default;
	}

	/**
	 *
	 * Roles & Access Rights
	 *
	 */
	public function can_access_settings(  )
	{
		$manage_options = current_user_can( 'manage_options' );
		return apply_filters( 'sclegn_allow_setup', $manage_options );
	}

	public function can_access_features(  )
	{
		$editor = current_user_can( 'administrator' ) || current_user_can( 'editor' ) || current_user_can( 'author' );
		return apply_filters( 'sclegn_allow_usage', $editor );
	}

	public function can_access_moderation_features(  )
	{
		$moderator = current_user_can( 'administrator' ) || current_user_can( 'editor' );
		return apply_filters( 'sclegn_allow_moderation', $moderator );
	}

	#region Options

	static function get_plugin_option_name(  )
	{
		return self::$plugin_option_name;
	}

	function list_options(  )
	{
		return array( 
			'services' => [],
			'defaults' => [],
			'option_checkbox' => false,
			'option_text' => 'Default',
			'locked_dates' => [],
			'draft_enabled' => false,
			'public_api' => false,
			'api_token'	=> null,
			'days_per_page' => 'week',
			'default_service' => null,
			'ai_suggestions' => false,
			'auto_schedule_posts' => 0,
			'hashtags_groups' => [],
			'migrated_sclegn_posts_meta' => false,
			'template' => '',
		 );
	}

	function get_option( $option, $default )
	{
		$options = $this->get_all_options(  );
		return $options[$option] ?? $default;
	}

	function get_all_options(  )
	{
		$options = get_option( $this->option_name, null );
		$options = $this->check_options( $options );

		$modules = $this->get_modules(  );
		$modules_types = array_map( function ( $service ) {
			return $service['type'];
		}, $modules );
		$current_options = array_merge( $options, array( 
			'modules' => $modules_types,
			'accounts' => $this->get_accounts( $options['services'] ),
		 ) );
		// Remove past dates from sclegn_locked_dates option.
		$current_options['locked_dates'] = $this->remove_past_dates( $current_options['locked_dates'] );

		return $current_options;
	}

	function remove_past_dates( $dates )
	{
		if ( !count( $dates ) ) {
			return $dates;
		}
		$today = new DateTime( 'today' );
		$newDates = [];
		foreach ( $dates as $date ) {
			$target = new DateTime( $date );
			if ( $target < $today ) {
				continue;
			}
			$newDates[] = $date;
		}
		return $newDates;
	}

	// Upgrade from the old way of storing options to the new way.
	function check_options( $options = [] )
	{
		$plugin_options = $this->list_options(  );
		$options = empty( $options ) ? [] : $options;
		$hasChanges = false;
		foreach ( $plugin_options as $option => $default ) {
			// The option already exists
			if ( isset( $options[$option] ) ) {
				continue;
			}
			// The option does not exist, so we need to add it.
			// Let's use the old value if any, or the default value.
			$options[$option] = get_option( 'sclegn_' . $option, $default );
			delete_option( 'sclegn_' . $option );
			$hasChanges = true;
		}
		if ( !$options['migrated_sclegn_posts_meta'] ) {
			$this->register_sclegn_posts_post_meta(  );
			$options['migrated_sclegn_posts_meta'] = true;
			$hasChanges = true;
		}
		if ( $hasChanges ) {
			update_option( $this->option_name, $options );
		}
		return $options;
	}

	function update_options( $options )
	{
		list( $success, $message, $options ) = $this->sanitize_options_before_update( $options );
		if ( !$success ) {
			return [$success, $message, $options];
		}
		if ( !update_option( $this->option_name, $options, false ) ) {
			return [false, "Could not update options.", $options];
		}
		$options = $this->sanitize_options(  );
		return [true, $message, $options];
	}

	function sanitize_options_before_update( $options )
	{
		// services
		foreach ( $options['services'] as &$service ) {
			if ( empty( $service['id'] ) ) {
				$service['id'] = uniqid(  );

				if ( $service['type'] === 'linkedin' ) {
					$service['redirect_url'] = get_admin_url( null, 'admin.php?page=sclegn_settings&nekoTab=LinkedIn&sclegn_settings_linkedin=' . $service['id'], 'admin' );
				}
				if ( $service['type'] === 'pinterest' ) {
					$service['redirect_uri'] = get_admin_url( null, '', 'admin' );
					$service['state'] = uniqid(  );
				}
			}
			if ( empty( $service['creation'] ) ) {
				$service['creation'] = time(  ) * 1000;
			}
		}

		// locked_dates
		$new_locked_dates = $this->remove_past_dates( $options['locked_dates'] );
		sort( $new_locked_dates );
		$options['locked_dates'] = $new_locked_dates;

		// draft_enabled
		if ( $options['draft_enabled'] === false && $this->exists_draft_posts(  ) ) {
			return [
				false,
				'Please either delete all the drafts or validate all of them before turning off this option.',
				$options,
			];
		}

		return [true, 'OK', $options];
	}

	// Validate and keep the options clean and logical.
	function sanitize_options(  )
	{
		$options = $this->get_all_options(  );
		$option_checkbox = $options['option_checkbox'];
		$option_text = $options['option_text'];
		$hasChanges = false;
		if ( $option_checkbox === '' ) {
			$options['option_checkbox'] = false;
			$hasChanges = true;
		}
		if ( $option_text === '' ) {
			$options['option_text'] = 'Default';
			$hasChanges = true;
		}

		if ( $hasChanges ) {
			update_option( $this->option_name, $options, false );
		}
		return $options;
	}

	function exists_draft_posts(  )
	{
		$args = [
			'post_type' => $this->get_social_post_type(  ),
			'post_status' => ['draft'],
		];
		$posts = get_posts( $args );
		return count( $posts ) > 0;
	}

	# endregion
}
