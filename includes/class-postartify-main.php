<?php

class Postartify_Main {

	private $default_style;

	public function __construct() {
		$this->default_style = get_option( 'postartify_default_style', 'minimal' );
	}

	public function register_settings() {
		register_setting( 'postartify_settings', 'postartify_default_style' );
		register_setting( 'postartify_settings', 'postartify_auto_generate' );
		register_setting( 'postartify_settings', 'postartify_image_width' );
		register_setting( 'postartify_settings', 'postartify_image_height' );
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, 'ai-image' ) !== false || $hook === 'post.php' || $hook === 'post-new.php' ) {
			wp_enqueue_style( 'postartify-admin-css', plugin_dir_url( __DIR__ ) . 'admin/css/admin.css', array(), '1.0.0' );
			wp_enqueue_script( 'postartify-admin-js', plugin_dir_url( __DIR__ ) . 'admin/js/admin.js', array( 'jquery' ), '1.0.0', true );

			wp_localize_script(
				'postartify-admin-js',
				'postartifyData',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'postartify_nonce' ),
					'postId'  => get_the_ID(),
				)
			);
		}
	}

	public function add_meta_boxes() {
		add_meta_box(
			'postartify_generator',
			'AI Image Generator',
			array( $this, 'render_meta_box' ),
			array( 'post', 'page' ),
			'side',
			'high'
		);
	}

	/**
	 * Render meta box in post editor
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'postartify_meta_box', 'postartify_meta_box_nonce' );
		$has_thumbnail = has_post_thumbnail( $post->ID );
		?>
		<div class="postartify-meta-box">
			<div class="postartify-section">
				<h4>Featured Image</h4>
				<?php if ( $has_thumbnail ) : ?>
					<p class="postartify-status">✓ Featured image exists</p>
				<?php else : ?>
					<p class="postartify-status">⚠ No featured image</p>
				<?php endif; ?>
				<button type="button" class="button button-primary postartify-generate-featured" data-post-id="<?php echo $post->ID; ?>">
					<?php echo $has_thumbnail ? 'Regenerate' : 'Generate'; ?> Featured Image
				</button>
			</div>
			
			<div class="postartify-loading" style="display:none;">
				<span class="spinner is-active"></span>
				<p>Generating image...</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Auto-generate featured image on post save
	 */
	public function auto_generate_featured( $post_id, $post, $update ) {
		// Check if auto-generate is enabled
		if ( ! get_option( 'postartify_auto_generate' ) ) {
			return;
		}

		// Skip if post already has thumbnail
		if ( has_post_thumbnail( $post_id ) ) {
			return;
		}

		// Skip autosaves and revisions
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only for published posts
		if ( $post->post_status !== 'publish' ) {
			return;
		}

		// Generate image
		$this->generate_featured_image( $post_id );
	}

	/**
	 * AJAX: Generate featured image
	 */
	public function ajax_generate_featured() {
		check_ajax_referer( 'postartify_nonce', 'nonce' );

		$post_id = intval( $_POST['post_id'] );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$result = $this->generate_featured_image( $post_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success(
			array(
				'attachment_id' => $result,
				'image_url'     => wp_get_attachment_url( $result ),
			)
		);
	}

	/**
	 * AJAX: Generate inline image
	 */
	public function ajax_generate_inline() {
		check_ajax_referer( 'postartify_nonce', 'nonce' );

		$prompt  = sanitize_text_field( $_POST['prompt'] );
		$post_id = intval( $_POST['post_id'] );

		$result = $this->generate_with_pollinations( $prompt, 'inline-' . $post_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success(
			array(
				'attachment_id' => $result,
				'image_url'     => wp_get_attachment_url( $result ),
			)
		);
	}

	/**
	 * AJAX: Batch generate images
	 */
	public function ajax_batch_generate() {
		check_ajax_referer( 'postartify_nonce', 'nonce' );

		if ( ! isset( $_POST['post_ids'] ) ) {
			wp_send_json_error( 'No post IDs provided' );
		}

		$post_ids = json_decode( stripslashes( $_POST['post_ids'] ) );

		if ( ! is_array( $post_ids ) ) {
			wp_send_json_error( 'Invalid post IDs format' );
		}

		$results = array();
		foreach ( $post_ids as $post_id ) {
			$result              = $this->generate_featured_image( $post_id );
			$results[ $post_id ] = ! is_wp_error( $result );

			if ( ! is_wp_error( $result ) ) {
				sleep( 2 ); // Rate limiting
			}
		}

		wp_send_json_success( $results );
	}

	/**
	 * Generate featured image for a post
	 */
	private function generate_featured_image( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error( 'invalid_post', 'Post not found' );
		}

		// Create prompt from title and excerpt
		$title   = $post->post_title;
		$excerpt = $post->post_excerpt ? $post->post_excerpt : wp_trim_words( $post->post_content, 30 );

		$prompt = $this->create_prompt( $title, $excerpt );

		// Generate image
		$attachment_id = $this->generate_with_pollinations( $prompt, 'featured-' . $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		set_post_thumbnail( $post_id, $attachment_id );

		return $attachment_id;
	}

	/**
	 * Create AI prompt from title and excerpt
	 */
	private function create_prompt( $title, $excerpt ) {
		$style = $this->get_style_description( $this->default_style );

		$prompt  = "Create a {$style} image for a blog post titled '{$title}'. ";
		$prompt .= "The post is about: {$excerpt}. ";
		$prompt .= 'Make it visually appealing, professional, and suitable for a blog featured image. ';
		$prompt .= 'No text or words in the image.';

		return $prompt;
	}

	/**
	 * Get style description for prompt
	 */
	private function get_style_description( $style ) {
		$styles = array(
			'minimal'        => 'clean, minimal, simple design with limited colors',
			'flat'           => 'modern flat design with bold colors',
			'watercolor'     => 'soft watercolor painting style',
			'photorealistic' => 'photorealistic, high-quality photograph',
			'neon'           => 'vibrant neon colors with glowing effects',
			'illustration'   => 'hand-drawn illustration style',
		);

		return isset( $styles[ $style ] ) ? $styles[ $style ] : $styles['minimal'];
	}

	private function generate_with_pollinations( $prompt, $prefix ) {
		$width  = get_option( 'postartify_image_width', 1024 );
		$height = get_option( 'postartify_image_height', 1024 );

		$query = urlencode( $prompt . ' | ' . $width . 'x' . $height );

		$image_url = 'https://image.pollinations.ai/prompt/' . $query;

		// Save to WordPress Media Library
		return $this->save_to_media_library( $image_url, $prompt, $prefix );
	}

	/**
	 * Save image to WordPress media library
	 */
	private function save_to_media_library( $image_url, $description, $prefix = '' ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $image_url );

		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$file_array = array(
			'name'     => $prefix . '-' . time() . '.png',
			'tmp_name' => $tmp,
		);

		$id = media_handle_sideload( $file_array, 0, $description );

		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			return $id;
		}

		// Add metadata
		update_post_meta( $id, '_postartify_generated', 1 );
		update_post_meta( $id, '_postartify_prompt', $description );
		update_post_meta( $id, '_postartify_style', $this->default_style );
		update_post_meta( $id, '_postartify_date', current_time( 'mysql' ) );

		return $id;
	}
}
