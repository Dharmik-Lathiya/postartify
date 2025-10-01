jQuery( document ).ready(
	function ($) {

		// Generate Featured Image
		$( '.postartify-generate-featured' ).on(
			'click',
			function () {
				const button  = $( this );
				const postId  = button.data( 'post-id' );
				const metaBox = button.closest( '.postartify-meta-box' );

				button.prop( 'disabled', true );
				metaBox.find( '.postartify-loading' ).show();

				$.ajax(
					{
						url: postartifyData.ajaxUrl,
						type: 'POST',
						data: {
							action: 'postartify_generate_featured',
							nonce: postartifyData.nonce,
							post_id: postId
						},
						success: function (response) {
							if (response.success) {
								// Update featured image in WordPress
								$( '#postimagediv img' ).attr( 'src', response.data.image_url );
								$( '#set-post-thumbnail img' ).attr( 'src', response.data.image_url );

								// Show success message
								showNotice( 'Featured image generated successfully!', 'success' );

								// Update button text
								button.text( 'Regenerate Featured Image' );
								metaBox.find( '.postartify-status' ).html( '✓ Featured image exists' );

								// Reload page to show thumbnail
								setTimeout(
									function () {
										location.reload();
									},
									1500
								);
							} else {
								showNotice( 'Error: ' + response.data, 'error' );
							}
						},
						error: function () {
							showNotice( 'Failed to generate image. Please try again.', 'error' );
						},
						complete: function () {
							button.prop( 'disabled', false );
							metaBox.find( '.postartify-loading' ).hide();
						}
					}
				);
			}
		);

		// Generate Image inline
		$( document ).on(
			'click',
			'.postartify-generate-inline',
			function () {
				const button = $( this );
				const prompt = button.data( 'prompt' );
				const postId = postartifyData.postId;

				button.prop( 'disabled', true ).text( 'Generating...' );

				$.ajax(
					{
						url: postartifyData.ajaxUrl,
						type: 'POST',
						data: {
							action: 'postartify_generate_inline',
							nonce: postartifyData.nonce,
							prompt: prompt,
							post_id: postId
						},
						success: function (response) {
							if (response.success) {
								const imageHtml = '<div class="postartify-generated-preview">' +
								'<img src="' + response.data.image_url + '" style="max-width: 100%; margin: 10px 0;">' +
								'<p><a href="#" class="postartify-insert-image" data-id="' + response.data.attachment_id + '">Insert into Post</a></p>' +
								'</div>';

								button.parent().append( imageHtml );
								button.text( 'Generated' ).addClass( 'button-primary' );

								showNotice( 'Image generated! Click "Insert into Post" to add it.', 'success' );
							} else {
								showNotice( 'Error: ' + response.data, 'error' );
								button.prop( 'disabled', false ).text( 'Try Again' );
							}
						},
						error: function () {
							showNotice( 'Failed to generate image.', 'error' );
							button.prop( 'disabled', false ).text( 'Try Again' );
						}
					}
				);
			}
		);

		// Insert Generated Image into Post
		$( document ).on(
			'click',
			'.postartify-insert-image',
			function (e) {
				e.preventDefault();
				const attachmentId = $( this ).data( 'id' );

				if (typeof wp !== 'undefined' && wp.media) {
					wp.media.editor.insert( '[gallery ids="' + attachmentId + '"]' );
				} else {

					const imageUrl  = $( this ).closest( '.postartify-generated-preview' ).find( 'img' ).attr( 'src' );
					const imageHtml = '<img src="' + imageUrl + '" alt="AI Generated Image" class="aligncenter">';

					if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
						tinymce.activeEditor.insertContent( imageHtml );
					} else if ($( '#content' ).length) {
						$( '#content' ).val( $( '#content' ).val() + '\n\n' + imageHtml );
					}
				}

				showNotice( 'Image inserted into post!', 'success' );
			}
		);

		// Batch Generation
		$( '.postartify-start-batch' ).on(
			'click',
			function () {
				const button = $( this );
				const count  = button.data( 'count' );

				console.log( button,count );

				if ( ! confirm( 'Generate featured images for ' + count + ' posts? This may take several minutes.' )) {
					return;
				}

				button.prop( 'disabled', true );
				$( '.postartify-batch-progress' ).show();

				const postIds = [];
				$( 'table tbody tr[data-post-id]' ).each(
					function () {
						postIds.push( $( this ).data( 'post-id' ) );
					}
				);

				let processed = 0;
				processBatchPost( 0 );

				function processBatchPost(index) {
					if (index >= postIds.length) {

						$( '.postartify-progress-text' ).text( 'Completed! ' + processed + ' of ' + count + ' images generated.' );
						$( '.postartify-batch-log' ).append( '<p class="success">✓ Batch generation complete!</p>' );
						button.prop( 'disabled', false ).text( 'Generation Complete' );
						return;
					}

					const postId = postIds[index];
					const row    = $( 'tr[data-post-id="' + postId + '"]' );

					row.find( '.postartify-batch-status' ).html( '<span class="spinner is-active"></span> Generating...' );

					$.ajax(
						{
							url: postartifyData.ajaxUrl,
							type: 'POST',
							data: {
								action: 'postartify_generate_featured',
								nonce: postartifyData.nonce,
								post_id: postId
							},
							success: function (response) {
								processed++;

								if (response.success) {
									row.find( '.postartify-batch-status' ).html( '✓ Complete' ).css( 'color', 'green' );
									$( '.postartify-batch-log' ).append( '<p>✓ Generated image for: ' + row.find( 'a' ).text() + '</p>' );
								} else {
									row.find( '.postartify-batch-status' ).html( '✗ Failed' ).css( 'color', 'red' );
									$( '.postartify-batch-log' ).append( '<p class="error">✗ Failed: ' + row.find( 'a' ).text() + '</p>' );
								}

								const progress = (processed / postIds.length) * 100;
								$( '.postartify-progress-fill' ).css( 'width', progress + '%' );
								$( '.postartify-progress-text' ).text( processed + ' of ' + count + ' completed' );

								setTimeout(
									function () {
										processBatchPost( index + 1 );
									},
									3000
								);
							},
							error: function () {
								row.find( '.postartify-batch-status' ).html( '✗ Error' ).css( 'color', 'red' );
								$( '.postartify-batch-log' ).append( '<p class="error">✗ Error: ' + row.find( 'a' ).text() + '</p>' );

								setTimeout(
									function () {
										processBatchPost( index + 1 );
									},
									1000
								);
							}
						}
					);
				}
			}
		);

		function showNotice(message, type) {
			const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
			const notice      = $( '<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>' );

			$( '.wrap h1, .wrap h2' ).first().after( notice );
		}

	}
);