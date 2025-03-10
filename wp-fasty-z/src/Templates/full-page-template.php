<?php
/* Template Name: Full Page Template */

get_header();

// Get the relative path to the static file
$static_file_path = get_post_meta(get_the_ID(), '_wp_fasty_static_file', true);
if ($static_file_path && file_exists(get_template_directory() . $static_file_path)) {
    // Simply include the rendered file
    include(get_template_directory() . $static_file_path);
} else {
    // If the file is not found, display the standard content
    the_content();
}

get_footer();
