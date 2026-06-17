<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Szűrő + lapozás AJAX handler ──────────────────────────────────────────────
function bsza_filter_szallasok() {
    check_ajax_referer( 'szallasok_nonce', 'nonce' );

    $per_page = 9;
    $offset   = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;

    $args = array(
        'post_type'      => 'szallasok',
        'posts_per_page' => $per_page,
        'offset'         => $offset,
        'post_status'    => 'publish',
    );

    // Szöveges keresés (szállás neve + leírás)
    if ( ! empty( $_POST['kereses'] ) ) {
        $args['s'] = sanitize_text_field( wp_unslash( $_POST['kereses'] ) );
    }

    // Rendezés
    $sort = isset( $_POST['sort'] ) ? sanitize_key( $_POST['sort'] ) : 'date_desc';
    switch ( $sort ) {
        case 'name_asc':     $args['orderby'] = 'title';          $args['order'] = 'ASC';  break;
        case 'name_desc':    $args['orderby'] = 'title';          $args['order'] = 'DESC'; break;
        case 'city_asc':     $args['orderby'] = 'meta_value';     $args['order'] = 'ASC';
                             $args['meta_key'] = 'szallas_hely_telepules'; break;
        case 'ferohely_asc': $args['orderby'] = 'meta_value_num'; $args['order'] = 'ASC';
                             $args['meta_key'] = 'szallas_max_ferohely'; break;
        case 'date_asc':     $args['orderby'] = 'date';           $args['order'] = 'ASC';  break;
        default:             $args['orderby'] = 'date';           $args['order'] = 'DESC';
    }

    // Taxonómia szűrők
    $tax_query = array( 'relation' => 'AND' );
    if ( ! empty( $_POST['tipus'] ) ) {
        $tax_query[] = array(
            'taxonomy' => 'szallas_tipus',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( $_POST['tipus'] ),
        );
    }
    if ( ! empty( $_POST['felszereltseg'] ) && is_array( $_POST['felszereltseg'] ) ) {
        foreach ( $_POST['felszereltseg'] as $slug ) {
            $tax_query[] = array(
                'taxonomy' => 'szallas_felszereltseg_tax',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $slug ),
            );
        }
    }
    if ( count( $tax_query ) > 1 ) $args['tax_query'] = $tax_query;

    // Meta szűrők
    $meta_query = array( 'relation' => 'AND' );
    if ( ! empty( $_POST['telepules'] ) ) {
        $meta_query[] = array( 'key' => 'szallas_hely_telepules', 'value' => sanitize_text_field( $_POST['telepules'] ), 'compare' => '=' );
    }
    if ( ! empty( $_POST['min_ferohely'] ) ) {
        $meta_query[] = array( 'key' => 'szallas_max_ferohely', 'value' => absint( $_POST['min_ferohely'] ), 'compare' => '>=', 'type' => 'NUMERIC' );
    }
    if ( count( $meta_query ) > 1 ) $args['meta_query'] = $meta_query;

    // Összes találat száma
    $count_args                   = $args;
    $count_args['posts_per_page'] = -1;
    $count_args['offset']         = 0;
    $count_args['fields']         = 'ids';
    $total = count( get_posts( $count_args ) );

    $query = new WP_Query( $args );
    ob_start();
    if ( $query->have_posts() ) :
        while ( $query->have_posts() ) : $query->the_post();
            include BSZA_PATH . 'templates/card-template.php';
        endwhile;
        wp_reset_postdata();
    endif;
    $html = ob_get_clean();

    wp_send_json_success( array(
        'html'     => $html,
        'loaded'   => $offset + $query->post_count,
        'total'    => $total,
        'has_more' => ( $offset + $query->post_count ) < $total,
    ) );
}
add_action( 'wp_ajax_filter_szallasok',        'bsza_filter_szallasok' );
add_action( 'wp_ajax_nopriv_filter_szallasok', 'bsza_filter_szallasok' );

// ── Modal adat lekérése AJAX-szal (térkép markerekhez) ────────────────────────
function bsza_get_modal_data() {
    check_ajax_referer( 'szallasok_nonce', 'nonce' );

    $post_id = absint( $_POST['id'] );
    $post    = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'szallasok' ) {
        wp_send_json_error( 'Nem található.' );
    }

    $leiras_raw    = $post->post_content;
    $leiras_teljes = $leiras_raw ? wp_strip_all_tags( $leiras_raw ) : '';
    $tipus_terms   = get_the_terms( $post_id, 'szallas_tipus' );
    $felszereltseg_terms = get_the_terms( $post_id, 'szallas_felszereltseg_tax' );
    $felszereltseg_nevek = array();
    if ( $felszereltseg_terms && ! is_wp_error( $felszereltseg_terms ) ) {
        foreach ( $felszereltseg_terms as $t ) $felszereltseg_nevek[] = $t->name;
    }

    $gallery_ids = get_post_meta( $post_id, 'szallas_galeria_ids', true );
    if ( ! is_array( $gallery_ids ) ) $gallery_ids = array();
    $images = array();
    if ( has_post_thumbnail( $post_id ) ) {
        $thumb_id = get_post_thumbnail_id( $post_id );
        $images[] = array( 'url' => get_the_post_thumbnail_url( $post_id, 'large' ), 'thumb' => get_the_post_thumbnail_url( $post_id, 'thumbnail' ) );
        foreach ( $gallery_ids as $img_id ) {
            if ( $img_id == $thumb_id ) continue;
            $url = wp_get_attachment_image_url( $img_id, 'large' );
            if ( $url ) $images[] = array( 'url' => $url, 'thumb' => wp_get_attachment_image_url( $img_id, 'thumbnail' ) );
        }
    } else {
        foreach ( $gallery_ids as $img_id ) {
            $url = wp_get_attachment_image_url( $img_id, 'large' );
            if ( $url ) $images[] = array( 'url' => $url, 'thumb' => wp_get_attachment_image_url( $img_id, 'thumbnail' ) );
        }
    }

    wp_send_json_success( array(
        'id'            => $post_id,
        'cim'           => $post->post_title,
        'telepules'     => get_post_meta( $post_id, 'szallas_hely_telepules', true ),
        'utca'          => get_post_meta( $post_id, 'szallas_utca_hazszam', true ),
        'tipus'         => ( $tipus_terms && ! is_wp_error( $tipus_terms ) ) ? $tipus_terms[0]->name : '',
        'ferohely'      => get_post_meta( $post_id, 'szallas_max_ferohely', true ),
        'telefon'       => get_post_meta( $post_id, 'szallas_telefon', true ),
        'email'         => get_post_meta( $post_id, 'szallas_email', true ),
        'foglalas'      => get_post_meta( $post_id, 'szallas_foglalas', true ),
        'lat'           => get_post_meta( $post_id, 'szallas_lat', true ),
        'lng'           => get_post_meta( $post_id, 'szallas_lng', true ),
        'leiras'        => $leiras_teljes,
        'kepek'         => $images,
        'felszereltseg' => $felszereltseg_nevek,
    ) );
}
add_action( 'wp_ajax_bsza_get_modal_data',        'bsza_get_modal_data' );
add_action( 'wp_ajax_nopriv_bsza_get_modal_data', 'bsza_get_modal_data' );
