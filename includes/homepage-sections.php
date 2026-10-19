<?php
/** Targeted additions to saved templates, with backups; no homepage reset. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function bh_section_source( $file ) {
    $source = file_get_contents( get_theme_file_path( 'patterns/' . $file . '.php' ) );
    return trim( substr( $source, strpos( $source, '?>' ) + 2 ) );
}
function bh_append_trust_sections( $content ) {
    $addition = '';
    foreach ( array( 'bh-client-testimonials' => 'client-testimonials', 'bh-press-mentions' => 'press-mentions' ) as $marker => $file ) {
        if ( false === strpos( $content, $marker ) ) { $addition .= "\n" . bh_section_source( $file ); }
    }
    if ( ! $addition ) { return $content; }
    $position = strrpos( $content, '</main>' );
    if ( false === $position ) { return new WP_Error( 'structure', 'The homepage has no main wrapper. Insert the Client testimonials and Press patterns manually in the Site Editor.' ); }
    return substr( $content, 0, $position ) . $addition . "\n" . substr( $content, $position );
}
function bh_refresh_trust_blocks( $blocks ) {
    foreach ( $blocks as &$block ) {
        $classes = explode( ' ', $block['attrs']['className'] ?? '' );
        foreach ( array( 'bh-client-testimonials' => 'client-testimonials', 'bh-press-mentions' => 'press-mentions' ) as $marker => $file ) {
            if ( in_array( $marker, $classes, true ) ) {
                $replacement = parse_blocks( bh_section_source( $file ) );
                $block = $replacement[0];
                continue 2;
            }
        }
        if ( ! empty( $block['innerBlocks'] ) ) { $block['innerBlocks'] = bh_refresh_trust_blocks( $block['innerBlocks'] ); }
    }
    unset( $block );
    return $blocks;
}
function bh_apply_targeted_section( $footer = false, $refresh = false ) {
    if ( ! current_user_can( 'edit_theme_options' ) ) { return new WP_Error( 'permission', 'Permission required.' ); }
    $slug = $footer ? 'footer' : 'front-page'; $type = $footer ? 'wp_template_part' : 'wp_template';
    $template = get_block_template( get_stylesheet() . '//' . $slug, $type );
    if ( ! $template ) { return new WP_Error( 'template', 'Template unavailable; insert the patterns manually.' ); }
    $original_content = $template->content;
    if ( $refresh ) { $template->content = serialize_blocks( bh_refresh_trust_blocks( parse_blocks( $template->content ) ) ); }
    $content = $footer ? bh_section_source( 'studio-footer' ) : bh_append_trust_sections( $template->content );
    if ( is_wp_error( $content ) ) { return $content; }
    if ( $content === $original_content ) { return true; }
    $backup = get_option( 'bh_targeted_section_backups', array() );
    if ( ! isset( $backup[ $slug ] ) ) { $backup[ $slug ] = array( 'content' => $original_content, 'type' => $type ); update_option( 'bh_targeted_section_backups', $backup, false ); }
    $args = array( 'post_type' => $type, 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $template->title, 'post_content' => $content );
    if ( ! empty( $template->wp_id ) ) { $args['ID'] = $template->wp_id; }
    $id = wp_insert_post( wp_slash( $args ), true );
    if ( is_wp_error( $id ) ) { return $id; }
    $terms = wp_set_object_terms( $id, get_stylesheet(), 'wp_theme' );
    if ( is_wp_error( $terms ) ) { return $terms; }
    if ( $footer ) { wp_set_object_terms( $id, 'footer', 'wp_template_part_area' ); }
    return true;
}
add_action( 'admin_menu', function () { add_theme_page( 'Homepage & Footer', 'Homepage & Footer', 'edit_theme_options', 'bh-homepage-sections', 'bh_homepage_sections_screen' ); } );
function bh_homepage_sections_screen() {
    if ( ! current_user_can( 'edit_theme_options' ) ) { return; }
    $notice = '';
    if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
        check_admin_referer( 'bh_targeted_sections' );
        $action = sanitize_key( wp_unslash( $_POST['bh_action'] ?? '' ) );
        $result = in_array( $action, array( 'home', 'footer', 'refresh' ), true ) ? bh_apply_targeted_section( 'footer' === $action, 'refresh' === $action ) : new WP_Error( 'action', 'Unknown action.' );
        $notice = is_wp_error( $result ) ? $result->get_error_message() : 'Saved. Review the result in the Site Editor and clear your site cache.';
    }
    echo '<div class="wrap"><h1>Homepage &amp; Footer</h1>';
    if ( $notice ) { echo '<div class="notice notice-info"><p>' . esc_html( $notice ) . '</p></div>'; }
    echo '<p>Adds editable testimonials and press mentions to the end of your saved homepage main content. Existing hero, logos, portfolio, typography and content are preserved. Repeated clicks do not duplicate sections. Move the new Groups in List View if desired.</p><p>The footer button replaces only the footer with the new logo/contact/social layout and ORWO Studio credit. Previous template content is backed up in the WordPress option bh_targeted_section_backups.</p><form method="post">';
    wp_nonce_field( 'bh_targeted_sections' );
    echo '<button class="button button-primary" name="bh_action" value="home">Add missing homepage sections</button> <button class="button" name="bh_action" value="refresh">Update testimonial and press sections</button> <button class="button" name="bh_action" value="footer">Apply improved footer</button></form><p>Alternatively, insert the Client testimonials, Press and media mentions, or Studio footer patterns in the Site Editor. The update button replaces only the testimonial and press Groups with the four supplied testimonials and current press layout. Other homepage blocks stay intact. All quotes remain editable in List View.</p></div>';
}
