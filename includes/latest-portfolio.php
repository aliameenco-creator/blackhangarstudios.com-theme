<?php
/** Compact, automatically refreshed homepage portfolio selection. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function bh_render_latest_portfolio() {
    $query = new WP_Query( array( 'post_type' => 'bh_portfolio', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ), 'no_found_rows' => true ) );
    if ( ! $query->posts ) { return current_user_can( 'edit_posts' ) ? '<p>No published productions yet.</p>' : ''; }
    return '<div class="bh-latest-scroll" tabindex="0" role="region" aria-label="Latest productions — scroll horizontally on smaller screens"><div class="bh-latest-posters">' . implode( '', array_map( function ( $post ) { return bh_project_card( $post->ID ); }, $query->posts ) ) . '</div></div>';
}
