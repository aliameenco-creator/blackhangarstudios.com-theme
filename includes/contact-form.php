<?php
/**
 * Contact form recreated from the original Wix form (First Name, Last Name, Email*, Message, "Send",
 * "Thanks for submitting!"), placed with the [bh_contact_form] shortcode. Submissions are saved as private Enquiries
 * in wp-admin and emailed to the address under Settings -> General. No third-party service is used.
 * Also provides [bh_map] (Google Maps for the studio address) and [bh_virtual_tour] (the original CloudPano 360 tour).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bh_contact_fields() {
    return array(
        array( 'key' => 'first_name', 'label' => 'First Name', 'type' => 'text', 'required' => false, 'max' => 100 ),
        array( 'key' => 'last_name', 'label' => 'Last Name', 'type' => 'text', 'required' => false, 'max' => 100 ),
        array( 'key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'max' => 250 ),
        array( 'key' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => false, 'max' => 5000 ),
    );
}

function bh_contact_recipient() {
    $email = sanitize_email( (string) get_option( 'bh_contact_recipient', '' ) );
    return is_email( $email ) ? $email : 'sales@blackhangarstudios.com';
}

add_action( 'init', function () {
    register_post_type( 'bh_enquiry', array(
        'labels'          => array( 'name' => 'Enquiries', 'singular_name' => 'Enquiry', 'menu_name' => 'Enquiries', 'all_items' => 'All enquiries', 'edit_item' => 'Enquiry' ),
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'show_in_rest'    => false,
        'menu_icon'       => 'dashicons-email-alt',
        'menu_position'   => 26,
        'supports'        => array( 'title' ),
        'capability_type' => 'post',
        'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
        'map_meta_cap'    => true,
    ) );
} );

add_action( 'admin_init', function () {
    register_setting( 'general', 'bh_contact_recipient', array( 'type' => 'string', 'sanitize_callback' => 'sanitize_email', 'default' => '' ) );
    add_settings_field( 'bh_contact_recipient', 'Contact form recipient', function () {
        echo '<input type="email" class="regular-text" name="bh_contact_recipient" value="' . esc_attr( get_option( 'bh_contact_recipient', '' ) ) . '" placeholder="sales@blackhangarstudios.com"><p class="description">Contact page messages are emailed here and always saved under Enquiries. Leave empty for sales@blackhangarstudios.com.</p>';
    }, 'general' );
} );

add_action( 'add_meta_boxes_bh_enquiry', function () {
    add_meta_box( 'bh_enquiry_fields', 'Submitted details', function ( $post ) {
        $values = (array) get_post_meta( $post->ID, '_bh_fields', true );
        echo '<table class="widefat striped"><tbody>';
        foreach ( bh_contact_fields() as $field ) {
            echo '<tr><th style="width:180px">' . esc_html( $field['label'] ) . '</th><td style="white-space:pre-wrap">' . esc_html( $values[ $field['key'] ] ?? '' ) . '</td></tr>';
        }
        echo '<tr><th>Email notification</th><td>' . esc_html( get_post_meta( $post->ID, '_bh_mail', true ) ?: 'unknown' ) . '</td></tr><tr><th>Page</th><td>' . esc_html( get_post_meta( $post->ID, '_bh_page', true ) ) . '</td></tr></tbody></table>';
    }, 'bh_enquiry', 'normal', 'high' );
} );
add_filter( 'manage_bh_enquiry_posts_columns', function ( $columns ) {
    return array( 'cb' => $columns['cb'], 'title' => 'Name / Email', 'bh_mail' => 'Email sent', 'date' => 'Received' );
} );
add_action( 'manage_bh_enquiry_posts_custom_column', function ( $column, $id ) {
    if ( 'bh_mail' === $column ) { echo esc_html( get_post_meta( $id, '_bh_mail', true ) ); }
}, 10, 2 );

function bh_form_respond( $ok, $message, $errors = array(), $values = array() ) {
    if ( str_contains( (string) ( $_SERVER['HTTP_ACCEPT'] ?? '' ), 'application/json' ) ) {
        wp_send_json( array( 'ok' => $ok, 'message' => $message, 'errors' => $errors ), $ok ? 200 : 422 );
    }
    $back = wp_get_referer() ? wp_get_referer() : home_url( '/contact' );
    $back = remove_query_arg( array( 'bh_sent', 'bh_token' ), $back );
    if ( $ok ) {
        wp_safe_redirect( add_query_arg( 'bh_sent', '1', $back ) . '#message' );
        exit;
    }
    $token = wp_generate_password( 20, false );
    set_transient( 'bh_form_' . $token, array( 'message' => $message, 'errors' => $errors, 'values' => $values ), 10 * MINUTE_IN_SECONDS );
    wp_safe_redirect( add_query_arg( 'bh_token', $token, $back ) . '#message' );
    exit;
}

function bh_handle_contact_form() {
    $thanks = 'Thanks for submitting!';
    $values = array();
    $errors = array();
    foreach ( bh_contact_fields() as $field ) {
        $raw = isset( $_POST[ 'bh_' . $field['key'] ] ) ? wp_unslash( $_POST[ 'bh_' . $field['key'] ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidationSanitization.InputNotSanitized -- Public form; sanitised below and protected by the checks that follow.
        $value = 'textarea' === $field['type'] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
        $values[ $field['key'] ] = $value;
        if ( $field['required'] && '' === trim( $value ) ) { $errors[ $field['key'] ] = $field['label'] . ' is required.'; }
        elseif ( 'email' === $field['type'] && '' !== $value && ! is_email( $value ) ) { $errors[ $field['key'] ] = 'Please enter a valid email address.'; }
        elseif ( mb_strlen( $value ) > $field['max'] ) { $errors[ $field['key'] ] = $field['label'] . ' is too long (maximum ' . $field['max'] . ' characters).'; }
    }
    // Spam protection that survives page caching: honeypot, minimum time on page (JavaScript), per-visitor rate limit.
    if ( ! empty( $_POST['bh_website'] ) ) { bh_form_respond( true, $thanks ); } // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $elapsed = isset( $_POST['bh_elapsed'] ) ? (int) $_POST['bh_elapsed'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( $elapsed < 3000 ) {
        bh_form_respond( false, 'Your message could not be verified. Please wait a moment and submit again, or email sales@blackhangarstudios.com.', array(), $values );
    }
    $visitor = 'bh_rate_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() );
    $count = (int) get_transient( $visitor );
    if ( $count >= 5 ) { bh_form_respond( false, 'Too many messages in a short time. Please try again later or email sales@blackhangarstudios.com.', array(), $values ); }
    if ( $errors ) { bh_form_respond( false, 'Please check the highlighted fields.', $errors, $values ); }
    set_transient( $visitor, $count + 1, 10 * MINUTE_IN_SECONDS );

    $page = esc_url_raw( (string) wp_get_referer() );
    $name = trim( $values['first_name'] . ' ' . $values['last_name'] );
    $id = wp_insert_post( array( 'post_type' => 'bh_enquiry', 'post_status' => 'private', 'post_title' => '' !== $name ? $name . ' (' . $values['email'] . ')' : $values['email'] ), true );
    if ( ! is_wp_error( $id ) ) {
        update_post_meta( $id, '_bh_fields', $values );
        update_post_meta( $id, '_bh_page', $page );
    }
    $lines = array();
    foreach ( bh_contact_fields() as $field ) { $lines[] = $field['label'] . ': ' . ( '' === $values[ $field['key'] ] ? '-' : $values[ $field['key'] ] ); }
    $lines[] = '';
    $lines[] = 'Sent from: ' . $page;
    $reply = '' !== $name ? str_replace( array( "\r", "\n", '<', '>' ), '', $name ) . ' <' . $values['email'] . '>' : $values['email'];
    $sent = wp_mail( bh_contact_recipient(), 'Black Hangar website enquiry from ' . ( '' !== $name ? $name : $values['email'] ), implode( "\n", $lines ), array( 'Reply-To: ' . $reply ) );
    if ( ! is_wp_error( $id ) ) { update_post_meta( $id, '_bh_mail', $sent ? 'sent to ' . bh_contact_recipient() : 'FAILED - check site email settings' ); }
    bh_form_respond( true, $thanks );
}
add_action( 'admin_post_nopriv_bh_contact', 'bh_handle_contact_form' );
add_action( 'admin_post_bh_contact', 'bh_handle_contact_form' );

add_shortcode( 'bh_contact_form', function () {
    $state = array( 'message' => '', 'errors' => array(), 'values' => array() );
    if ( isset( $_GET['bh_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $saved = get_transient( 'bh_form_' . sanitize_key( wp_unslash( $_GET['bh_token'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( is_array( $saved ) ) { $state = $saved; }
    }
    $uid = wp_unique_id( 'bh-form-' );
    $html = '<form class="bh-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" novalidate data-bh-form>';
    $html .= '<div class="form-status" role="status" aria-live="polite">';
    if ( isset( $_GET['bh_sent'] ) ) { $html .= '<p class="form-success">Thanks for submitting!</p>'; } // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    elseif ( $state['message'] ) { $html .= '<p class="form-error">' . esc_html( $state['message'] ) . '</p>'; }
    $html .= '</div><input type="hidden" name="action" value="bh_contact"><input type="hidden" name="bh_elapsed" value="" data-elapsed>';
    $html .= '<div class="hp" aria-hidden="true"><label>Website <input type="text" name="bh_website" tabindex="-1" autocomplete="off"></label></div><div class="form-grid">';
    $auto = array( 'first_name' => 'given-name', 'last_name' => 'family-name', 'email' => 'email' );
    foreach ( bh_contact_fields() as $field ) {
        $id = $uid . '-' . $field['key'];
        $error = $state['errors'][ $field['key'] ] ?? '';
        $value = $state['values'][ $field['key'] ] ?? '';
        $attributes = ' id="' . esc_attr( $id ) . '" name="bh_' . esc_attr( $field['key'] ) . '" maxlength="' . (int) $field['max'] . '"' . ( $field['required'] ? ' required aria-required="true"' : '' ) . ( $error ? ' aria-invalid="true"' : '' ) . ( isset( $auto[ $field['key'] ] ) ? ' autocomplete="' . esc_attr( $auto[ $field['key'] ] ) . '"' : '' );
        $html .= '<div class="field' . ( in_array( $field['type'], array( 'email', 'textarea' ), true ) ? ' wide' : '' ) . '"><label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . ( $field['required'] ? ' <span class="req" aria-hidden="true">*</span>' : '' ) . '</label>';
        $html .= 'textarea' === $field['type'] ? '<textarea rows="6"' . $attributes . '>' . esc_textarea( $value ) . '</textarea>' : '<input type="' . esc_attr( $field['type'] ) . '" value="' . esc_attr( $value ) . '"' . $attributes . '>';
        $html .= '<span class="field-error">' . esc_html( $error ) . '</span></div>';
    }
    $html .= '</div><div class="form-actions"><button type="submit">Send</button></div>';
    $html .= '<noscript><p class="form-error">Please enable JavaScript to send this form, or email <a href="mailto:sales@blackhangarstudios.com">sales@blackhangarstudios.com</a>.</p></noscript></form>';
    return $html;
} );

add_shortcode( 'bh_map', function () {
    $query = rawurlencode( 'Black Hangar Studios, Lasham Airfield, Alton GU34 5SR' );
    return '<div class="bh-embed bh-map"><iframe src="https://maps.google.com/maps?q=' . esc_attr( $query ) . '&amp;z=13&amp;output=embed" title="Map: Black Hangar Studios, Lasham Airfield, Alton GU34 5SR" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>';
} );

add_shortcode( 'bh_virtual_tour', function () {
    return '<div class="bh-embed bh-tour"><iframe src="https://app.cloudpano.com/tours/q3lH-T05k" title="Black Hangar Studios 360 virtual tour" loading="lazy" allow="xr-spatial-tracking; accelerometer; gyroscope; fullscreen" allowfullscreen></iframe></div>';
} );
