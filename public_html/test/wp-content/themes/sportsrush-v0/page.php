<?php get_header(); ?>
<div class="rounded-2xl border bg-card p-5">
  <div class="prose max-w-none">
    <?php
      if (have_posts()) : while (have_posts()) : the_post();
        the_content();
      endwhile; endif;
    ?>
  </div>
</div>
<?php get_footer(); ?>
