<?php
/** Footer (v0 look) */
?>
</main>

<footer class="border-t bg-card">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 grid gap-8 md:grid-cols-3">
    <div>
      <div class="text-lg font-semibold"><?php bloginfo('name'); ?></div>
      <p class="mt-2 text-sm text-muted-foreground"><?php bloginfo('description'); ?></p>
    </div>
    <div>
      <div class="text-sm font-medium mb-3">Navigation</div>
      <nav class="flex flex-col gap-2">
        <?php
          wp_nav_menu([
            'theme_location' => 'footer',
            'container'      => false,
            'items_wrap'     => '%3$s',
            'depth'          => 1,
            'fallback_cb'    => '__return_empty_string',
            'walker'         => new class extends Walker_Nav_Menu {
              function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
                $output .= '<a class="text-sm text-muted-foreground hover:text-foreground" href="'. esc_url($item->url) .'">'. esc_html($item->title) .'</a>';
              }
            }
          ]);
        ?>
      </nav>
    </div>
    <div>
      <div class="text-sm font-medium mb-3">Follow</div>
      <div class="flex gap-3">
        <a class="inline-flex h-9 w-9 items-center justify-center rounded-full border hover:bg-accent" href="#" aria-label="X/Twitter">X</a>
        <a class="inline-flex h-9 w-9 items-center justify-center rounded-full border hover:bg-accent" href="#" aria-label="Facebook">Fb</a>
        <a class="inline-flex h-9 w-9 items-center justify-center rounded-full border hover:bg-accent" href="#" aria-label="Instagram">Ig</a>
      </div>
    </div>
  </div>
  <div class="border-t">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4 text-center text-xs text-muted-foreground">
      © <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
