<?php
/* Template Name: Enhanced Homepage (v0) */
get_header(); ?>
<section class="grid gap-6 md:grid-cols-3">
  <div class="md:col-span-2 space-y-6">
    <div class="rounded-2xl border bg-card p-5">
      <h2 class="text-lg font-semibold">Upcoming Fixtures</h2>
      <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <?php the_content(); ?>
      </div>
    </div>
    <div class="rounded-2xl border bg-card p-5">
      <h2 class="text-lg font-semibold">Latest Results</h2>
      <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <!-- Drop your results shortcode or block here -->
      </div>
    </div>
  </div>
  <aside class="space-y-6">
    <div class="rounded-2xl border bg-card p-5">
      <h3 class="text-base font-semibold">Leaderboard</h3>
      <?php echo shortcode_exists('football_pool_rankings') ? do_shortcode('[football_pool_rankings top=10]') : ''; ?>
    </div>
    <div class="rounded-2xl border bg-card p-5">
      <h3 class="text-base font-semibold">News</h3>
      <!-- Your news widget shortcode -->
    </div>
  </aside>
</section>
<?php get_footer(); ?>