<?php
/** Apply only the two reviewed templates; keep global styles and theme mods intact. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', function () {
    add_theme_page( 'Black Hangar Page Designs', 'Black Hangar Page Designs', 'edit_theme_options', 'bh-page-designs', 'bh_page_designs_screen' );
} );

function bh_apply_page_designs() {
    if ( ! current_user_can( 'edit_theme_options' ) ) { return new WP_Error( 'permission', 'Permission required.' ); }
    $backup = get_option( 'bh_page_designs_backup', array() );
    $changes = get_option( 'bh_page_designs_ids', array() );
    foreach ( array( 'front-page', 'film-studio' ) as $slug ) {
        $source = get_theme_file_path( 'templates/' . $slug . '.html' );
        if ( ! is_readable( $source ) ) { return new WP_Error( 'source', 'Missing template: ' . $slug ); }
        $templates = get_posts( array( 'post_type' => 'wp_template', 'post_status' => array( 'publish', 'draft', 'auto-draft' ), 'name' => $slug, 'numberposts' => 1, 'tax_query' => array( array( 'taxonomy' => 'wp_theme', 'field' => 'slug', 'terms' => get_stylesheet() ) ) ) );
        $post = $templates ? $templates[0] : null;
        if ( ! array_key_exists( $slug, $backup ) ) {
            $backup[ $slug ] = $post ? array( 'content' => $post->post_content, 'title' => $post->post_title, 'status' => $post->post_status ) : null;
            update_option( 'bh_page_designs_backup', $backup, false );
        }
        $args = array( 'post_type' => 'wp_template', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => 'front-page' === $slug ? 'Front Page' : 'Film studio', 'post_content' => file_get_contents( $source ) );
        if ( $post ) { $args['ID'] = $post->ID; }
        $id = wp_insert_post( wp_slash( $args ), true );
        if ( is_wp_error( $id ) ) { return $id; }
        $terms = wp_set_object_terms( $id, get_stylesheet(), 'wp_theme' );
        if ( is_wp_error( $terms ) ) { return $terms; }
        $changes[ $slug ] = $id;
        update_option( 'bh_page_designs_ids', $changes, false );
    }
    update_option( 'bh_page_designs_ids', $changes, false );
    return true;
}

function bh_restore_page_designs() {
    if ( ! current_user_can( 'edit_theme_options' ) ) { return new WP_Error( 'permission', 'Permission required.' ); }
    $backup = get_option( 'bh_page_designs_backup', array() );
    $ids = get_option( 'bh_page_designs_ids', array() );
    foreach ( $ids as $slug => $id ) {
        $post = get_post( $id );
        if ( ! $post || 'wp_template' !== $post->post_type || $slug !== $post->post_name || ! has_term( get_stylesheet(), 'wp_theme', $id ) ) { continue; }
        if ( ! array_key_exists( $slug, $backup ) ) { continue; }
        if ( null === $backup[ $slug ] ) { wp_delete_post( $id, true ); }
        else {
            $result = wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $backup[ $slug ]['content'], 'post_title' => $backup[ $slug ]['title'], 'post_status' => $backup[ $slug ]['status'] ) ), true );
            if ( is_wp_error( $result ) ) { return $result; }
        }
    }
    return true;
}

function bh_page_designs_screen() {
    if ( ! current_user_can( 'edit_theme_options' ) ) { return; }
    $notice = '';
    if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['bh_design_action'] ) && is_string( $_POST['bh_design_action'] ) ) {
        check_admin_referer( 'bh_page_designs' );
        $action = sanitize_key( wp_unslash( $_POST['bh_design_action'] ) );
        $result = 'apply' === $action ? bh_apply_page_designs() : ( 'restore' === $action ? bh_restore_page_designs() : new WP_Error( 'action', 'Unknown action.' ) );
        $notice = is_wp_error( $result ) ? $result->get_error_message() : ( 'apply' === $action ? 'Both page designs applied. Clear your website cache.' : 'Previous saved template content restored. Theme files remain updated.' );
    }
    echo '<div class="wrap"><h1>Black Hangar Page Designs</h1>';
    if ( $notice ) { echo '<div class="notice notice-info"><p>' . esc_html( $notice ) . '</p></div>'; }
    echo '<p>Apply the reviewed Homepage and Film Studio templates. Existing saved edits to these two templates will be replaced and backed up. Additional CSS, global styles, site title, logo, header, footer, menus and page content are not changed.</p><p>After applying, assign the Film studio template to your existing Film Studio page. Replace pictures and the hero video in the Site Editor.</p><form method="post">';
    wp_nonce_field( 'bh_page_designs' );
    echo '<button class="button button-primary" name="bh_design_action" value="apply">Apply both page designs</button> ';
    if ( get_option( 'bh_page_designs_backup', false ) !== false ) { echo '<button class="button" name="bh_design_action" value="restore">Restore previous saved templates</button>'; }
    echo '</form><p>Before uploading a theme update, export your current theme from the Site Editor and back up your site. A restore here cannot restore files from the old theme ZIP.</p></div>';
}

