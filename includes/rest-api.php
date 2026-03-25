<?php
/**
 * Balaton Szállások – REST API
 * Verzió: 1.0.0
 *
 * Endpontok:
 *   GET  /wp-json/bsza/v1/szallasok           – Lista (szűrés, lapozás)
 *   GET  /wp-json/bsza/v1/szallas/{id}        – Egy szállás részletei
 *   POST /wp-json/bsza/v1/szallas             – Szállás létrehozása
 *   PUT  /wp-json/bsza/v1/szallas/{id}        – Szállás frissítése
 *   POST /wp-json/bsza/v1/szallas/{id}/kep    – Kép sideload URL-ből (galéria)
 *
 * Auth: WordPress Application Password (Basic Auth), publish_posts capability.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// REST endpoint regisztráció
// ============================================================
add_action( 'rest_api_init', function () {

    // ---- Lista ----
    register_rest_route( 'bsza/v1', '/szallasok', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'bsza_api_list',
        'permission_callback' => 'bsza_api_auth',
        'args'                => [
            'per_page'  => [ 'type' => 'integer', 'default' => 20,  'minimum' => 1, 'maximum' => 100 ],
            'page'      => [ 'type' => 'integer', 'default' => 1,   'minimum' => 1 ],
            'search'    => [ 'type' => 'string',  'default' => '' ],
            'status'    => [ 'type' => 'string',  'default' => 'publish', 'enum' => ['publish','draft','any'] ],
            'telepules' => [ 'type' => 'string',  'default' => '' ],
            'tipus'     => [ 'type' => 'string',  'default' => '' ],
        ],
    ] );

    // ---- Egy szállás ----
    register_rest_route( 'bsza/v1', '/szallas/(?P<id>\d+)', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'bsza_api_get',
            'permission_callback' => 'bsza_api_auth',
        ],
        [
            'methods'             => WP_REST_Server::EDITABLE,   // PUT + PATCH
            'callback'            => 'bsza_api_update',
            'permission_callback' => 'bsza_api_auth',
        ],
    ] );

    // ---- Létrehozás ----
    register_rest_route( 'bsza/v1', '/szallas', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'bsza_api_create',
        'permission_callback' => 'bsza_api_auth',
        'args'                => bsza_api_args(),
    ] );

    // ---- Kép sideload URL-ből ----
    register_rest_route( 'bsza/v1', '/szallas/(?P<id>\d+)/kep', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'bsza_api_sideload_image',
        'permission_callback' => 'bsza_api_auth',
        'args'                => [
            'url'       => [ 'type' => 'string', 'required' => true ],
            'is_thumb'  => [ 'type' => 'boolean', 'default' => false ],
        ],
    ] );

    // ---- Taxonómiák listája (típusok, felszereltségek) ----
    register_rest_route( 'bsza/v1', '/meta', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'bsza_api_meta',
        'permission_callback' => 'bsza_api_auth',
    ] );

    // ---- Státusz / ping ----
    register_rest_route( 'bsza/v1', '/status', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'bsza_api_status',
        'permission_callback' => '__return_true',
    ] );
} );

// ============================================================
// Auth
// ============================================================
function bsza_api_auth() {
    return current_user_can( 'publish_posts' );
}

// ============================================================
// Közös arg definíciók
// ============================================================
function bsza_api_args() {
    return [
        'title'        => [ 'type' => 'string',  'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
        'content'      => [ 'type' => 'string',  'default'  => '' ],
        'status'       => [ 'type' => 'string',  'default'  => 'publish', 'enum' => ['publish','draft'] ],
        'telepules'    => [ 'type' => 'string',  'default'  => '', 'sanitize_callback' => 'sanitize_text_field' ],
        'utca'         => [ 'type' => 'string',  'default'  => '', 'sanitize_callback' => 'sanitize_text_field' ],
        'telefon'      => [ 'type' => 'string',  'default'  => '', 'sanitize_callback' => 'sanitize_text_field' ],
        'email'        => [ 'type' => 'string',  'default'  => '', 'sanitize_callback' => 'sanitize_email' ],
        'foglalas'     => [ 'type' => 'string',  'default'  => '', 'sanitize_callback' => 'esc_url_raw' ],
        'max_ferohely' => [ 'type' => 'integer', 'default'  => 0 ],
        'lat'          => [ 'type' => 'string',  'default'  => '', 'sanitize_callback' => 'sanitize_text_field' ],
        'lng'          => [ 'type' => 'string',  'default'  => '', 'sanitize_callback' => 'sanitize_text_field' ],
        'tipus'        => [ 'type' => 'array',   'default'  => [], 'items' => [ 'type' => 'string' ] ],
        'felszereltseg'=> [ 'type' => 'array',   'default'  => [], 'items' => [ 'type' => 'string' ] ],
        'galeria_ids'  => [ 'type' => 'array',   'default'  => [], 'items' => [ 'type' => 'integer' ] ],
        'thumbnail_id' => [ 'type' => 'integer', 'default'  => 0 ],
    ];
}

// ============================================================
// Szállás → API response formátum
// ============================================================
function bsza_api_format( int $post_id ): array {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'szallasok' ) return [];

    // Taxonómiák
    $tipus_terms = wp_get_post_terms( $post_id, 'szallas_tipus', [ 'fields' => 'names' ] );
    $felszh_terms= wp_get_post_terms( $post_id, 'szallas_felszereltseg_tax', [ 'fields' => 'names' ] );

    // Galéria
    $galeria_ids = get_post_meta( $post_id, 'szallas_galeria_ids', true );
    if ( ! is_array( $galeria_ids ) ) {
        $galeria_ids = $galeria_ids ? json_decode( $galeria_ids, true ) : [];
    }
    $galeria_ids = array_map( 'intval', (array) $galeria_ids );

    // Thumbnail
    $thumb_id  = (int) get_post_thumbnail_id( $post_id );
    $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';

    // Galéria URL-ek (az összes kép)
    $galeria_urls = array_values( array_filter( array_map( function( $id ) {
        return wp_get_attachment_image_url( $id, 'medium' ) ?: '';
    }, $galeria_ids ) ) );

    return [
        'id'           => $post_id,
        'title'        => $post->post_title,
        'slug'         => $post->post_name,
        'status'       => $post->post_status,
        'content'      => $post->post_content,
        'telepules'    => get_post_meta( $post_id, 'szallas_hely_telepules', true ) ?: '',
        'utca'         => get_post_meta( $post_id, 'szallas_utca_hazszam',   true ) ?: '',
        'telefon'      => get_post_meta( $post_id, 'szallas_telefon',        true ) ?: '',
        'email'        => get_post_meta( $post_id, 'szallas_email',          true ) ?: '',
        'foglalas'     => get_post_meta( $post_id, 'szallas_foglalas',       true ) ?: '',
        'max_ferohely' => (int) ( get_post_meta( $post_id, 'szallas_max_ferohely', true ) ?: 0 ),
        'lat'          => get_post_meta( $post_id, 'szallas_lat',            true ) ?: '',
        'lng'          => get_post_meta( $post_id, 'szallas_lng',            true ) ?: '',
        'tipus'        => is_wp_error( $tipus_terms )  ? [] : (array) $tipus_terms,
        'felszereltseg'=> is_wp_error( $felszh_terms ) ? [] : (array) $felszh_terms,
        'galeria_ids'  => $galeria_ids,
        'galeria_urls' => $galeria_urls,
        'thumbnail_id' => $thumb_id,
        'thumbnail_url'=> $thumb_url ?: '',
        'permalink'    => get_permalink( $post_id ) ?: '',
        'edit_url'     => admin_url( "post.php?post={$post_id}&action=edit" ),
        'created_at'   => get_post_field( 'post_date', $post_id ),
        'modified_at'  => get_post_field( 'post_modified', $post_id ),
    ];
}

// ============================================================
// Taxonómiák mentése (típus + felszereltség)
// ============================================================
function bsza_api_save_taxonomies( int $post_id, array $tipus_names, array $felszh_names ) {
    // Típus (hierarchikus, radio → max 1 db)
    if ( ! empty( $tipus_names ) ) {
        $term_ids = [];
        foreach ( $tipus_names as $name ) {
            $term = get_term_by( 'name', $name, 'szallas_tipus' );
            if ( $term && ! is_wp_error( $term ) ) $term_ids[] = $term->term_id;
        }
        wp_set_post_terms( $post_id, $term_ids, 'szallas_tipus' );
    }

    // Felszereltség (lapos, több db)
    if ( ! empty( $felszh_names ) ) {
        $term_ids = [];
        foreach ( $felszh_names as $name ) {
            $term = get_term_by( 'name', $name, 'szallas_felszereltseg_tax' );
            if ( $term && ! is_wp_error( $term ) ) $term_ids[] = $term->term_id;
        }
        wp_set_post_terms( $post_id, $term_ids, 'szallas_felszereltseg_tax' );
    }
}

// ============================================================
// GET /bsza/v1/szallasok – Lista
// ============================================================
function bsza_api_list( WP_REST_Request $req ) {
    $per_page  = (int) $req->get_param( 'per_page' );
    $page      = (int) $req->get_param( 'page' );
    $search    = sanitize_text_field( $req->get_param( 'search' ) );
    $status    = $req->get_param( 'status' );
    $telepules = sanitize_text_field( $req->get_param( 'telepules' ) );
    $tipus     = sanitize_text_field( $req->get_param( 'tipus' ) );

    $args = [
        'post_type'      => 'szallasok',
        'post_status'    => $status === 'any' ? [ 'publish', 'draft' ] : $status,
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ];

    if ( $search ) {
        $args['s'] = $search;
    }

    // Település szűrő – meta query
    if ( $telepules ) {
        $args['meta_query'] = [
            [
                'key'     => 'szallas_hely_telepules',
                'value'   => $telepules,
                'compare' => 'LIKE',
            ]
        ];
    }

    // Típus szűrő – taxonomy query
    if ( $tipus ) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'szallas_tipus',
                'field'    => 'name',
                'terms'    => $tipus,
            ]
        ];
    }

    $query = new WP_Query( $args );
    $items = [];
    foreach ( $query->posts as $post ) {
        $items[] = bsza_api_format( $post->ID );
    }

    return rest_ensure_response( [
        'items'       => $items,
        'total'       => (int) $query->found_posts,
        'total_pages' => (int) $query->max_num_pages,
        'page'        => $page,
        'per_page'    => $per_page,
    ] );
}

// ============================================================
// GET /bsza/v1/szallas/{id}
// ============================================================
function bsza_api_get( WP_REST_Request $req ) {
    $id   = (int) $req->get_param( 'id' );
    $post = get_post( $id );

    if ( ! $post || $post->post_type !== 'szallasok' ) {
        return new WP_Error( 'not_found', 'Szállás nem található', [ 'status' => 404 ] );
    }

    return rest_ensure_response( bsza_api_format( $id ) );
}

// ============================================================
// POST /bsza/v1/szallas – Létrehozás
// ============================================================
function bsza_api_create( WP_REST_Request $req ) {
    $p = $req->get_params();

    $post_id = wp_insert_post( [
        'post_type'    => 'szallasok',
        'post_title'   => $p['title'],
        'post_content' => wp_kses_post( $p['content'] ),
        'post_status'  => $p['status'] ?? 'publish',
    ], true );

    if ( is_wp_error( $post_id ) ) {
        return new WP_Error( 'insert_failed', $post_id->get_error_message(), [ 'status' => 500 ] );
    }

    bsza_api_save_meta( $post_id, $p );

    return rest_ensure_response( bsza_api_format( $post_id ) );
}

// ============================================================
// PUT /bsza/v1/szallas/{id} – Frissítés
// ============================================================
function bsza_api_update( WP_REST_Request $req ) {
    $id   = (int) $req->get_param( 'id' );
    $post = get_post( $id );

    if ( ! $post || $post->post_type !== 'szallasok' ) {
        return new WP_Error( 'not_found', 'Szállás nem található', [ 'status' => 404 ] );
    }

    $p = $req->get_params();

    $update = [ 'ID' => $id ];
    if ( isset( $p['title'] )   ) $update['post_title']   = sanitize_text_field( $p['title'] );
    if ( isset( $p['content'] ) ) $update['post_content']  = wp_kses_post( $p['content'] );
    if ( isset( $p['status'] )  ) $update['post_status']   = in_array( $p['status'], ['publish','draft'] ) ? $p['status'] : $post->post_status;

    wp_update_post( $update );
    bsza_api_save_meta( $id, $p );

    // Transient cache törlése (térkép)
    delete_transient( 'bsza_terkep_adatok' );

    return rest_ensure_response( bsza_api_format( $id ) );
}

// ============================================================
// Meta mentés (közös a create / update között)
// ============================================================
function bsza_api_save_meta( int $post_id, array $p ) {
    $meta_map = [
        'telepules'    => 'szallas_hely_telepules',
        'utca'         => 'szallas_utca_hazszam',
        'telefon'      => 'szallas_telefon',
        'email'        => 'szallas_email',
        'foglalas'     => 'szallas_foglalas',
        'max_ferohely' => 'szallas_max_ferohely',
        'lat'          => 'szallas_lat',
        'lng'          => 'szallas_lng',
    ];

    foreach ( $meta_map as $param => $meta_key ) {
        if ( isset( $p[$param] ) ) {
            update_post_meta( $post_id, $meta_key, $p[$param] );
        }
    }

    // Galéria IDs
    if ( isset( $p['galeria_ids'] ) && is_array( $p['galeria_ids'] ) ) {
        update_post_meta( $post_id, 'szallas_galeria_ids', array_map( 'intval', $p['galeria_ids'] ) );
        // Transient cache törlése
        delete_transient( 'bsza_terkep_adatok' );
    }

    // Kiemelt kép (thumbnail)
    if ( ! empty( $p['thumbnail_id'] ) ) {
        set_post_thumbnail( $post_id, (int) $p['thumbnail_id'] );
    }

    // Taxonómiák
    if ( isset( $p['tipus'] ) ) {
        bsza_api_save_taxonomies( $post_id, (array) $p['tipus'], [] );
    }
    if ( isset( $p['felszereltseg'] ) ) {
        bsza_api_save_taxonomies( $post_id, [], (array) $p['felszereltseg'] );
    }
    // Ha mindkettő jön egyszerre
    if ( isset( $p['tipus'] ) && isset( $p['felszereltseg'] ) ) {
        bsza_api_save_taxonomies( $post_id, (array) $p['tipus'], (array) $p['felszereltseg'] );
    }
}

// ============================================================
// POST /bsza/v1/szallas/{id}/kep – Kép sideload URL-ből
// (Firebase Storage URL → WP Media Library attachment)
// ============================================================
function bsza_api_sideload_image( WP_REST_Request $req ) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $post_id  = (int) $req->get_param( 'id' );
    $url      = esc_url_raw( $req->get_param( 'url' ) );
    $is_thumb = (bool) $req->get_param( 'is_thumb' );

    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'szallasok' ) {
        return new WP_Error( 'not_found', 'Szállás nem található', [ 'status' => 404 ] );
    }

    if ( ! $url ) {
        return new WP_Error( 'no_url', 'URL megadása kötelező', [ 'status' => 400 ] );
    }

    // Ideiglenes letöltés + importálás a WP Media Library-ba
    $tmp = download_url( $url, 30 );
    if ( is_wp_error( $tmp ) ) {
        return new WP_Error( 'download_failed', 'Kép letöltése sikertelen: ' . $tmp->get_error_message(), [ 'status' => 500 ] );
    }

    // Fájlnév kinyerése az URL-ből
    $url_path  = parse_url( $url, PHP_URL_PATH );
    $file_name = basename( $url_path );
    // Ha nincs kiterjesztés a névben, adjunk hozzá .jpg-t
    if ( ! pathinfo( $file_name, PATHINFO_EXTENSION ) ) {
        $file_name .= '.jpg';
    }
    $file_name = sanitize_file_name( $file_name );

    $file_array = [
        'name'     => $file_name,
        'tmp_name' => $tmp,
    ];

    $attachment_id = media_handle_sideload( $file_array, $post_id );

    // Ideiglenes fájl törlése (ha még létezik)
    if ( file_exists( $tmp ) ) {
        @unlink( $tmp );
    }

    if ( is_wp_error( $attachment_id ) ) {
        return new WP_Error( 'sideload_failed', 'Importálás sikertelen: ' . $attachment_id->get_error_message(), [ 'status' => 500 ] );
    }

    // Ha kiemelt képnek jelölték
    if ( $is_thumb ) {
        set_post_thumbnail( $post_id, $attachment_id );
    } else {
        // Hozzáadja a galéria ID tömbhöz
        $galeria = get_post_meta( $post_id, 'szallas_galeria_ids', true );
        if ( ! is_array( $galeria ) ) {
            $galeria = $galeria ? json_decode( $galeria, true ) : [];
        }
        $galeria   = array_map( 'intval', (array) $galeria );
        $galeria[] = $attachment_id;
        update_post_meta( $post_id, 'szallas_galeria_ids', $galeria );
    }

    return rest_ensure_response( [
        'attachment_id' => $attachment_id,
        'url'           => wp_get_attachment_image_url( $attachment_id, 'medium' ) ?: wp_get_attachment_url( $attachment_id ),
        'full_url'      => wp_get_attachment_url( $attachment_id ),
        'is_thumb'      => $is_thumb,
    ] );
}

// ============================================================
// GET /bsza/v1/meta – Típusok és felszereltségek listája
// ============================================================
function bsza_api_meta() {
    $tipusok = get_terms( [ 'taxonomy' => 'szallas_tipus', 'hide_empty' => false, 'orderby' => 'name' ] );
    $felszh  = get_terms( [ 'taxonomy' => 'szallas_felszereltseg_tax', 'hide_empty' => false, 'orderby' => 'name' ] );

    $fmt = function( $terms ) {
        if ( is_wp_error( $terms ) ) return [];
        return array_map( fn($t) => [ 'id' => $t->term_id, 'name' => $t->name, 'count' => $t->count ], $terms );
    };

    // Egyedi települések a szállásokból
    global $wpdb;
    $telepulesek = $wpdb->get_col(
        "SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
         WHERE meta_key = 'szallas_hely_telepules'
           AND meta_value != ''
         ORDER BY meta_value ASC"
    );

    return rest_ensure_response( [
        'tipusok'     => $fmt( $tipusok ),
        'felszereltseg' => $fmt( $felszh ),
        'telepulesek' => $telepulesek ?: [],
    ] );
}

// ============================================================
// GET /bsza/v1/status – Státusz / ping
// ============================================================
function bsza_api_status() {
    return rest_ensure_response( [
        'plugin'     => 'Balaton Szállások REST API',
        'version'    => BSZA_VERSION,
        'endpoint'   => rest_url( 'bsza/v1/szallasok' ),
        'wp_version' => get_bloginfo( 'version' ),
        'cpt_exists' => post_type_exists( 'szallasok' ),
    ] );
}
