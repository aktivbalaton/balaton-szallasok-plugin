<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Egyedi mezők panel elrejtése (itt is, biztonsági okokból) ─────────────────
add_action( 'add_meta_boxes', function() {
    remove_meta_box( 'postcustom', 'szallasok', 'normal' );
}, 99 );

// ── Custom Post Type: szallasok ────────────────────────────────────────────────
function bsza_register_cpt() {
    $labels = array(
        'name'               => 'Szállások',
        'singular_name'      => 'Szállás',
        'menu_name'          => 'My Szállások',
        'add_new'            => 'Új szállás',
        'add_new_item'       => 'Új szállás hozzáadása',
        'edit_item'          => 'Szállás szerkesztése',
        'new_item'           => 'Új szállás',
        'view_item'          => 'Szállás megtekintése',
        'search_items'       => 'Szállások keresése',
        'not_found'          => 'Nem található szállás',
        'not_found_in_trash' => 'Nincs szállás a kukában',
    );

    register_post_type( 'szallasok', array(
        'labels'          => $labels,
        'public'          => true,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'query_var'       => true,
        'rewrite'         => array( 'slug' => 'szallasok' ),
        'capability_type' => 'post',
        'has_archive'     => false,
        'hierarchical'    => false,
        'menu_position'   => 25,
        'menu_icon'       => 'dashicons-building',
        'supports'        => array( 'title', 'editor', 'thumbnail' ),
        'show_in_rest'    => false,
    ) );
}
add_action( 'init', 'bsza_register_cpt' );

// ── Taxonómia: Szállás típus ───────────────────────────────────────────────────
function bsza_register_tipus_taxonomy() {
    register_taxonomy( 'szallas_tipus', 'szallasok', array(
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_menu'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'szallas-tipus' ),
        'labels'            => array(
            'name'          => 'Típusok',
            'singular_name' => 'Típus',
            'search_items'  => 'Típus keresése',
            'all_items'     => 'Összes típus',
            'edit_item'     => 'Típus szerkesztése',
            'update_item'   => 'Típus frissítése',
            'add_new_item'  => 'Új típus hozzáadása',
            'new_item_name' => 'Új típus neve',
            'menu_name'     => 'Típusok',
        ),
        'meta_box_cb' => false,
    ) );

    $defaults = array(
        'Apartman'    => 'apartman',
        'Hotel'       => 'hotel',
        'Vendégház'   => 'vendeghaz',
        'Kemping'     => 'kemping',
        'Panzió'      => 'panzio',
        'Szoba kiadó' => 'szoba-kiado',
    );
    foreach ( $defaults as $name => $slug ) {
        if ( ! term_exists( $name, 'szallas_tipus' ) ) {
            wp_insert_term( $name, 'szallas_tipus', array( 'slug' => $slug ) );
        }
    }
}
add_action( 'init', 'bsza_register_tipus_taxonomy' );

// ── Taxonómia: Felszereltség ───────────────────────────────────────────────────
function bsza_register_felszereltseg_taxonomy() {
    register_taxonomy( 'szallas_felszereltseg_tax', 'szallasok', array(
        'hierarchical'      => false,
        'show_ui'           => true,
        'show_admin_column' => false,
        'show_in_menu'      => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'szallas-felszereltseg' ),
        'labels'            => array(
            'name'          => 'Felszereltségek',
            'singular_name' => 'Felszereltség',
            'search_items'  => 'Felszereltség keresése',
            'all_items'     => 'Összes felszereltség',
            'edit_item'     => 'Felszereltség szerkesztése',
            'update_item'   => 'Felszereltség frissítése',
            'add_new_item'  => 'Új felszereltség hozzáadása',
            'new_item_name' => 'Új felszereltség neve',
            'menu_name'     => 'Felszereltségek',
        ),
        'meta_box_cb' => false,
    ) );

    $defaults = array(
        'WiFi', 'Parkoló', 'Medence', 'Légkondicionálás',
        'Kisállat fogadása', 'Reggeli', 'Kert / Terasz',
        'Mosógép', 'TV', 'Pótágy lehetséges',
        'Grillterasz', 'Kerékpártároló', 'Szauna',
    );
    foreach ( $defaults as $name ) {
        if ( ! term_exists( $name, 'szallas_felszereltseg_tax' ) ) {
            wp_insert_term( $name, 'szallas_felszereltseg_tax' );
        }
    }
}
add_action( 'init', 'bsza_register_felszereltseg_taxonomy' );
