<?php
/* Template Name: Rankings (v0) */
get_header(); ?>
<div class="rounded-2xl border bg-card p-5">
  <div class="flex items-center justify-between gap-3">
    <h1 class="text-xl font-semibold">Rankings</h1>
  </div>
  <div class="mt-5 overflow-x-auto">
    <?php echo shortcode_exists('football_pool_rankings') ? do_shortcode('[football_pool_rankings]') : ''; ?>
  </div>
</div>
<?php get_footer(); ?>