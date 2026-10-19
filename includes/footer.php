<?php
/**
 * Footer details applied when the page is shown, so they also apply to a footer saved in the Site Editor:
 * the copyright line always shows the current year, and the former "A project by ORWO Studio" credit is not shown.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'render_block_core/paragraph', function ( $html ) {
    if ( false !== strpos( $html, 'orwo.studio' ) && false !== stripos( $html, 'A project by' ) ) { return ''; }
    if ( false === strpos( $html, '©' ) || false === strpos( $html, 'Black Hangar Studios' ) ) { return $html; }
    $year = wp_date( 'Y' );
    // "© Black Hangar Studios" or "© 2025 Black Hangar Studios" -> "© <current year> Black Hangar Studios".
    return preg_replace( '/©\s*(?:(?:19|20)\d{2}\s*)?(?=Black Hangar Studios)/u', '© ' . $year . ' ', $html, 1 );
} );
