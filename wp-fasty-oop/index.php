<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no `home.php` file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package _by
 */

get_header();
?>

	<section id="primary">
		<main id="main">

		<?php
		if ( have_posts() ) {

			// Load posts loop.
			while ( have_posts() ) {
				the_post();
				get_the_content();
			}

		} else {

			// If no content, include the "No posts found" template.

		}
		?>

		</main><!-- #main -->
	</section><!-- #primary -->

<?php
get_footer();
