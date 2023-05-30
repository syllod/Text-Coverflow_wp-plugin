<?php
/**
 * Plugin Name: Text CoverFlow Plugin
 * Version: 1.0.45
 * Description: A plugin to create a coverflow with Text content
 * Author: Sylvain L
 */


// Register the scripts and styles
function text_coverflow_plugin_scripts() {
    wp_enqueue_script('jquery');

    // Check if Bootstrap should be loaded
    $load_bootstrap = get_option('text_coverflow_load_bootstrap', true);
    if ($load_bootstrap) {
        wp_enqueue_script('bootstrap-bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js', array('jquery'), false, true);
    }

    // Check if Swiper should be loaded
    $load_swiper = get_option('text_coverflow_load_swiper', true);
    if ($load_swiper) {
        wp_enqueue_script('swiper-bundle', 'https://unpkg.com/swiper@6.5.1/swiper-bundle.min.js', array('jquery'), false, true);
    }

    // Add the 'defer' parameter to load the script in defer mode
    wp_script_add_data('text-coverflow-script', 'defer', true);

    wp_enqueue_script('text-coverflow-script', plugin_dir_url(__FILE__) . 'assets/text-coverflow-script.js', array('jquery'), false, true);

    wp_enqueue_style('text-coverflow-style', plugin_dir_url(__FILE__) . 'assets/text-coverflow-style.css', array(), '1.0.45');
}
add_action('wp_enqueue_scripts', 'text_coverflow_plugin_scripts');



// Enqueue admin styles
function text_coverflow_plugin_admin_styles() {
    // Register and enqueue the admin CSS
    wp_enqueue_style('text-coverflow-plugin-admin', plugin_dir_url(__FILE__) . 'assets/text-coverflow-plugin-admin.css', array(), '1.0.45');
}
add_action('admin_enqueue_scripts', 'text_coverflow_plugin_admin_styles');


// Register the plugin settings
function text_coverflow_plugin_register_settings() {
    // Register the checkbox settings
    register_setting('text_coverflow_plugin_options', 'text_coverflow_load_bootstrap');
    register_setting('text_coverflow_plugin_options', 'text_coverflow_load_swiper');
}
add_action('admin_init', 'text_coverflow_plugin_register_settings');


// Add the checkbox fields to the settings page
function text_coverflow_plugin_settings_fields() {
    // Checkbox for Bootstrap
    $load_bootstrap = get_option('text_coverflow_load_bootstrap', true);
    echo '<label for="text_coverflow_load_bootstrap">';
    echo '<input type="checkbox" id="text_coverflow_load_bootstrap" name="text_coverflow_load_bootstrap" value="1" ' . checked($load_bootstrap, true, false) . '>';
    echo ' Load Bootstrap';
    echo '</label><br>';

    // Checkbox for Swiper
    $load_swiper = get_option('text_coverflow_load_swiper', true);
    echo '<label for="text_coverflow_load_swiper">';
    echo '<input type="checkbox" id="text_coverflow_load_swiper" name="text_coverflow_load_swiper" value="1" ' . checked($load_swiper, true, false) . '>';
    echo ' Load Swiper';
    echo '</label>';
}


// Add the settings page to the admin menu
function text_coverflow_plugin_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=coverflow',
        'Coverflow Plugin Settings',
        'Plugin Settings',
        'manage_options',
        'text_coverflow_plugin_settings',
        'text_coverflow_plugin_render_settings_page'
    );
}
add_action('admin_menu', 'text_coverflow_plugin_add_settings_page');


// Render the settings page
function text_coverflow_plugin_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Coverflow Plugin Settings</h1> 
	<p>Deactivate these scripts if you use the shortcode in elementor</p>
        <form method="post" action="options.php">
            <?php
            settings_fields('text_coverflow_plugin_options');
            do_settings_sections('text_coverflow_plugin_options');
            text_coverflow_plugin_settings_fields();
            submit_button();
            ?>
        </form>
    </div>
    <?php
}


// Register the post type
function text_coverflow_plugin_post_type() {
    register_post_type('coverflow', [
        'labels' => [
            'name' => 'Coverflow',
            'singular_name' => 'Coverflow',
        ],
        'public' => true,
        'has_archive' => false,
        'rewrite' => ['slug' => 'coverflow/%coverflow_id%'], // Use '%coverflow_id%' in the "slug" argument
        'show_in_rest' => true,
    ]);
}
add_action('init', 'text_coverflow_plugin_post_type');

// Add the shortcode with the appropriate ID in admin notifications
function text_coverflow_plugin_display_shortcode() {
    global $post;

    // Check if the post is of type "coverflow"
    if ($post->post_type === 'coverflow') {
        $shortcode = '[text_coverflow id="' . $post->ID . '"]';
        $message = 'Use this shortcode to display the coverflow: <code>' . esc_html($shortcode) . '</code>';

        echo '<div class="notice notice-info"><p>' . $message . '</p></div>';
    }
}
add_action('admin_notices', 'text_coverflow_plugin_display_shortcode');

// Modify permalink to include the post ID in the URL
function text_coverflow_plugin_custom_permalink($permalink, $post) {
    if ($post->post_type === 'coverflow') {
        $permalink = str_replace('%coverflow_id%', $post->ID, $permalink);
    }

    return $permalink;
}
add_filter('post_type_link', 'text_coverflow_plugin_custom_permalink', 10, 2);

// Add the metabox
function text_coverflow_plugin_add_metabox() {
    add_meta_box('coverflow_elements', 'Coverflow Elements', 'text_coverflow_plugin_render_metabox', 'coverflow', 'normal', 'high');
}
add_action('add_meta_boxes', 'text_coverflow_plugin_add_metabox');

// Hide content editor for custom post type "coverflow"
function text_coverflow_plugin_hide_post_editor() {
    remove_post_type_support('coverflow', 'editor');
}
add_action('init', 'text_coverflow_plugin_hide_post_editor');


// Render the metabox
function text_coverflow_plugin_render_metabox($post) {
    // Use nonce for verification
    wp_nonce_field(plugin_basename(__FILE__), 'text_coverflow_plugin_nonce');

    // Retrieve current metadata
    $metadata = get_post_meta($post->ID);

    // Initialize the counter
    $counter = 1;

    // Loop to create the fields for each coverflow element
    for ($i = 1; $i <= 6; $i++) {
        // Display the title for the group
        echo '<h3>Element ' . $counter . '</h3>';

        echo '<div id="coverflow_element_' . $i . '" class="coverflow_element">';
        
        // Title field
        $current_title = isset($metadata['coverflow_element_title_' . $i]) ? $metadata['coverflow_element_title_' . $i][0] : '';
        echo '<label for="coverflow_element_title_' . $i . '">Title:</label>';
        echo '<input type="text" id="coverflow_element_title_' . $i . '" name="coverflow_element_title_' . $i . '" value="' . $current_title . '">';
        
        // Description field
        $current_description = isset($metadata['coverflow_element_description_' . $i]) ? $metadata['coverflow_element_description_' . $i][0] : '';
        echo '<label for="coverflow_element_description_' . $i . '">Description:</label>';
        echo '<textarea id="coverflow_element_description_' . $i . '" name="coverflow_element_description_' . $i . '">' . $current_description . '</textarea>';
        
        // Image field
        $current_image = isset($metadata['coverflow_element_image_' . $i]) ? $metadata['coverflow_element_image_' . $i][0] : '';
        echo '<label for="coverflow_element_image_' . $i . '">Image:</label>';
        echo '<input type="text" id="coverflow_element_image_' . $i . '" name="coverflow_element_image_' . $i . '" value="' . $current_image . '">';
        echo '<img id="coverflow_element_image_preview_' . $i . '" src="' . $current_image . '" style="max-width: 100%; display: ' . ($current_image ? 'block' : 'none') . '">';
        
        echo '</div>';

        // Increment the counter
        $counter++;

        // Add the separator
        if ($i < 6) {
            echo '<div class="separator"></div>';
        }
    }
}


// Save the post metadata
function text_coverflow_plugin_save_postdata($post_id) {
    // Verify nonce
    if (!isset($_POST['text_coverflow_plugin_nonce']) || !wp_verify_nonce($_POST['text_coverflow_plugin_nonce'], plugin_basename(__FILE__))) {
        return;
    }

    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if ('coverflow' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    // Loop to save each coverflow element
    for ($i = 1; $i <= 6; $i++) {
        // Title
        if (isset($_POST['coverflow_element_title_' . $i])) {
            update_post_meta($post_id, 'coverflow_element_title_' . $i, sanitize_text_field($_POST['coverflow_element_title_' . $i]));
        }
        
        // Description
        if (isset($_POST['coverflow_element_description_' . $i])) {
            update_post_meta($post_id, 'coverflow_element_description_' . $i, sanitize_text_field($_POST['coverflow_element_description_' . $i]));
        }
        
        // Image
        if (isset($_POST['coverflow_element_image_' . $i])) {
            update_post_meta($post_id, 'coverflow_element_image_' . $i, sanitize_text_field($_POST['coverflow_element_image_' . $i]));
        }
    }
}
add_action('save_post', 'text_coverflow_plugin_save_postdata');


// Shortcode to display the coverflow
function text_coverflow_plugin_shortcode($atts) {
    // Get the post
    $post = get_post($atts['id']);

    // Retrieve current metadata
    $metadata = get_post_meta($post->ID);

    // Start the output
    $output = '<div class="container py-5">';
    $output .= '<div class="row">';
    $output .= '<div class="col-12">';
    $output .= '<div class="swiper-container mySwiper">';
    $output .= '<div class="swiper-wrapper">';

    // Loop to add each coverflow element to the output
    for ($i = 1; $i <= 6; $i++) {
        if (!empty($metadata['coverflow_element_title_' . $i][0]) || !empty($metadata['coverflow_element_description_' . $i][0]) || !empty($metadata['coverflow_element_image_' . $i][0])) {
            $output .= '<div class="swiper-slide">';
            $output .= '<div class="swiper-item">';
	    $output .= '<div class="icons-header-2"><img src="' . $metadata['coverflow_element_image_' . $i][0] . '" alt="' . get_post_meta($post->ID, 'coverflow_element_image_alt_' . $i, true) . '" /></div>';
            $output .= '<h3>' . $metadata['coverflow_element_title_' . $i][0] . '</h3>';
            $output .= $metadata['coverflow_element_description_' . $i][0];
            $output .= '</div>';
            $output .= '</div>';
        }
    }

    // End the output
    $output .= '</div>';
    $output .= '<div class="swiper-pagination"></div>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';

    // Return the output
    return $output;
}
add_shortcode('text_coverflow', 'text_coverflow_plugin_shortcode');


// Output the JavaScript code in the footer
function text_coverflow_plugin_output_footer_scripts() {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var swiper = new Swiper(".mySwiper", {
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                effect: "coverflow",
                loop: true,
                speed: 800,
                autoplay: {
                    delay: 3000,
                },
                centeredSlides: true,
                slidesPerView: "auto",
                coverflowEffect: {
                    rotate: 0,
                    stretch: 140,
                    depth: 300,
                    modifier: 1,
                    slideShadows: false,
                }
            });
            
            document.addEventListener("keydown", function(event) {
                if (event.keyCode === 37) {
                    // FlÃ¨che gauche pressÃ©e
                    swiper.slidePrev();
                } else if (event.keyCode === 39) {
                    // FlÃ¨che droite pressÃ©e
                    swiper.slideNext();
                }
            });
        });
    </script>';
}
add_action('wp_footer', 'text_coverflow_plugin_output_footer_scripts');
