<?php
/* Template Name: Full Page Template */

// Disable formatting filters before outputting the content
remove_filter('the_content', 'wpautop');
remove_filter('the_content', 'wptexturize');
remove_filter('the_content', 'convert_chars');

// Disable shortcode_unautop tags
remove_filter('the_content', 'shortcode_unautop');

// Output the raw content
echo get_post_field('post_content', get_the_ID(), 'raw');