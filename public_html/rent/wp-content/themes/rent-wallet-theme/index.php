<?php
/**
 * Main template file
 *
 * @package Rent_Wallet_Theme
 */

get_header();
?>

<?php if (have_posts()): ?>
    <?php while (have_posts()): the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <?php if (!is_singular()): ?>
                    <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <?php endif; ?>
            </header>
            
            <div class="entry-content">
                <?php
                if (is_singular()) {
                    the_content();
                } else {
                    the_excerpt();
                }
                ?>
            </div>
        </article>
    <?php endwhile; ?>
<?php else: ?>
    <p><?php _e('No content found.', 'rent-wallet-theme'); ?></p>
<?php endif; ?>

<?php
get_footer();
