<?php
/** Header (v0 look) */
?><!doctype html>
<html <?php language_attributes(); ?> class="h-full antialiased">
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>

  <script>
    // Tailwind CDN runtime config to map v0 CSS variables to utility colors
    window.tailwind = window.tailwind || {};
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            background: 'oklch(var(--color-background) / <alpha-value>)',
            foreground: 'oklch(var(--color-foreground) / <alpha-value>)',
            card: 'oklch(var(--color-card) / <alpha-value>)',
            'card-foreground': 'oklch(var(--color-card-foreground) / <alpha-value>)',
            popover: 'oklch(var(--color-popover) / <alpha-value>)',
            'popover-foreground': 'oklch(var(--color-popover-foreground) / <alpha-value>)',
            primary: 'oklch(var(--color-primary) / <alpha-value>)',
            'primary-foreground': 'oklch(var(--color-primary-foreground) / <alpha-value>)',
            secondary: 'oklch(var(--color-secondary) / <alpha-value>)',
            'secondary-foreground': 'oklch(var(--color-secondary-foreground) / <alpha-value>)',
            muted: 'oklch(var(--color-muted) / <alpha-value>)',
            'muted-foreground': 'oklch(var(--color-muted-foreground) / <alpha-value>)',
            accent: 'oklch(var(--color-accent) / <alpha-value>)',
            'accent-foreground': 'oklch(var(--color-accent-foreground) / <alpha-value>)',
            destructive: 'oklch(var(--color-destructive) / <alpha-value>)',
            'destructive-foreground': 'oklch(var(--color-destructive-foreground) / <alpha-value>)',
            border: 'oklch(var(--color-border) / <alpha-value>)',
            input: 'oklch(var(--color-input) / <alpha-value>)',
            ring: 'oklch(var(--color-ring) / <alpha-value>)',
            sidebar: 'oklch(var(--color-sidebar) / <alpha-value>)',
            'sidebar-foreground': 'oklch(var(--color-sidebar-foreground) / <alpha-value>)',
            'sidebar-primary': 'oklch(var(--color-sidebar-primary) / <alpha-value>)',
            'sidebar-primary-foreground': 'oklch(var(--color-sidebar-primary-foreground) / <alpha-value>)',
            'sidebar-accent': 'oklch(var(--color-sidebar-accent) / <alpha-value>)',
            'sidebar-accent-foreground': 'oklch(var(--color-sidebar-accent-foreground) / <alpha-value>)',
            'sidebar-border': 'oklch(var(--color-sidebar-border) / <alpha-value>)',
            'sidebar-ring': 'oklch(var(--color-sidebar-ring) / <alpha-value>)',
          },
          borderRadius: {
            lg: 'var(--radius-lg)',
            xl: 'var(--radius-xl)',
            '2xl': 'calc(var(--radius-xl) + 4px)'
          }
        }
      }
    };
  </script>
  <script src="https://cdn.tailwindcss.com"></script>

</head>
<body <?php body_class("min-h-screen bg-background text-foreground"); ?>>
<?php wp_body_open(); ?>

<header class="sticky top-0 z-50 border-b bg-card/80 backdrop-blur supports-[backdrop-filter]:bg-card/60">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="flex items-center gap-3">
      <?php if (has_custom_logo()) :
        $logo_id = get_theme_mod('custom_logo');
        $logo = $logo_id ? wp_get_attachment_image_src($logo_id, 'full') : null;
        if ($logo): ?>
        <img src="<?php echo esc_url($logo[0]); ?>" alt="<?php bloginfo('name'); ?>" class="h-8 w-auto rounded-md" />
      <?php else: ?>
        <span class="text-lg font-semibold"><?php bloginfo('name'); ?></span>
      <?php endif; else: ?>
        <span class="text-lg font-semibold"><?php bloginfo('name'); ?></span>
      <?php endif; ?>
    </a>

    <nav class="hidden md:flex items-center gap-6" aria-label="Primary">
      <?php
        wp_nav_menu([
          'theme_location' => 'primary',
          'container'      => false,
          'items_wrap'     => '%3$s',
          'depth'          => 1,
          'fallback_cb'    => '__return_empty_string',
          'walker'         => new class extends Walker_Nav_Menu {
            function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
              $classes = 'text-sm font-medium text-muted-foreground hover:text-foreground transition';
              $output .= '<a class="'.$classes.'" href="'. esc_url($item->url) .'">'. esc_html($item->title) .'</a>';
            }
          }
        ]);
      ?>
    </nav>

    <div class="flex items-center gap-2">
      <?php if (is_user_logged_in()): ?>
        <a href="<?php echo esc_url( wp_logout_url( home_url('/') ) ); ?>" class="inline-flex items-center rounded-xl border px-3 py-1.5 text-sm hover:bg-accent">Log out</a>
      <?php else: ?>
        <a href="<?php echo esc_url( wp_login_url() ); ?>" class="inline-flex items-center rounded-xl border px-3 py-1.5 text-sm hover:bg-accent">Log in</a>
      <?php endif; ?>
      <button class="md:hidden inline-flex items-center rounded-xl border px-2 py-1.5" type="button" aria-label="Toggle menu" data-nav-toggle aria-expanded="false">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
  </div>

  <div class="md:hidden border-t hidden" data-nav-menu>
    <div class="mx-auto max-w-7xl px-4 py-3 flex flex-col gap-2">
      <?php
        wp_nav_menu([
          'theme_location' => 'primary',
          'container'      => false,
          'items_wrap'     => '%3$s',
          'depth'          => 1,
          'fallback_cb'    => '__return_empty_string',
          'walker'         => new class extends Walker_Nav_Menu {
            function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
              $classes = 'block rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent';
              $output .= '<a class="'.$classes.'" href="'. esc_url($item->url) .'">'. esc_html($item->title) .'</a>';
            }
          }
        ]);
      ?>
    </div>
  </div>
</header>

<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
