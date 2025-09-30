
<div class="wrap">
            <h1>AI Image Generator Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('aiig_settings'); ?>
                
                <table class="form-table">
                                       
                    <tr>
                        <th scope="row">Default Style</th>
                        <td>
                            <select name="aiig_default_style">
                                <option value="minimal" <?php selected(get_option('aiig_default_style'), 'minimal'); ?>>Minimal</option>
                                <option value="flat" <?php selected(get_option('aiig_default_style'), 'flat'); ?>>Flat Design</option>
                                <option value="watercolor" <?php selected(get_option('aiig_default_style'), 'watercolor'); ?>>Watercolor</option>
                                <option value="photorealistic" <?php selected(get_option('aiig_default_style'), 'photorealistic'); ?>>Photorealistic</option>
                                <option value="neon" <?php selected(get_option('aiig_default_style'), 'neon'); ?>>Neon</option>
                                <option value="illustration" <?php selected(get_option('aiig_default_style'), 'illustration'); ?>>Illustration</option>
                            </select>
                            <p class="description">All generated images will use this style for brand consistency</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Auto-Generate</th>
                        <td>
                            <label>
                                <input type="checkbox" name="aiig_auto_generate" value="1" <?php checked(get_option('aiig_auto_generate'), 1); ?>>
                                Automatically generate featured images for new posts without thumbnails
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Image Dimensions</th>
                        <td>
                            <input type="number" name="aiig_image_width" value="<?php echo esc_attr(get_option('aiig_image_width', 1024)); ?>" style="width: 80px;"> x
                            <input type="number" name="aiig_image_height" value="<?php echo esc_attr(get_option('aiig_image_height', 1024)); ?>" style="width: 80px;"> px
                            <p class="description">Default: 1024x1024. Recommended sizes: 1024x1024, 1792x1024, or 1024x1792</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h2>Style Examples</h2>
            <div class="aiig-style-examples">
                <div class="style-card">
                    <strong>Minimal:</strong> Clean, simple designs with limited colors
                </div>
                <div class="style-card">
                    <strong>Flat Design:</strong> Modern, 2D illustrations with bold colors
                </div>
                <div class="style-card">
                    <strong>Watercolor:</strong> Soft, artistic watercolor paintings
                </div>
                <div class="style-card">
                    <strong>Photorealistic:</strong> Realistic, photograph-like images
                </div>
                <div class="style-card">
                    <strong>Neon:</strong> Vibrant neon colors and glowing effects
                </div>
                <div class="style-card">
                    <strong>Illustration:</strong> Hand-drawn style illustrations
                </div>
            </div>
        </div>
        