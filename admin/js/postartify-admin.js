jQuery(document).ready(function($) {
    
    // Generate Featured Image
    $('.aiig-generate-featured').on('click', function() {
        const button = $(this);
        const postId = button.data('post-id');
        const metaBox = button.closest('.aiig-meta-box');
        
        button.prop('disabled', true);
        metaBox.find('.aiig-loading').show();
        
        $.ajax({
            url: aiigData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'aiig_generate_featured',
                nonce: aiigData.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    // Update featured image in WordPress
                    $('#postimagediv img').attr('src', response.data.image_url);
                    $('#set-post-thumbnail img').attr('src', response.data.image_url);
                    
                    // Show success message
                    showNotice('Featured image generated successfully!', 'success');
                    
                    // Update button text
                    button.text('Regenerate Featured Image');
                    metaBox.find('.aiig-status').html('✓ Featured image exists');
                    
                    // Reload page to show thumbnail
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotice('Error: ' + response.data, 'error');
                }
            },
            error: function() {
                showNotice('Failed to generate image. Please try again.', 'error');
            },
            complete: function() {
                button.prop('disabled', false);
                metaBox.find('.aiig-loading').hide();
            }
        });
    });
    
    // Analyze Post for Inline Suggestions
    $('.aiig-analyze-post').on('click', function() {
        const button = $(this);
        const postId = button.data('post-id');
        const suggestionsDiv = $('.aiig-suggestions');
        
        button.prop('disabled', true);
        suggestionsDiv.html('<span class="spinner is-active"></span> Analyzing...');
        
        $.ajax({
            url: aiigData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'aiig_analyze_post',
                nonce: aiigData.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '<ul class="aiig-suggestion-list">';
                    
                    response.data.forEach(function(suggestion) {
                        html += '<li>';
                        html += '<strong>' + suggestion.text + '</strong><br>';
                        html += '<button class="button aiig-generate-inline" data-prompt="' + escapeHtml(suggestion.prompt) + '">';
                        html += 'Generate Image Here</button>';
                        html += '</li>';
                    });
                    
                    html += '</ul>';
                    suggestionsDiv.html(html);
                } else {
                    suggestionsDiv.html('<p>No suggestions found. Try adding more headings or content.</p>');
                }
            },
            error: function() {
                suggestionsDiv.html('<p class="error">Failed to analyze post.</p>');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
    
    // Generate Inline Image
    $(document).on('click', '.aiig-generate-inline', function() {
        const button = $(this);
        const prompt = button.data('prompt');
        const postId = aiigData.postId;
        
        button.prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: aiigData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'aiig_generate_inline',
                nonce: aiigData.nonce,
                prompt: prompt,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    // Show image preview
                    const imageHtml = '<div class="aiig-generated-preview">' +
                        '<img src="' + response.data.image_url + '" style="max-width: 100%; margin: 10px 0;">' +
                        '<p><a href="#" class="aiig-insert-image" data-id="' + response.data.attachment_id + '">Insert into Post</a></p>' +
                        '</div>';
                    
                    button.parent().append(imageHtml);
                    button.text('✓ Generated').addClass('button-primary');
                    
                    showNotice('Image generated! Click "Insert into Post" to add it.', 'success');
                } else {
                    showNotice('Error: ' + response.data, 'error');
                    button.prop('disabled', false).text('Try Again');
                }
            },
            error: function() {
                showNotice('Failed to generate image.', 'error');
                button.prop('disabled', false).text('Try Again');
            }
        });
    });
    
    // Insert Generated Image into Post
    $(document).on('click', '.aiig-insert-image', function(e) {
        e.preventDefault();
        const attachmentId = $(this).data('id');
        
        // Use WordPress media functions if available
        if (typeof wp !== 'undefined' && wp.media) {
            wp.media.editor.insert('[gallery ids="' + attachmentId + '"]');
        } else {
            // Fallback: Insert image HTML
            const imageUrl = $(this).closest('.aiig-generated-preview').find('img').attr('src');
            const imageHtml = '<img src="' + imageUrl + '" alt="AI Generated Image" class="aligncenter">';
            
            // Insert into editor
            if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                tinymce.activeEditor.insertContent(imageHtml);
            } else if ($('#content').length) {
                $('#content').val($('#content').val() + '\n\n' + imageHtml);
            }
        }
        
        showNotice('Image inserted into post!', 'success');
    });
    
    // Batch Generation
    $('.aiig-start-batch').on('click', function() {
        const button = $(this);
        const count = button.data('count');

		console.log(button,count);
		
        
        if (!confirm('Generate featured images for ' + count + ' posts? This may take several minutes.')) {
            return;
        }
        
        button.prop('disabled', true);
        $('.aiig-batch-progress').show();
        
        // Get all post IDs
        const postIds = [];
        $('table tbody tr[data-post-id]').each(function() {
            postIds.push($(this).data('post-id'));
        });
        
        // Process posts one by one
        let processed = 0;
        processBatchPost(0);
        
        function processBatchPost(index) {
            if (index >= postIds.length) {
                // All done
                $('.aiig-progress-text').text('Completed! ' + processed + ' of ' + count + ' images generated.');
                $('.aiig-batch-log').append('<p class="success">✓ Batch generation complete!</p>');
                button.prop('disabled', false).text('Generation Complete');
                return;
            }
            
            const postId = postIds[index];
            const row = $('tr[data-post-id="' + postId + '"]');
            
            row.find('.aiig-batch-status').html('<span class="spinner is-active"></span> Generating...');
            
            $.ajax({
                url: aiigData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aiig_generate_featured',
                    nonce: aiigData.nonce,
                    post_id: postId
                },
                success: function(response) {
                    processed++;
                    
                    if (response.success) {
                        row.find('.aiig-batch-status').html('✓ Complete').css('color', 'green');
                        $('.aiig-batch-log').append('<p>✓ Generated image for: ' + row.find('a').text() + '</p>');
                    } else {
                        row.find('.aiig-batch-status').html('✗ Failed').css('color', 'red');
                        $('.aiig-batch-log').append('<p class="error">✗ Failed: ' + row.find('a').text() + '</p>');
                    }
                    
                    // Update progress
                    const progress = (processed / postIds.length) * 100;
                    $('.aiig-progress-fill').css('width', progress + '%');
                    $('.aiig-progress-text').text(processed + ' of ' + count + ' completed');
                    
                    // Process next post after delay (rate limiting)
                    setTimeout(function() {
                        processBatchPost(index + 1);
                    }, 3000);
                },
                error: function() {
                    row.find('.aiig-batch-status').html('✗ Error').css('color', 'red');
                    $('.aiig-batch-log').append('<p class="error">✗ Error: ' + row.find('a').text() + '</p>');
                    
                    // Continue with next post
                    setTimeout(function() {
                        processBatchPost(index + 1);
                    }, 1000);
                }
            });
        }
    });
    
    // Helper Functions
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1, .wrap h2').first().after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});