<?php
/**
 * Client requests (2026-10-19), offered on Appearance -> Black Hangar Pages:
 * - "Our Studio" button next to Portfolio in the homepage hero (in templates/front-page.html) opening a popup with
 *   the studio videos.
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

/**
 * "Our Studio" popup on the homepage: the hero's Our Studio button opens the studio showreel and studio video tour.
 * Videos come from the Media Library (migration import, found by their source hash) or, if missing, their original
 * address. Without JavaScript the button simply opens the Videos page.
 */
function bh_studio_video_url( $sha, $fallback ) {
    $ids = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_pmi_key', 'meta_value' => 'media:' . $sha, 'fields' => 'ids', 'numberposts' => 1, 'suppress_filters' => true ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
    return $ids ? wp_get_attachment_url( $ids[0] ) : $fallback;
}

add_action( 'wp_footer', function () {
    if ( ! is_front_page() ) { return; }
    $videos = array(
        array( 'Showreel', bh_studio_video_url( '9f6753c92574475aa66cf2ad47814da04e6b7e8a1196482ef3e30dae9ac7a403', 'https://video.wixstatic.com/video/0da2fe_c308a38ca0a74693b9091e117d3095f8/1080p/mp4/file.mp4' ), bh_studio_video_url( 'b4d8c558fd7359995b6889d6f9a20c302a6da228c3d592fdfb3fbf1017880dc0', '' ), 'is-scope' ),
        array( 'Studio tour', bh_studio_video_url( '87a8648aa5be98a22973514142d77b516488334fd810ee91300f671a1c042fc9', 'https://video.wixstatic.com/video/0da2fe_5818c9c46c5a4706943f082ed0998e41/1080p/mp4/file.mp4' ), bh_studio_video_url( 'ccf351569271f633925d77dfec0e619bdfccd316e5d52afbfabd7eff0f3bd6e3', '' ), 'is-wide' ),
    );
    echo '<dialog class="bh-studio-modal" id="bh-studio-modal" aria-label="Our Studio videos"><div class="bh-studio-modal-inner"><div class="bh-studio-modal-head"><p class="bh-studio-modal-title">Our Studio</p><div class="bh-studio-tabs" role="tablist">';
    foreach ( $videos as $i => $video ) {
        echo '<button type="button" role="tab" aria-selected="' . ( $i ? 'false' : 'true' ) . '" data-bh-tab="' . (int) $i . '">' . esc_html( $video[0] ) . '</button>';
    }
    echo '</div><button type="button" class="bh-studio-close" aria-label="Close">&times;</button></div>';
    foreach ( $videos as $i => $video ) {
        echo '<div class="bh-studio-video ' . esc_attr( $video[3] ) . '" data-bh-panel="' . (int) $i . '"' . ( $i ? ' hidden' : '' ) . '><video controls playsinline preload="none"' . ( $video[2] ? ' poster="' . esc_url( $video[2] ) . '"' : '' ) . ' src="' . esc_url( $video[1] ) . '"></video></div>';
    }
    echo '<p class="bh-studio-more"><a href="/videos">More studio videos and the 360° virtual tour &rarr;</a></p></div></dialog>';
} );
