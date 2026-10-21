<?php
/**
 * Homepage additions applied when the page is shown, so the homepage saved in the Site Editor is never changed:
 * - browser tab / search title and description from the original site when none is set (the tab showed "-");
 * - an "Our Studio" button next to Portfolio in the hero, opening the 360 studio tour on the Videos page;
 * - a lighter copy of the hero trailer with a poster frame.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bh_home_title() { return 'Black Hangar Studios | Best Film & TV Production Studio'; }

/** True when a title is the theme's earlier default, which may already be saved as the Yoast homepage title. */
function bh_title_is_old_default( $title ) {
    return "Black Hangar Studios | UK's Best Film & TV Production Studio" === html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES );
}
function bh_home_description() { return "Black Hangar Studios is the best film & TV production studio in Hampshire offering the UK's Largest Green Screen & all production needs in one place"; }

/** True when a title is missing or only separators, e.g. "-" when the site title is empty. */
function bh_title_is_empty( $title ) {
    $title = html_entity_decode( wp_strip_all_tags( (string) $title ) );
    $name = (string) get_bloginfo( 'name' );
    if ( '' !== $name ) { $title = str_replace( $name, '', $title ); }
    return '' === trim( $title, " \t\n\r-–—|·:" );
}

add_filter( 'pre_get_document_title', function ( $title ) {
    return is_front_page() && '' === get_bloginfo( 'name' ) ? bh_home_title() : $title;
}, 20 );
foreach ( array( 'wpseo_title', 'wpseo_opengraph_title', 'wpseo_twitter_title' ) as $hook ) {
    add_filter( $hook, function ( $title ) { return is_front_page() && ( bh_title_is_empty( $title ) || bh_title_is_old_default( $title ) ) ? bh_home_title() : $title; }, 20 );
}
foreach ( array( 'wpseo_metadesc', 'wpseo_opengraph_desc' ) as $hook ) {
    add_filter( $hook, function ( $text ) { return is_front_page() && '' === trim( (string) $text ) ? bh_home_description() : $text; }, 20 );
}
add_action( 'wp_head', function () {
    if ( is_front_page() && ! defined( 'WPSEO_VERSION' ) ) { echo '<meta name="description" content="' . esc_attr( bh_home_description() ) . '">' . "\n"; }
}, 1 );

/**
 * Serves the hero trailer as a 720p/30fps copy without audio (about 2 MB instead of 8.9 MB) with a poster frame,
 * so the homepage shows the hero at once and loads far less. The saved homepage and Media Library file are unchanged.
 */
add_filter( 'render_block_core/cover', function ( $html ) {
    if ( ! is_front_page() || false === strpos( $html, 'blackhangarstudios-trailer.mp4' ) ) { return $html; }
    $video  = esc_url( get_theme_file_uri( 'assets/hero/hero-720.mp4' ) );
    $poster = esc_url( get_theme_file_uri( 'assets/hero/hero-poster.webp' ) );
    return preg_replace(
        '#(<video\b[^>]*?)\ssrc="[^"]*blackhangarstudios-trailer\.mp4"#',
        '$1 preload="auto" poster="' . $poster . '" src="' . $video . '"',
        $html,
        1
    );
} );

/** Adds Our Studio after the Portfolio button in the homepage hero (once; skipped if the homepage already has it). */
add_filter( 'render_block_core/buttons', function ( $html ) {
    static $done = false;
    if ( $done || ! is_front_page() || false === strpos( $html, 'href="/portfolio"' ) ) { return $html; }
    $done = true;
    if ( false !== strpos( $html, 'bh-studio-button' ) ) { return $html; }
    $button = '<div class="wp-block-button bh-studio-button is-style-outline"><a class="wp-block-button__link has-small-font-size has-custom-font-size wp-element-button" href="/videos#virtual-tour" style="border-top-left-radius:13px;border-top-right-radius:13px;border-bottom-left-radius:13px;border-bottom-right-radius:13px;padding-top:var(--wp--preset--spacing--20);padding-right:29px;padding-bottom:var(--wp--preset--spacing--20);padding-left:29px">Our Studio</a></div>';
    $end = strrpos( $html, '</div>' );
    return false === $end ? $html : substr( $html, 0, $end ) . $button . substr( $html, $end );
} );
