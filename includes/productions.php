<?php
/**
 * Client requests (2026-10-19), offered on Appearance -> Black Hangar Pages:
 * - "Our Studio" button next to Portfolio in the homepage hero: see includes/home.php.
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
