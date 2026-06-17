<?php
/**
 * Plugin Name: Balaton Szállások
 * Plugin URI:
 * Description: Balatoni szállások kezelése és megjelenítése – ACF-mentes, önálló plugin.
 * Version: 3.2.1
 * Author: aktivbalaton.hu
 * Text Domain: balaton-szallasok
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$bsza_plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
define( 'BSZA_VERSION', $bsza_plugin_data['Version'] );
define( 'BSZA_PATH', plugin_dir_path( __FILE__ ) );
define( 'BSZA_URL',  plugin_dir_url( __FILE__ ) );

// ── Gutenberg kikapcsolása – LEGELSŐ, minden require_once előtt ───────────────
add_filter( 'use_block_editor_for_post_type', function( $use, $post_type ) {
    if ( $post_type === 'szallasok' ) return false;
    return $use;
}, 10, 2 );

// ── Egyedi mezők panel elrejtése ──────────────────────────────────────────────
add_action( 'add_meta_boxes', function() {
    remove_meta_box( 'postcustom', 'szallasok', 'normal' );
}, 99 );

// ── Modulok betöltése ─────────────────────────────────────────────────────────
require_once BSZA_PATH . 'includes/cpt.php';
require_once BSZA_PATH . 'includes/meta-boxes.php';
require_once BSZA_PATH . 'includes/shortcodes.php';
require_once BSZA_PATH . 'includes/ajax-handlers.php';
require_once BSZA_PATH . 'includes/rest-api.php';

if ( file_exists( BSZA_PATH . 'includes/settings.php' ) ) {
    require_once BSZA_PATH . 'includes/settings.php';
}

// ── Egyedi szállás oldal sablon (plugin-ból) ──────────────────────────────────
add_filter( 'single_template', function( $template ) {
    global $post;
    if ( $post && $post->post_type === 'szallasok' ) {
        $plugin_template = BSZA_PATH . 'templates/single-szallasok.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }
    return $template;
} );

// ── Frontend eszközök ──────────────────────────────────────────────────────────
function bsza_enqueue_frontend_assets() {
    $api_key = get_option( 'bsza_google_maps_api_key', '' );

    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
        array(), '6.5.0'
    );
    wp_enqueue_style(
        'balaton-szallasok-frontend',
        BSZA_URL . 'assets/css/frontend.css',
        array(), BSZA_VERSION
    );
    wp_enqueue_script( 'jquery' );

    // Google Maps – csak akkor töltjük be, ha más plugin még nem töltötte be
    // és van API kulcs megadva
    if ( $api_key && ! wp_script_is( 'google-maps', 'enqueued' ) && ! wp_script_is( 'google-maps', 'done' ) ) {
        global $wp_scripts;
        $maps_already_loaded = false;
        if ( isset( $wp_scripts->registered ) ) {
            foreach ( $wp_scripts->registered as $handle => $script ) {
                if ( isset( $script->src ) && strpos( $script->src, 'maps.googleapis.com' ) !== false ) {
                    $maps_already_loaded = true;
                    break;
                }
            }
        }

        if ( ! $maps_already_loaded ) {
            wp_enqueue_script(
                'bsza-google-maps',
                'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&callback=bszaInitMap&loading=async',
                array(), null, true
            );
        }
    }

    wp_enqueue_script(
        'balaton-szallasok-frontend',
        BSZA_URL . 'assets/js/frontend.js',
        array( 'jquery' ), BSZA_VERSION, true
    );
    wp_localize_script( 'balaton-szallasok-frontend', 'szallasokAjax', array(
        'ajaxurl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'szallasok_nonce' ),
        'hasMapKey' => ! empty( $api_key ) ? '1' : '0',
        'mapCenter' => array( 'lat' => 46.8389, 'lng' => 17.8868 ),
        'mapZoom'   => 10,
    ) );
}
add_action( 'wp_enqueue_scripts', 'bsza_enqueue_frontend_assets' );

// ── Térkép cache törlése publish / trash / delete esetén ──────────────────────────
add_action( 'publish_szallasok', function() { delete_transient( 'bsza_terkep_adatok' ); } );
add_action( 'trash_szallasok',   function() { delete_transient( 'bsza_terkep_adatok' ); } );
add_action( 'delete_post',       function( $post_id ) {
    if ( get_post_type( $post_id ) === 'szallasok' ) {
        delete_transient( 'bsza_terkep_adatok' );
    }
} );
