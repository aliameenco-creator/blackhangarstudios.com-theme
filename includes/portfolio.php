<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Accept only YouTube identifiers, never arbitrary iframe HTML or URLs. */
function bh_youtube_id( $value ) {
    if ( ! is_string( $value ) ) { return ''; }
    $value = trim( $value );
    if ( preg_match( '/^[A-Za-z0-9_-]{11}$/D', $value ) ) { return $value; }
    $url = wp_parse_url( $value );
    if ( ! is_array( $url ) || empty( $url['host'] ) || ! in_array( strtolower( $url['scheme'] ?? '' ), array( 'http', 'https' ), true ) || isset( $url['user'] ) || isset( $url['pass'] ) ) { return ''; }
    $host = strtolower( $url['host'] );
    $path = trim( $url['path'] ?? '', '/' );
    $id = '';
    if ( in_array( $host, array( 'youtu.be', 'www.youtu.be' ), true ) ) { $id = $path; }
    elseif ( in_array( $host, array( 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com' ), true ) ) {
        if ( 'watch' === $path ) { parse_str( $url['query'] ?? '', $query ); $id = $query['v'] ?? ''; }
        elseif ( preg_match( '#^(?:embed|shorts)/([A-Za-z0-9_-]{11})$#D', $path, $match ) ) { $id = $match[1]; }
    }
    return is_string( $id ) && preg_match( '/^[A-Za-z0-9_-]{11}$/D', $id ) ? $id : '';
}
add_action( 'add_meta_boxes_bh_portfolio', function () {
    add_meta_box( 'bh-project', __( 'Production details', 'black-hangar' ), 'bh_project_fields', 'bh_portfolio', 'normal', 'high' );
} );
function bh_project_fields( $post ) {
    wp_nonce_field( 'bh_project_save', 'bh_project_nonce' );
    $fields = array( 'bh_year' => 'Release year', 'bh_trailer' => 'YouTube trailer URL', 'bh_order' => 'Display order (lower first)' );
    foreach ( $fields as $key => $label ) {
        $number = 'bh_trailer' !== $key;
        echo '<p><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><br><input class="widefat" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="' . ( $number ? 'number' : 'url' ) . '" value="' . esc_attr( get_post_meta( $post->ID, $key, true ) ) . '"' . ( 'bh_year' === $key ? ' min="1888" max="2200"' : '' ) . '></p>';
    }
    echo '<p><label>Format <select name="bh_format">';
    foreach ( array( 'film' => 'Film', 'tv' => 'TV', 'commercial' => 'Commercial', 'other' => 'Other' ) as $key => $label ) {
        echo '<option value="' . esc_attr( $key ) . '" ' . selected( get_post_meta( $post->ID, 'bh_format', true ), $key, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select></label></p><p><label><input type="checkbox" name="bh_home" value="1" ' . checked( get_post_meta( $post->ID, 'bh_home', true ), '1', false ) . '> Show in homepage poster grid</label></p><p>Use Featured image for the poster and the main editor for description / cast. Only valid YouTube links are accepted. SEO fields appear in Yoast.</p>';
}
add_action( 'save_post_bh_portfolio', 'bh_save_project' );
function bh_save_project( $post_id ) {
    if ( ! isset( $_POST['bh_project_nonce'] ) || ! is_string( $_POST['bh_project_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bh_project_nonce'] ) ), 'bh_project_save' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) { return; }
    $input = function ( $key ) { return isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; };
    $year = absint( $input( 'bh_year' ) );
    update_post_meta( $post_id, 'bh_year', $year >= 1888 && $year <= 2200 ? $year : '' );
    $video = bh_youtube_id( $input( 'bh_trailer' ) );
    update_post_meta( $post_id, 'bh_trailer', $video ? 'https://www.youtube.com/watch?v=' . $video : '' );
    $order = max( -9999, min( 9999, intval( $input( 'bh_order' ) ) ) );
    update_post_meta( $post_id, 'bh_order', $order );
    remove_action( 'save_post_bh_portfolio', 'bh_save_project' );
    wp_update_post( array( 'ID' => $post_id, 'menu_order' => $order ) );
    add_action( 'save_post_bh_portfolio', 'bh_save_project' );
    update_post_meta( $post_id, 'bh_home', '1' === $input( 'bh_home' ) ? '1' : '0' );
    $format = $input( 'bh_format' );
    update_post_meta( $post_id, 'bh_format', in_array( $format, array( 'film', 'tv', 'commercial', 'other' ), true ) ? $format : 'film' );
}
function bh_project_card( $id ) {
    $title = get_the_title( $id );
    $image = get_the_post_thumbnail( $id, 'large', array( 'loading' => 'lazy', 'alt' => $title ) );
    if ( ! $image ) { $image = '<span class="bh-poster-placeholder">' . esc_html( $title ) . '</span>'; }
    return '<article class="bh-card"><a href="' . esc_url( get_permalink( $id ) ) . '"><span class="bh-poster">' . $image . '</span><h3>' . esc_html( $title ) . '</h3><p>' . esc_html( get_post_meta( $id, 'bh_year', true ) ) . '</p></a></article>';
}
function bh_render_portfolio( $attributes ) {
    $mode = $attributes['mode'] ?? 'rows';
    $home = ! empty( $attributes['homeOnly'] );
    $args = array( 'post_type' => 'bh_portfolio', 'post_status' => 'publish', 'posts_per_page' => 'grid' === $mode ? 12 : 40, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'DESC' ), 'post__not_in' => is_singular( 'bh_portfolio' ) ? array( get_the_ID() ) : array() );
    if ( $home ) { $args['meta_query'] = array( array( 'key' => 'bh_home', 'value' => '1' ) ); }
    if ( 'grid' === $mode ) { $args['paged'] = max( 1, absint( get_query_var( 'paged' ) ), absint( get_query_var( 'page' ) ) ); }
    $query = new WP_Query( $args );
    if ( ! $query->have_posts() ) { return current_user_can( 'edit_posts' ) ? '<p class="bh-empty">Add published projects in Portfolio. Set a poster and enable the homepage checkbox to display them here.</p>' : ''; }
    $posts = $query->posts;
    $cards = array_map( function ( $post ) { return bh_project_card( $post->ID ); }, $posts );
    // Existing saved homepage blocks keep working without replacing their templates.
    if ( $home && 'rows' === $mode ) {
        return '<div class="bh-grid bh-home-posters" aria-label="Productions">' . implode( '', $cards ) . '</div>';
    }
    if ( 'grid' === $mode ) {
        return '<div class="bh-grid">' . implode( '', $cards ) . '</div><nav aria-label="Portfolio pages" class="bh-pagination">' . wp_kses_post( paginate_links( array( 'total' => $query->max_num_pages, 'current' => $args['paged'] ) ) ) . '</nav>';
    }
    $rows = ! empty( $attributes['twoRows'] ) ? 2 : 1;
    $html = '<section class="bh-marquee" aria-label="Productions"><button type="button" class="bh-pause" aria-pressed="false">Pause moving posters</button>';
    for ( $row = 0; $row < $rows; $row++ ) {
        $part = $rows > 1 && count( $cards ) > 1 ? array_values( array_filter( $cards, function ( $card, $index ) use ( $row ) { return $index % 2 === $row; }, ARRAY_FILTER_USE_BOTH ) ) : $cards;
        $html .= '<div class="bh-row' . ( $row ? ' bh-reverse' : '' ) . '"><div class="bh-track"><div class="bh-set">' . implode( '', $part ) . '</div><div class="bh-set bh-clone" aria-hidden="true" inert>' . implode( '', $part ) . '</div></div></div>';
    }
    return $html . '</section>';
}
function bh_render_project( $attributes, $content, $block ) {
    static $rendering = false;
    if ( $rendering ) { return ''; }
    $id = absint( $block->context['postId'] ?? get_the_ID() );
    if ( 'bh_portfolio' !== get_post_type( $id ) ) { return ''; }
    $video = bh_youtube_id( get_post_meta( $id, 'bh_trailer', true ) );
    $year = get_post_meta( $id, 'bh_year', true );
    $html = '<div class="bh-project-layout"><div class="bh-project-poster">' . get_the_post_thumbnail( $id, 'large' ) . '</div><div><p class="bh-eyebrow">' . esc_html( get_post_meta( $id, 'bh_format', true ) . ( $year ? ' / ' . $year : '' ) ) . '</p>';
    $rendering = true;
    try {
        $copy = apply_filters( 'the_content', get_post_field( 'post_content', $id ) );
    } finally { $rendering = false; }
    $html .= '<h1>' . esc_html( get_the_title( $id ) ) . '</h1><div class="bh-project-copy">' . $copy . '</div>';
    $html .= '</div></div>';
    if ( $video ) {
        $html .= '<section class="bh-trailer" aria-label="Production trailer"><h2>Watch the Trailer</h2><div class="bh-video"><iframe src="https://www.youtube-nocookie.com/embed/' . esc_attr( $video ) . '" title="' . esc_attr( get_the_title( $id ) . ' — trailer' ) . '" loading="lazy" allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div></section>';
    }
    return $html;
}
