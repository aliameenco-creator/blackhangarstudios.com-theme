<?php
/** Root portfolio URLs; maintained with the theme, no extra plugin. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! function_exists( 'bhpu_root_allowed' ) ) {

function bhpu_root_allowed( $post ) {
    if ( ! $post || 'bh_portfolio' !== $post->post_type || 'publish' !== $post->post_status || ! $post->post_name ) { return false; }
    $reserved = array( 'portfolio', 'production', 'wp-json', 'wp-admin', 'wp-login.php', 'feed', 'author', 'category', 'tag', 'search', 'page', 'sitemap.xml', 'robots.txt' );
    foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
        if ( $type->has_archive ) { $reserved[] = is_string( $type->has_archive ) ? $type->has_archive : ( $type->rewrite['slug'] ?? $type->name ); }
    }
    foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) {
        if ( ! empty( $taxonomy->rewrite['slug'] ) ) { $reserved[] = explode( '/', $taxonomy->rewrite['slug'] )[0]; }
    }
    if ( in_array( $post->post_name, $reserved, true ) ) { return false; }
    $types = array_values( get_post_types( array( 'public' => true ) ) );
    $matches = get_posts( array( 'post_type' => $types, 'post_status' => array( 'publish', 'private' ), 'name' => $post->post_name, 'post__not_in' => array( $post->ID ), 'posts_per_page' => 1, 'fields' => 'ids' ) );
    return ! $matches;
}

add_filter( 'post_type_link', function ( $url, $post ) {
    return bhpu_root_allowed( $post ) ? home_url( user_trailingslashit( '/' . $post->post_name ) ) : $url;
}, 10, 2 );

add_action( 'parse_request', function ( $wp ) {
    if ( is_admin() || isset( $_GET['preview'] ) || isset( $_GET['rest_route'] ) ) { return; }
    $slug = trim( $wp->request, '/' );
    if ( ! $slug || false !== strpos( $slug, '/' ) ) { return; }
    $post = get_page_by_path( $slug, OBJECT, 'bh_portfolio' );
    if ( ! bhpu_root_allowed( $post ) ) { return; }
    // Preserve query arguments unrelated to page identity, such as tracking parameters.
    foreach ( array( 'name', 'pagename', 'page', 'attachment', 'attachment_id', 'error' ) as $key ) { unset( $wp->query_vars[ $key ] ); }
    $wp->query_vars['post_type'] = 'bh_portfolio';
    $wp->query_vars['p'] = $post->ID;
} );

add_action( 'template_redirect', function () {
    if ( ! is_singular( 'bh_portfolio' ) || is_preview() || ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true ) ) { return; }
    $post = get_queried_object();
    if ( ! bhpu_root_allowed( $post ) ) { return; }
    $path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
    $target = get_permalink( $post );
    if ( untrailingslashit( $path ?? '' ) !== untrailingslashit( wp_parse_url( $target, PHP_URL_PATH ) ) ) {
        wp_safe_redirect( $target, 301, 'Black Hangar Portfolio URLs' );
        exit;
    }
}, 1 );

}
