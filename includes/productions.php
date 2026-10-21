<?php
/**
 * Client requests (2026-10-19, finished films confirmed 2026-10-21), offered on Appearance -> Black Hangar Pages:
 * - "Our Studio" button next to Portfolio in the homepage hero: see includes/home.php.
 * - The finished productions The Fallow Few, The Chair and Frost added to the Portfolio and published with their
 *   official posters. "The Chair" (also "Santa's Chair") was released as A Christmas Wish For Everyone (same director,
 *   story and producers; its poster credits ORWO Studios & Black Hangar Studios), so it uses the release title and an
 *   older "The Chair" draft is renamed. Sources: the Fallow Few sales agent (Moviehouse Entertainment), The Film
 *   Catalogue (Kaleidoscope) and the official Frost site; text only from those and ORWO Distribution.
 * - Re-running is safe: a production that already exists is only completed (missing poster, year, trailer, or text
 *   still equal to the earlier draft text). Text someone changed is kept, and a published production is never unpublished.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bh_new_productions() {
    return array(
        'thefallowfew' => array(
            'title'   => 'The Fallow Few',
            'status'  => 'publish',
            'year'    => 2025,
            'content' => '<!-- wp:paragraph --><p>December 1916. The Western Front. A disillusioned soldier, tasked with guarding British deserters on death row, disobeys his superiors to save his latest charge\'s life at the eleventh hour. Based on a true story.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Starring Bradley James <em>(Cpl. George Hansen)</em>, Luke Brandon Field <em>(APM Sydney Oakes)</em>, Tony Robinson <em>(Canon F.G. Scott)</em> and Christopher Bonwell <em>(Lt. Edwin Dyett)</em>.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Written and directed by JR Rappaport. Produced by Jake Seal, JR Rappaport and Kevin Marshall. An ORWO Studios and Glasshouse Pictures production.</p><!-- /wp:paragraph -->',
            'old'     => array( '<!-- wp:paragraph --><p>Starring Bradley James <em>(Cpl. George Hansen)</em>, Luke Brandon Field <em>(APM Sydney Oakes)</em>, Tony Robinson <em>(Canon F.G. Scott)</em> and Christopher Bonwell <em>(Lt. Edwin Dyett)</em>.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>December 1916. The Western Front. A disillusioned soldier, tasked with guarding British deserters on death row, disobeys his superiors to save his latest charge\'s life at the eleventh hour.</p><!-- /wp:paragraph -->' ),
            'trailer' => 'https://www.youtube.com/watch?v=TrnLCFsN5i8',
            'image'   => 'assets/pages/the-fallow-few-poster.jpg',
            'alt'     => 'The Fallow Few poster: Bradley James, Luke Brandon Field and Tony Robinson',
        ),
        'a-christmas-wish-for-everyone' => array(
            'title'   => 'A Christmas Wish For Everyone',
            'aliases' => array( 'thechair' => 'The Chair' ),
            'status'  => 'publish',
            'year'    => 2026,
            'content' => '<!-- wp:paragraph --><p>A family-run café faces closure before Christmas, and its owner Elle and her daughter risk losing their home to developers. A magic wish-granting chair connects them with William, a visiting entrepreneur, and together they work to save the Mountain View Café and unite their community.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Starring Elizabeth Mitchell, Jeremy Piven and Bradley Stryker.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Directed by Maguy R. Cohen. Written by Maguy R. Cohen and Tom Stohlgren. Produced by Charley McDougall, Jamie McLeod-Ross, Joram Moreka and Jake Seal. An ORWO Studios and Black Hangar Studios production, developed under the title <em>The Chair</em>.</p><!-- /wp:paragraph -->',
            'old'     => array(
                '<!-- wp:paragraph --><p>Directed by Maguy Cohen.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>A mother-daughter story with a lot of heart, set in a small mountain café.</p><!-- /wp:paragraph -->',
                '<!-- wp:paragraph --><p>Directed by Maguy Cohen.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>A fresh family feature set in one location, a small mountain café: a mother-daughter story with a lot of heart.</p><!-- /wp:paragraph -->',
            ),
            'image'   => 'assets/pages/a-christmas-wish-for-everyone-poster.jpg',
            'alt'     => 'A Christmas Wish For Everyone poster: Jeremy Piven and Elizabeth Mitchell outside a snowy mountain café',
        ),
        'frost' => array(
            'title'   => 'Frost',
            'status'  => 'publish',
            'year'    => 2026,
            'content' => '<!-- wp:paragraph --><p>A rational, emotionally distant police detective comes to a small town in East Serbia, a region known for its pagan traditions and black magic, to investigate the disappearance of a girl.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Starring Kamka Tocinovski, Jovo Maksić, Antonio Scarpa and Vesna Trivalić.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Written and directed by Pavle Vučković. Produced by Tatjana Žeželj Gojković, Jake Seal and Stefan Mladenović. Original title <em>Mraz</em>. Official Competition, Sitges Film Festival 2026.</p><!-- /wp:paragraph -->',
            'old'     => array(
                '',
                '<!-- wp:paragraph --><p>A rational, emotionally distant police detective comes to a small town in East Serbia, a region known for its pagan traditions and black magic, to investigate the disappearance of a girl.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Directed by Pavle Vučković. Featuring Kamka Tocinovski, Jovo Maksić, Antonio Scarpa and Vesna Trivalić.</p><!-- /wp:paragraph -->',
            ),
            'trailer' => 'https://www.youtube.com/watch?v=WMUkNT0qAzc',
            'image'   => 'assets/pages/frost-poster.jpg',
            'alt'     => 'Frost poster: a hand touching a frozen leaf in a dark forest',
        ),
    );
}

/** The existing production for a slug or one of its earlier slugs (any status except the trash), or 0. */
function bh_production_id( $slug ) {
    $item = bh_new_productions()[ $slug ] ?? array();
    foreach ( array_merge( array( $slug ), array_keys( $item['aliases'] ?? array() ) ) as $name ) {
        $ids = get_posts( array( 'post_type' => 'bh_portfolio', 'name' => $name, 'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private' ), 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => true ) );
        if ( $ids ) { return (int) $ids[0]; }
    }
    return 0;
}

/** True when an existing production still has an earlier working title and slug that it should be renamed from. */
function bh_production_needs_rename( $slug, $id ) {
    $old = bh_new_productions()[ $slug ]['aliases'] ?? array();
    $name = get_post_field( 'post_name', $id );
    return isset( $old[ $name ] ) && get_the_title( $id ) === $old[ $name ];
}

/** True when a production was deliberately deleted (it is in the trash): it is never re-added. */
function bh_production_trashed( $slug ) {
    foreach ( array_merge( array( $slug ), array_keys( bh_new_productions()[ $slug ]['aliases'] ?? array() ) ) as $name ) {
        if ( get_posts( array( 'post_type' => 'bh_portfolio', 'name' => $name . '__trashed', 'post_status' => 'trash', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => true ) ) ) { return true; }
    }
    return false;
}

/** What adding/completing a production would change: empty when nothing is left to do. */
function bh_production_changes( $slug ) {
    $item = bh_new_productions()[ $slug ];
    $id = bh_production_id( $slug );
    if ( ! $id ) { return bh_production_trashed( $slug ) ? array() : array( 'create' ); }
    $changes = bh_production_needs_rename( $slug, $id ) ? array( 'rename' ) : array();
    if ( in_array( (string) get_post_field( 'post_content', $id ), $item['old'] ?? array(), true ) ) { $changes[] = 'content'; }
    if ( ! empty( $item['image'] ) && ! has_post_thumbnail( $id ) ) { $changes[] = 'poster'; }
    if ( ! empty( $item['year'] ) && '' === (string) get_post_meta( $id, 'bh_year', true ) ) { $changes[] = 'year'; }
    if ( ! empty( $item['trailer'] ) && '' === (string) get_post_meta( $id, 'bh_trailer', true ) ) { $changes[] = 'trailer'; }
    if ( 'publish' === $item['status'] && 'draft' === get_post_status( $id ) ) { $changes[] = 'publish'; }
    return $changes;
}

/** Slugs of the productions with something left to add. */
function bh_new_productions_pending() {
    if ( ! post_type_exists( 'bh_portfolio' ) ) { return array(); }
    return array_values( array_filter( array_keys( bh_new_productions() ), function ( $slug ) { return (bool) bh_production_changes( $slug ); } ) );
}

/** Uploads a bundled theme image to the Media Library for a production and makes it the poster. */
function bh_production_poster( $id, $item ) {
    if ( ! current_user_can( 'upload_files' ) ) { return; }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $tmp = wp_tempnam( basename( $item['image'] ) );
    if ( ! $tmp || ! copy( get_theme_file_path( $item['image'] ), $tmp ) ) { return; }
    $image = media_handle_sideload( array( 'name' => basename( $item['image'] ), 'tmp_name' => $tmp ), $id, $item['title'] . ' poster' );
    if ( is_wp_error( $image ) ) {
        wp_delete_file( $tmp );
        return;
    }
    update_post_meta( $image, '_wp_attachment_image_alt', $item['alt'] );
    set_post_thumbnail( $id, $image );
}

/** Adds or completes the new productions. Returns the number of productions changed. */
function bh_add_new_productions() {
    if ( ! current_user_can( 'edit_posts' ) ) { return 0; }
    $changed = 0;
    foreach ( bh_new_productions_pending() as $slug ) {
        $item = bh_new_productions()[ $slug ];
        $changes = bh_production_changes( $slug );
        $publish = 'publish' === $item['status'] && current_user_can( 'publish_posts' );
        if ( in_array( 'create', $changes, true ) ) {
            $id = wp_insert_post( wp_slash( array( 'post_type' => 'bh_portfolio', 'post_status' => 'draft', 'post_title' => $item['title'], 'post_name' => $slug, 'post_content' => $item['content'] ) ), true );
            if ( is_wp_error( $id ) ) { continue; }
            update_post_meta( $id, 'bh_format', 'film' );
            update_post_meta( $id, 'bh_home', '0' );
            $changes = array( 'poster', 'year', 'trailer', 'publish' );
        } else {
            $id = bh_production_id( $slug );
            if ( in_array( 'rename', $changes, true ) ) { wp_update_post( wp_slash( array( 'ID' => $id, 'post_title' => $item['title'], 'post_name' => $slug ) ) ); }
            if ( in_array( 'content', $changes, true ) ) { wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $item['content'] ) ) ); }
        }
        if ( in_array( 'year', $changes, true ) && ! empty( $item['year'] ) ) { update_post_meta( $id, 'bh_year', (int) $item['year'] ); }
        if ( in_array( 'trailer', $changes, true ) && ! empty( $item['trailer'] ) ) { update_post_meta( $id, 'bh_trailer', $item['trailer'] ); }
        if ( in_array( 'poster', $changes, true ) && ! empty( $item['image'] ) ) { bh_production_poster( $id, $item ); }
        // Only publish with a poster, so the Portfolio never shows an empty card.
        if ( in_array( 'publish', $changes, true ) && $publish && has_post_thumbnail( $id ) ) { wp_publish_post( $id ); }
        ++$changed;
    }
    return $changed;
}
