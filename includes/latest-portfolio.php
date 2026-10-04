<?php
/** Full-width, automatically refreshed opposing portfolio rows. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function bh_render_latest_portfolio() {
    $query = new WP_Query( array( 'post_type' => 'bh_portfolio', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ), 'no_found_rows' => true ) );
    if ( ! $query->posts ) { return current_user_can( 'edit_posts' ) ? '<p>No published productions yet.</p>' : ''; }
    $html = '<section class="bh-marquee bh-latest-scroll" aria-label="Latest productions"><button type="button" class="bh-pause" aria-pressed="false">Pause moving posters</button>';
    for ( $row = 0; $row < 2; $row++ ) {
        $posts = count( $query->posts ) > 1 ? array_values( array_filter( $query->posts, function ( $post, $index ) use ( $row ) { return $index % 2 === $row; }, ARRAY_FILTER_USE_BOTH ) ) : $query->posts;
        $cards = array_map( function ( $post ) { return bh_project_card( $post->ID ); }, $posts );
        $copy = implode( '', $cards );
        $filler = str_replace( array( '<article ', '<a ' ), array( '<article aria-hidden="true" ', '<a tabindex="-1" ' ), $copy );
        $repeats = max( 1, (int) ceil( 24 / count( $cards ) ) );
        $set = $copy . '<span class="bh-loop-filler">' . str_repeat( $filler, $repeats - 1 ) . '</span>';
        $clone = str_replace( '<a ', '<a tabindex="-1" ', str_replace( '<a tabindex="-1" ', '<a ', $set ) );
        $duration = count( $cards ) * $repeats * 8;
        $html .= '<div class="bh-latest-row"><div class="bh-latest-track' . ( $row ? ' bh-latest-reverse' : '' ) . '" style="--bh-latest-duration:' . esc_attr( $duration ) . 's"><div class="bh-latest-set">' . $set . '</div><div class="bh-latest-set bh-latest-clone" aria-hidden="true">' . $clone . '</div></div></div>';
    }
    return $html . '</section>';
}
