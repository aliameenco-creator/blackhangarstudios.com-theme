<?php
/**
 * Permanent redirects for addresses that never existed on the original site but were linked from earlier theme
 * versions or Wix (for example /production-facilities, which Wix redirected to /production-services). They only apply
 * when WordPress has nothing at that address, so a real page created later always wins.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bh_redirect_map() {
    return array(
        'production-facilities' => '/production-services',
        'services'              => '/production-services',
        'faq'                   => '/general-5',
        'privacy-policy'        => '/general-5-1',
        'terms-and-conditions'  => '/general-5-2',
    );
}

add_action( 'template_redirect', function () {
    if ( ! is_404() || ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true ) ) { return; }
    $path = trim( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
    $home = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
    if ( $home && 0 === strpos( $path, $home . '/' ) ) { $path = substr( $path, strlen( $home ) + 1 ); }
    $map = bh_redirect_map();
    if ( isset( $map[ strtolower( $path ) ] ) ) {
        wp_safe_redirect( home_url( $map[ strtolower( $path ) ] ), 301, 'Black Hangar' );
        exit;
    }
}, 5 );
