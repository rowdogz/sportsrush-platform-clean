	<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_One
 * @since 1.0.0
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header class="entry-header alignwide">


		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

		<div class="entry-footer default-max-width">
		<?php twenty_twenty_one_entry_meta_footer(); ?>
		</div><!-- .entry-footer -->

		<?php twenty_twenty_one_post_thumbnail(); ?>

		

	</header>

	<div class="entry-content">
		<?php
		the_content();

		wp_link_pages(
			array(
				'before'   => '<nav class="page-links" aria-label="' . esc_attr__( 'Page', 'Gymnex' ) . '">',
				'after'    => '</nav>',
				/* translators: %: page number. */
				'pagelink' => esc_html__( 'Page %', 'Gymnex' ),
			)
		);
		?>
	</div><!-- .entry-content -->

	

	<?php if ( ! is_singular( 'attachment' ) ) : ?>
		<?php get_template_part( 'template-parts/post/author-bio' ); ?>
	<?php endif; ?>

</article><!-- #post-${ID} -->
