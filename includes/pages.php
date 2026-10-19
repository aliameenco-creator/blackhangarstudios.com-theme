<?php
/**
 * Appearance -> Black Hangar Pages: puts the designed, editable layouts (parts/pages/<slug>.html) into the existing
 * pages at their original addresses, restores the original blog publication dates and repairs old menu links.
 *
 * - The homepage and the News listing are never touched.
 * - Every page keeps its address, title, SEO settings and status. Its previous content and template are saved once and
 *   can be restored from the same screen; WordPress also keeps a revision.
 * - Pages someone edited after a design was applied are not selected by default, so edits are not overwritten.
 * - Images come from the Media Library (the files the migration importer uploaded, found by their source hash). A
 *   missing image is uploaded from the theme or from its original address first; nothing is changed if that fails.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bh_pages_manifest() {
    static $manifest = null;
    if ( null === $manifest ) {
        $file = get_theme_file_path( 'parts/pages.json' );
        $manifest = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array();
        if ( ! is_array( $manifest ) ) { $manifest = array(); }
    }
    return $manifest;
}

function bh_pages_version() {
    return (string) ( bh_pages_manifest()['version'] ?? '0' );
}

/** Existing page with this slug (any status except trash), or 0. */
function bh_pages_find( $slug ) {
    $ids = get_posts( array( 'post_type' => 'page', 'name' => $slug, 'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private' ), 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => true ) );
    return $ids ? (int) $ids[0] : 0;
}

/** missing | imported (not designed yet) | current | outdated | edited */
function bh_pages_state( $slug ) {
    $id = bh_pages_find( $slug );
    if ( ! $id ) { return array( 0, 'missing' ); }
    $version = get_post_meta( $id, '_bh_design_version', true );
    if ( '' === $version ) { return array( $id, 'imported' ); }
    if ( md5( (string) get_post_field( 'post_content', $id ) ) !== get_post_meta( $id, '_bh_design_hash', true ) ) { return array( $id, 'edited' ); }
    return array( $id, $version === bh_pages_version() ? 'current' : 'outdated' );
}

/** Attachment for a media hash: the importer's upload, an earlier theme upload, or a new upload. 0 if unavailable. */
function bh_pages_media_id( $sha, $upload = true ) {
    static $cache = array();
    if ( isset( $cache[ $sha ] ) ) { return $cache[ $sha ]; }
    foreach ( array( '_pmi_key' => 'media:' . $sha, '_bh_media_key' => $sha ) as $key => $value ) {
        $ids = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => $key, 'meta_value' => $value, 'fields' => 'ids', 'numberposts' => 1, 'suppress_filters' => true ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
        if ( $ids ) { return $cache[ $sha ] = (int) $ids[0]; }
    }
    $entry = bh_pages_manifest()['media'][ $sha ] ?? null;
    if ( ! $upload || ! $entry || 'video' === $entry['type'] || ! current_user_can( 'upload_files' ) ) { return 0; }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    if ( ! empty( $entry['bundled'] ) ) {
        $tmp = wp_tempnam( $entry['name'] );
        if ( ! $tmp || ! copy( get_theme_file_path( $entry['bundled'] ), $tmp ) ) { return 0; }
    } else {
        $tmp = download_url( $entry['source'], 120 );
        if ( is_wp_error( $tmp ) ) { return 0; }
    }
    $id = media_handle_sideload( array( 'name' => $entry['name'], 'tmp_name' => $tmp ), 0 );
    if ( is_wp_error( $id ) ) {
        wp_delete_file( $tmp );
        return 0;
    }
    update_post_meta( $id, '_bh_media_key', $sha );
    if ( ! empty( $entry['alt'] ) ) { update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $entry['alt'] ) ); }
    return $cache[ $sha ] = (int) $id;
}

/** The page's block markup with media resolved, or WP_Error. */
function bh_pages_markup( $slug ) {
    $file = get_theme_file_path( 'parts/pages/' . sanitize_file_name( $slug ) . '.html' );
    if ( ! is_readable( $file ) ) { return new WP_Error( 'bh_pages_missing', 'Design file missing for ' . $slug . '.' ); }
    $missing = array();
    $markup = preg_replace_callback( '/\{\{(img|id|video|file):([0-9a-f]{64})(?::(large|full|medium))?\}\}/', function ( $match ) use ( &$missing ) {
        list( , $kind, $sha ) = $match;
        $entry = bh_pages_manifest()['media'][ $sha ] ?? array();
        $id = bh_pages_media_id( $sha );
        if ( 'video' === $kind || 'file' === $kind ) {
            $url = $id ? wp_get_attachment_url( $id ) : ( $entry['source'] ?? '' );
            if ( ! $url ) { $missing[] = $sha; }
            return esc_url( (string) $url );
        }
        if ( ! $id ) {
            $missing[] = $sha;
            return 'img' === $kind ? '' : '0';
        }
        if ( 'id' === $kind ) { return (string) $id; }
        $url = wp_get_attachment_image_url( $id, $match[3] ?? 'large' );
        return esc_url( $url ? $url : (string) wp_get_attachment_url( $id ) );
    }, (string) file_get_contents( $file ) );
    if ( $missing ) { return new WP_Error( 'bh_pages_media', 'Images could not be found or uploaded: ' . count( array_unique( $missing ) ) . '. The page was not changed.' ); }
    return $markup;
}

function bh_pages_apply( $slug ) {
    $page = null;
    foreach ( bh_pages_manifest()['pages'] ?? array() as $item ) {
        if ( $item['slug'] === $slug ) { $page = $item; }
    }
    if ( ! $page ) { return new WP_Error( 'bh_pages_unknown', 'Unknown page.' ); }
    $id = bh_pages_find( $slug );
    if ( $id && ( (int) get_option( 'page_on_front' ) === $id || (int) get_option( 'page_for_posts' ) === $id ) ) { return new WP_Error( 'bh_pages_protected', 'The homepage and posts page are never changed.' ); }
    if ( $id ? ! current_user_can( 'edit_page', $id ) : ! current_user_can( 'publish_pages' ) ) { return new WP_Error( 'bh_pages_permission', 'Permission required.' ); }
    $markup = bh_pages_markup( $slug );
    if ( is_wp_error( $markup ) ) { return $markup; }
    if ( $id ) {
        if ( ! metadata_exists( 'post', $id, '_bh_design_backup' ) ) {
            update_post_meta( $id, '_bh_design_backup', wp_slash( array( 'content' => get_post_field( 'post_content', $id ), 'template' => get_post_meta( $id, '_wp_page_template', true ) ) ) );
        }
        $result = wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $markup ) ), true );
    } else {
        $result = wp_insert_post( wp_slash( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $page['title'], 'post_name' => $slug, 'post_content' => $markup ) ), true );
    }
    if ( is_wp_error( $result ) ) { return $result; }
    $id = (int) $result;
    update_post_meta( $id, '_wp_page_template', 'page-designed' );
    update_post_meta( $id, '_bh_design_version', bh_pages_version() );
    update_post_meta( $id, '_bh_design_hash', md5( (string) get_post_field( 'post_content', $id ) ) );
    return $id;
}

function bh_pages_restore( $slug ) {
    $id = bh_pages_find( $slug );
    if ( ! $id || ! current_user_can( 'edit_page', $id ) ) { return new WP_Error( 'bh_pages_permission', 'Permission required.' ); }
    $backup = get_post_meta( $id, '_bh_design_backup', true );
    if ( ! is_array( $backup ) ) { return new WP_Error( 'bh_pages_backup', 'No saved previous version.' ); }
    $result = wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $backup['content'] ) ), true );
    if ( is_wp_error( $result ) ) { return $result; }
    update_post_meta( $id, '_wp_page_template', $backup['template'] ? $backup['template'] : 'default' );
    foreach ( array( '_bh_design_backup', '_bh_design_version', '_bh_design_hash' ) as $key ) { delete_post_meta( $id, $key ); }
    return $id;
}

/** Imported posts whose publication date is not the original one: [post ID => original GMT date]. */
function bh_pages_post_dates() {
    $file = get_theme_file_path( 'parts/post-dates.json' );
    $dates = is_readable( $file ) ? (array) json_decode( (string) file_get_contents( $file ), true ) : array();
    $fix = array();
    $ids = get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private' ), 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_pmi_source_url', 'suppress_filters' => true ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
    foreach ( $ids as $id ) {
        $path = untrailingslashit( (string) wp_parse_url( (string) get_post_meta( $id, '_pmi_source_url', true ), PHP_URL_PATH ) );
        if ( empty( $dates[ $path ] ) ) { continue; }
        $original = gmdate( 'Y-m-d H:i:s', strtotime( $dates[ $path ] ) );
        if ( get_post_field( 'post_date_gmt', $id ) !== $original ) { $fix[ $id ] = $original; }
    }
    return $fix;
}

function bh_pages_fix_post_dates() {
    $done = 0;
    foreach ( bh_pages_post_dates() as $id => $gmt ) {
        if ( ! current_user_can( 'edit_post', $id ) ) { continue; }
        $result = wp_update_post( array( 'ID' => $id, 'post_date_gmt' => $gmt, 'post_date' => get_date_from_gmt( $gmt ), 'edit_date' => true ), true );
        if ( ! is_wp_error( $result ) ) { ++$done; }
    }
    return $done;
}

/** Old menu/footer link targets that are not original addresses -> original addresses. */
function bh_pages_link_map() {
    return array(
        '/services' => '/production-services', '/faq' => '/general-5', '/privacy-policy' => '/general-5-1', '/terms-and-conditions' => '/general-5-2',
        '/film-studio' => '/film-studio', '/videos' => '/videos', '/production-services' => '/production-services', '/post-production' => '/post-production',
        '/event-space' => '/event-space', '/on-the-lot' => '/on-the-lot', '/location' => '/location', '/portfolio' => '/portfolio', '/about-us' => '/about-us',
        '/about-1' => '/about-1', '/ev-supercharger-locations' => '/ev-supercharger-locations', '/news' => '/news', '/contact' => '/contact',
    );
}

function bh_pages_fix_links_in( $content ) {
    return preg_replace_callback( '#((?:href=|"url":)")(/[a-z0-9-]+)/?(")#', function ( $match ) {
        $map = bh_pages_link_map();
        return isset( $map[ $match[2] ] ) ? $match[1] . $map[ $match[2] ] . $match[3] : $match[0];
    }, $content );
}

/** Saved (customized) header/footer parts and navigation menus whose links still need repair. */
function bh_pages_link_targets() {
    $posts = get_posts( array( 'post_type' => array( 'wp_template_part', 'wp_navigation' ), 'post_status' => array( 'publish', 'draft' ), 'numberposts' => -1, 'suppress_filters' => true ) );
    return array_filter( $posts, function ( $post ) {
        if ( 'wp_template_part' === $post->post_type && ! has_term( get_stylesheet(), 'wp_theme', $post ) ) { return false; }
        return bh_pages_fix_links_in( $post->post_content ) !== $post->post_content;
    } );
}

function bh_pages_fix_links() {
    $done = 0;
    foreach ( bh_pages_link_targets() as $post ) {
        if ( ! current_user_can( 'edit_theme_options' ) ) { break; }
        $result = wp_update_post( wp_slash( array( 'ID' => $post->ID, 'post_content' => bh_pages_fix_links_in( $post->post_content ) ) ), true );
        if ( ! is_wp_error( $result ) ) { ++$done; }
    }
    return $done;
}

add_action( 'admin_menu', function () {
    add_theme_page( 'Black Hangar Pages', 'Black Hangar Pages', 'edit_pages', 'bh-pages', 'bh_pages_screen' );
} );

add_action( 'admin_notices', function () {
    $screen = get_current_screen();
    if ( ! current_user_can( 'edit_pages' ) || ( $screen && 'appearance_page_bh-pages' === $screen->id ) ) { return; }
    $pending = 0;
    foreach ( bh_pages_manifest()['pages'] ?? array() as $page ) {
        if ( in_array( bh_pages_state( $page['slug'] )[1], array( 'missing', 'imported', 'outdated' ), true ) ) { ++$pending; }
    }
    if ( $pending ) {
        echo '<div class="notice notice-info"><p>' . esc_html( sprintf( 'Black Hangar: %d page designs are ready to apply.', $pending ) ) . ' <a href="' . esc_url( admin_url( 'themes.php?page=bh-pages' ) ) . '">Review and apply</a></p></div>';
    }
} );

function bh_pages_screen() {
    if ( ! current_user_can( 'edit_pages' ) ) { return; }
    $notices = array();
    if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['bh_pages_action'] ) ) {
        check_admin_referer( 'bh_pages' );
        $action = sanitize_key( wp_unslash( $_POST['bh_pages_action'] ) );
        if ( 'apply' === $action ) {
            $slugs = array_map( 'sanitize_title', (array) wp_unslash( $_POST['bh_pages'] ?? array() ) );
            foreach ( $slugs as $slug ) {
                $result = bh_pages_apply( $slug );
                $notices[] = array( is_wp_error( $result ) ? 'error' : 'success', $slug . ': ' . ( is_wp_error( $result ) ? $result->get_error_message() : 'design applied.' ) );
            }
            if ( ! $slugs ) { $notices[] = array( 'warning', 'No pages were selected.' ); }
        } elseif ( 0 === strpos( $action, 'restore-' ) ) {
            $slug = sanitize_title( substr( $action, 8 ) );
            $result = bh_pages_restore( $slug );
            $notices[] = array( is_wp_error( $result ) ? 'error' : 'success', $slug . ': ' . ( is_wp_error( $result ) ? $result->get_error_message() : 'previous content and template restored.' ) );
        } elseif ( 'dates' === $action && current_user_can( 'edit_others_posts' ) ) {
            $notices[] = array( 'success', sprintf( '%d blog posts now show their original publication dates.', bh_pages_fix_post_dates() ) );
        } elseif ( 'hero' === $action || 'hero-restore' === $action ) {
            $result = 'hero' === $action ? bh_add_hero_buttons() : bh_restore_home_before_hero_buttons();
            $notices[] = array( is_wp_error( $result ) ? 'error' : 'success', is_wp_error( $result ) ? $result->get_error_message() : ( 'hero' === $action ? 'Studio Video and Services buttons added to the homepage hero.' : 'Homepage restored to the version before the buttons were added.' ) );
        } elseif ( 'productions' === $action ) {
            $notices[] = array( 'success', sprintf( '%d productions added to Portfolio as drafts. Add a poster (Featured image) and year, then publish.', bh_add_new_productions() ) );
        } elseif ( 'links' === $action && current_user_can( 'edit_theme_options' ) ) {
            $notices[] = array( 'success', sprintf( '%d saved header, footer or menu items now link to the original addresses.', bh_pages_fix_links() ) );
        }
        $notices[] = array( 'info', 'If the website uses a cache (LiteSpeed Cache), purge it to see the changes.' );
    }
    $labels = array(
        'missing'  => 'Not on the website yet: will be created',
        'imported' => 'Imported content, not designed yet',
        'outdated' => 'Earlier design, unchanged since: update available',
        'current'  => 'Designed (current)',
        'edited'   => 'Designed, then edited on the website',
    );
    echo '<div class="wrap"><h1>Black Hangar Pages</h1>';
    foreach ( $notices as $notice ) { echo '<div class="notice notice-' . esc_attr( $notice[0] ) . '"><p>' . esc_html( $notice[1] ) . '</p></div>'; }
    echo '<p>Applies the new editable page designs to the pages at their original addresses. Titles, addresses, SEO settings and publication status stay the same; the homepage and News are never changed. Each page\'s previous content is saved and can be restored here. Edit the pages afterwards like any other page in <strong>Pages</strong>.</p>';
    echo '<form method="post">';
    wp_nonce_field( 'bh_pages' );
    echo '<table class="widefat striped" style="max-width:1100px"><thead><tr><td class="check-column"></td><th>Page</th><th>Address</th><th>Status</th><th></th></tr></thead><tbody>';
    foreach ( bh_pages_manifest()['pages'] ?? array() as $page ) {
        list( $id, $state ) = bh_pages_state( $page['slug'] );
        $checked = in_array( $state, array( 'missing', 'imported', 'outdated' ), true );
        $path = '/' . $page['slug'];
        echo '<tr><th class="check-column"><input type="checkbox" name="bh_pages[]" value="' . esc_attr( $page['slug'] ) . '"' . checked( $checked, true, false ) . '></th>';
        echo '<td><strong>' . esc_html( $page['label'] ) . '</strong>' . ( $id ? ' &middot; <a href="' . esc_url( get_edit_post_link( $id ) ) . '">Edit</a> &middot; <a href="' . esc_url( get_permalink( $id ) ) . '" target="_blank">View</a>' : '' ) . '</td>';
        echo '<td><code>' . esc_html( $path ) . '</code></td><td>' . esc_html( $labels[ $state ] ) . ( 'edited' === $state ? ' <em>(applying replaces those edits)</em>' : '' ) . '</td><td>';
        if ( $id && metadata_exists( 'post', $id, '_bh_design_backup' ) ) {
            echo '<button class="button-link" name="bh_pages_action" value="restore-' . esc_attr( $page['slug'] ) . '" onclick="return confirm(\'Restore the content this page had before the design was applied?\')">Restore previous</button>';
        }
        echo '</td></tr>';
    }
    echo '</tbody></table><p><button class="button button-primary" name="bh_pages_action" value="apply">Apply design to selected pages</button></p>';
    $dates = bh_pages_post_dates();
    echo '<h2>Blog publication dates</h2>';
    if ( $dates ) {
        echo '<p>' . esc_html( sprintf( '%d imported blog posts show the import day instead of their original publication date (for example, 2023).', count( $dates ) ) ) . '</p><p><button class="button" name="bh_pages_action" value="dates">Restore original publication dates</button></p>';
    } else {
        echo '<p>All imported blog posts show their original publication dates.</p>';
    }
    $links = bh_pages_link_targets();
    echo '<h2>Menu and footer links</h2>';
    if ( $links ) {
        echo '<p>' . esc_html( sprintf( '%d saved header, footer or menu items link to addresses that do not exist (such as /services or /faq). Only those links are changed, to the original addresses; text and design stay the same.', count( $links ) ) ) . '</p><p><button class="button" name="bh_pages_action" value="links">Repair links</button></p>';
    } else {
        echo '<p>Menu and footer links point to the original addresses.</p>';
    }
    echo '<h2>Homepage hero buttons</h2>';
    if ( 'saved' === bh_hero_buttons_state() ) {
        echo '<p>Adds <strong>Studio Video</strong> (the studio video tour) and <strong>Services</strong> (Production Services) next to Portfolio in the homepage hero. Nothing else on the homepage changes; the current homepage is saved first.</p><p><button class="button" name="bh_pages_action" value="hero">Add hero buttons</button></p>';
    } else {
        echo '<p>The homepage hero shows the Studio Video and Services buttons.</p>';
    }
    if ( false !== get_option( 'bh_home_before_hero_buttons', false ) ) {
        echo '<p><button class="button-link" name="bh_pages_action" value="hero-restore" onclick="return confirm(\'Restore the homepage as it was before the buttons were added?\')">Restore homepage without the buttons</button></p>';
    }
    $new = bh_new_productions_pending();
    echo '<h2>New portfolio productions</h2>';
    if ( $new ) {
        echo '<p>' . esc_html( 'Adds ' . implode( ', ', array_map( function ( $slug ) { return bh_new_productions()[ $slug ]['title']; }, $new ) ) . ' to Portfolio as drafts with the information from the archived sites (The Fallow Few: cast, story, trailer and a film still). Add posters and release years, then publish each one.' ) . '</p><p><button class="button" name="bh_pages_action" value="productions">Add productions as drafts</button></p>';
    } else {
        echo '<p>The Fallow Few, The Chair and Frost are in Portfolio (see Portfolio in the admin menu to finish and publish them).</p>';
    }
    echo '</form></div>';
}
