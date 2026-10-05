<?php
/** Black Hangar presentation and project editing. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/includes/portfolio.php';
require_once __DIR__ . '/includes/latest-portfolio.php';
require_once __DIR__ . '/includes/homepage-sections.php';
require_once __DIR__ . '/includes/portfolio-urls.php';
require_once __DIR__ . '/includes/page-designs.php';
require_once __DIR__ . '/includes/github-updater.php';
add_action( 'after_setup_theme', function () {
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/theme.css' );
    add_editor_style( 'assets/built-pages.css' );
    add_editor_style( 'assets/poster-grid.css' );
    add_editor_style( 'assets/latest-portfolio.css' );
    add_editor_style( 'assets/trust-footer.css' );
    add_theme_support( 'post-thumbnails' );
} );
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'black-hangar', get_theme_file_uri( 'assets/theme.css' ), array(), '0.1.0' );
    wp_enqueue_style( 'black-hangar-layout-fixes', get_theme_file_uri( 'assets/layout-fixes.css' ), array( 'black-hangar' ), (string) filemtime( get_theme_file_path( 'assets/layout-fixes.css' ) ) );
    wp_enqueue_style( 'black-hangar-blog-fixes', get_theme_file_uri( 'assets/blog-fixes.css' ), array( 'black-hangar-layout-fixes' ), (string) filemtime( get_theme_file_path( 'assets/blog-fixes.css' ) ) );
    wp_enqueue_style( 'black-hangar-reference-fixes', get_theme_file_uri( 'assets/reference-fixes.css' ), array( 'black-hangar-blog-fixes' ), (string) filemtime( get_theme_file_path( 'assets/reference-fixes.css' ) ) );
    wp_enqueue_style( 'black-hangar-built-pages', get_theme_file_uri( 'assets/built-pages.css' ), array( 'black-hangar-reference-fixes' ), (string) filemtime( get_theme_file_path( 'assets/built-pages.css' ) ) );
    wp_enqueue_style( 'black-hangar-poster-grid', get_theme_file_uri( 'assets/poster-grid.css' ), array( 'black-hangar-built-pages' ), (string) filemtime( get_theme_file_path( 'assets/poster-grid.css' ) ) );
    wp_enqueue_style( 'black-hangar-latest-portfolio', get_theme_file_uri( 'assets/latest-portfolio.css' ), array( 'black-hangar-poster-grid' ), '0.3.9' );
    wp_enqueue_style( 'black-hangar-trust-footer', get_theme_file_uri( 'assets/trust-footer.css' ), array( 'black-hangar-latest-portfolio' ), '0.3.9' );
    wp_enqueue_script( 'black-hangar', get_theme_file_uri( 'assets/theme.js' ), array(), (string) filemtime( get_theme_file_path( 'assets/theme.js' ) ), true );
} );
add_action( 'init', function () {
    wp_register_script( 'bh-editor', get_theme_file_uri( 'assets/editor.js' ), array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ), '0.3.9', true );
    foreach ( array( 'portfolio', 'project' ) as $name ) {
        register_block_type( __DIR__ . '/blocks/' . $name, array( 'render_callback' => 'bh_render_' . $name ) );
    }
} );
add_action( 'enqueue_block_editor_assets', function () {
    wp_enqueue_script( 'bh-editor' );
} );
add_action( 'admin_notices', function () {
    if ( current_user_can( 'manage_options' ) && ! post_type_exists( 'bh_portfolio' ) ) {
        echo '<div class="notice notice-warning"><p>' . esc_html__( 'Black Hangar: configure the bh_portfolio post type in CPT UI. See the theme setup guide. No content type is registered by this theme.', 'black-hangar' ) . '</p></div>';
    }
} );
