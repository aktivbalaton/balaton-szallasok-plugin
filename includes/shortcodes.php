<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Szűrő form shortcode
function bsza_filter_shortcode( $atts ) {
    ob_start();
    include BSZA_PATH . 'templates/filter-template.php';
    return ob_get_clean();
}
add_shortcode( 'balaton_szallasok_filter', 'bsza_filter_shortcode' );

// Szálláslista shortcode
function bsza_lista_shortcode( $atts ) {
    ob_start();
    include BSZA_PATH . 'templates/lista-template.php';
    return ob_get_clean();
}
add_shortcode( 'balaton_szallasok_lista', 'bsza_lista_shortcode' );
