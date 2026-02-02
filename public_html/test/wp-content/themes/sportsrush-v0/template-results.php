<?php
/* Template Name: Results (v0) */
get_header(); ?>
<div class="rounded-2xl border bg-card p-5">
  <h1 class="text-xl font-semibold">Results</h1>
  <div class="mt-4 grid gap-3 md:grid-cols-2">
    <?php the_content(); ?>
  </div>
</div>
<?php get_footer(); ?>