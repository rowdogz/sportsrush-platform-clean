<?php
/**
 * SportsRush v0 — Functions
 */
if (!defined('ABSPATH')) exit;

add_action('after_setup_theme', function () {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('custom-logo', [
    'height'      => 64,
    'width'       => 64,
    'flex-width'  => true,
    'flex-height' => true,
  ]);
  add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
  register_nav_menus([
    'primary' => __('Primary Menu', 'sportsrush-v0'),
    'footer'  => __('Footer Menu',  'sportsrush-v0'),
  ]);
});

add_action('wp_enqueue_scripts', function () {
  // Dequeue common legacy styles if present (prevents specificity wars)
  wp_dequeue_style('sports-lite-style');
  wp_deregister_style('sports-lite-style');

  // v0 globals
  $globals_path = get_stylesheet_directory() . '/assets/css/globals.css';
  $globals_uri  = get_stylesheet_directory_uri() . '/assets/css/globals.css';
  if (file_exists($globals_path)) {
    wp_enqueue_style('sportsrush-v0-globals', $globals_uri, [], filemtime($globals_path));
  }

  // Optional: legacy overrides file if you drop one in later
  $legacy_path = get_stylesheet_directory() . '/style-2.css';
  $legacy_uri  = get_stylesheet_directory_uri() . '/style-2.css';
  if (file_exists($legacy_path)) {
    wp_enqueue_style('sportsrush-legacy-overrides', $legacy_uri, ['sportsrush-v0-globals'], filemtime($legacy_path));
  }

  // Small JS for mobile nav
  $nav_js_path = get_stylesheet_directory() . '/assets/js/nav.js';
  $nav_js_uri  = get_stylesheet_directory_uri() . '/assets/js/nav.js';
  if (file_exists($nav_js_path)) {
    wp_enqueue_script('sportsrush-v0-nav', $nav_js_uri, [], filemtime($nav_js_path), true);
  }
}, 20);
