<?php
	$posts_without_thumbnails = get_posts(
		array(
			'post_type'      => 'service',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);

	?>
		<div class="wrap">
			<h1>Batch Generate Featured Images</h1>
			
			<div class="aiig-batch-container">
				<div class="aiig-batch-info">
					<p><strong>Found <?php echo count( $posts_without_thumbnails ); ?> posts without featured images.</strong></p>
					<?php if ( count( $posts_without_thumbnails ) > 0 ) : ?>
						<p>This will generate featured images for all posts that don't have one.</p>
						<button type="button" class="button button-primary aiig-start-batch" data-count="<?php echo count( $posts_without_thumbnails ); ?>">
							Generate All Featured Images
						</button>
					<?php else : ?>
						<p>✓ All posts have featured images!</p>
					<?php endif; ?>
				</div>
				
				<div class="aiig-batch-progress" style="display:none;">
					<h3>Generation Progress</h3>
					<div class="aiig-progress-bar">
						<div class="aiig-progress-fill"></div>
					</div>
					<p class="aiig-progress-text">0 of <?php echo count( $posts_without_thumbnails ); ?> completed</p>
					<div class="aiig-batch-log"></div>
				</div>
			</div>
			
			<hr>
			
			<h2>Posts Without Thumbnails</h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Title</th>
						<th>Date</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $posts_without_thumbnails as $post ) : ?>
						<tr data-post-id="<?php echo $post->ID; ?>">
							<td>
								<a href="<?php echo get_edit_post_link( $post->ID ); ?>">
									<?php echo esc_html( $post->post_title ); ?>
								</a>
							</td>
							<td><?php echo get_the_date( '', $post->ID ); ?></td>
							<td class="aiig-batch-status">Pending</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>