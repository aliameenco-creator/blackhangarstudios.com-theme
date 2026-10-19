<?php
/**
 * Client requests (2026-10-19), offered on Appearance -> Black Hangar Pages:
 * - "Studio Video" and "Services" buttons in the homepage hero, next to "Portfolio". Only that button row of the
 *   saved homepage changes; the previous homepage is backed up and can be restored.
 * - The finished productions The Fallow Few, The Chair and Frost added to the Portfolio as drafts, using only
 *   verified text from the archived Fallow Few site and ORWO Distribution. Add posters and years, then publish.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bh_new_productions() {
    return array(
        'thefallowfew' => array(
            'title'   => 'The Fallow Few',
            'content' => '<!-- wp:paragraph --><p>Starring Bradley James <em>(Cpl. George Hansen)</em>, Luke Brandon Field <em>(APM Sydney Oakes)</em>, Tony Robinson <em>(Canon F.G. Scott)</em> and Christopher Bonwell <em>(Lt. Edwin Dyett)</em>.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>December 1916. The Western Front. A disillusioned soldier, tasked with guarding British deserters on death row, disobeys his superiors to save his latest charge\'s life at the eleventh hour.</p><!-- /wp:paragraph -->',
            'trailer' => 'https://www.youtube.com/watch?v=TrnLCFsN5i8',
            'image'   => 'assets/pages/the-fallow-few-still.jpg',
            'alt'     => 'Tony Robinson as Canon F.G. Scott in The Fallow Few',
        ),
        'thechair' => array(
            'title'   => 'The Chair',
            'content' => '<!-- wp:paragraph --><p>Directed by Maguy Cohen.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>A mother-daughter story with a lot of heart, set in a small mountain café.</p><!-- /wp:paragraph -->',
        ),
        'frost' => array( 'title' => 'Frost', 'content' => '' ),
    );
}

/** Slugs of the new productions not yet on the website (in any status, including the trash). */
function bh_new_productions_pending() {
    if ( ! post_type_exists( 'bh_portfolio' ) ) { return array(); }
    return array_filter( array_keys( bh_new_productions() ), function ( $slug ) {
        return ! get_posts( array( 'post_type' => 'bh_portfolio', 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => true ) )
            && ! get_posts( array( 'post_type' => 'bh_portfolio', 'name' => $slug . '__trashed', 'post_status' => 'trash', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => true ) );
    } );
}

function bh_add_new_productions() {
    if ( ! current_user_can( 'edit_posts' ) ) { return 0; }
    $added = 0;
    foreach ( bh_new_productions_pending() as $slug ) {
        $item = bh_new_productions()[ $slug ];
        $id = wp_insert_post( wp_slash( array( 'post_type' => 'bh_portfolio', 'post_status' => 'draft', 'post_title' => $item['title'], 'post_name' => $slug, 'post_content' => $item['content'] ) ), true );
        if ( is_wp_error( $id ) ) { continue; }
        update_post_meta( $id, 'bh_format', 'film' );
        update_post_meta( $id, 'bh_home', '0' );
        if ( ! empty( $item['trailer'] ) ) { update_post_meta( $id, 'bh_trailer', $item['trailer'] ); }
        if ( ! empty( $item['image'] ) && current_user_can( 'upload_files' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $tmp = wp_tempnam( basename( $item['image'] ) );
            if ( $tmp && copy( get_theme_file_path( $item['image'] ), $tmp ) ) {
                $image = media_handle_sideload( array( 'name' => basename( $item['image'] ), 'tmp_name' => $tmp ), $id );
                if ( is_wp_error( $image ) ) { wp_delete_file( $tmp ); }
                else {
                    update_post_meta( $image, '_wp_attachment_image_alt', $item['alt'] );
                    set_post_thumbnail( $id, $image );
                }
            }
        }
        ++$added;
    }
    return $added;
}

/** Saved homepage template of this theme (Site Editor), or null when the theme file is used. */
function bh_home_template_post() {
    $posts = get_posts( array( 'post_type' => 'wp_template', 'post_status' => array( 'publish', 'draft' ), 'name' => 'front-page', 'numberposts' => 1, 'suppress_filters' => true, 'tax_query' => array( array( 'taxonomy' => 'wp_theme', 'field' => 'slug', 'terms' => get_stylesheet() ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
    return $posts ? $posts[0] : null;
}

function bh_hero_buttons_markup() {
    return '<!-- wp:button {"className":"bh-hero-secondary"} -->' . "\n" . '<div class="wp-block-button bh-hero-secondary"><a class="wp-block-button__link wp-element-button" href="/videos#video-tour">Studio Video</a></div>' . "\n" . '<!-- /wp:button -->'
        . "\n\n" . '<!-- wp:button {"className":"bh-hero-secondary"} -->' . "\n" . '<div class="wp-block-button bh-hero-secondary"><a class="wp-block-button__link wp-element-button" href="/production-services">Services</a></div>' . "\n" . '<!-- /wp:button -->';
}

/** Adds the two buttons to the first button row of the homepage hero. Returns new content, or null if not possible. */
function bh_add_hero_buttons_to( $content ) {
    if ( false !== strpos( $content, 'href="/videos#video-tour"' ) ) { return null; }
    $blocks = parse_blocks( $content );
    $done = false;
    $walk = function ( &$list, $in_hero ) use ( &$walk, &$done ) {
        foreach ( $list as &$block ) {
            if ( $done ) { return; }
            $hero = $in_hero || ( 'core/cover' === $block['blockName'] && preg_match( '/(^|\s)hero(\s|$)/', $block['attrs']['className'] ?? '' ) );
            if ( $hero && 'core/buttons' === $block['blockName'] ) {
                foreach ( parse_blocks( bh_hero_buttons_markup() ) as $button ) {
                    if ( 'core/button' !== $button['blockName'] ) { continue; }
                    $block['innerBlocks'][] = $button;
                    $count = count( $block['innerContent'] );
                    // innerContent = [opening, null, ..., closing]: insert a placeholder before the closing markup.
                    array_splice( $block['innerContent'], $count - 1, 0, array( null ) );
                }
                $done = true;
                return;
            }
            if ( ! empty( $block['innerBlocks'] ) ) { $walk( $block['innerBlocks'], $hero ); }
        }
    };
    $walk( $blocks, false );
    return $done ? serialize_blocks( $blocks ) : null;
}

/** null when nothing needs doing, otherwise 'saved' (Site Editor homepage) or 'file' (theme file already has them). */
function bh_hero_buttons_state() {
    $post = bh_home_template_post();
    if ( ! $post ) { return null; }
    return null === bh_add_hero_buttons_to( $post->post_content ) ? null : 'saved';
}

function bh_add_hero_buttons() {
    if ( ! current_user_can( 'edit_theme_options' ) ) { return new WP_Error( 'bh_permission', 'Permission required.' ); }
    $post = bh_home_template_post();
    if ( ! $post ) { return new WP_Error( 'bh_home', 'The homepage uses the theme file, which already includes the buttons.' ); }
    $content = bh_add_hero_buttons_to( $post->post_content );
    if ( null === $content ) { return new WP_Error( 'bh_home', 'The buttons are already there, or no hero button row was found.' ); }
    if ( false === get_option( 'bh_home_before_hero_buttons', false ) ) { update_option( 'bh_home_before_hero_buttons', $post->post_content, false ); }
    $result = wp_update_post( wp_slash( array( 'ID' => $post->ID, 'post_content' => $content ) ), true );
    return is_wp_error( $result ) ? $result : true;
}

function bh_restore_home_before_hero_buttons() {
    $backup = get_option( 'bh_home_before_hero_buttons', false );
    $post = bh_home_template_post();
    if ( false === $backup || ! $post || ! current_user_can( 'edit_theme_options' ) ) { return new WP_Error( 'bh_home', 'Nothing to restore.' ); }
    $result = wp_update_post( wp_slash( array( 'ID' => $post->ID, 'post_content' => $backup ) ), true );
    if ( is_wp_error( $result ) ) { return $result; }
    delete_option( 'bh_home_before_hero_buttons' );
    return true;
}
