<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$per_page = 9;
$orderby  = 'date';
$order    = 'DESC';
$meta_key = '';

if ( isset( $_POST['sort'] ) ) {
    switch ( sanitize_key( $_POST['sort'] ) ) {
        case 'name_asc':     $orderby = 'title';          $order = 'ASC';  break;
        case 'name_desc':    $orderby = 'title';          $order = 'DESC'; break;
        case 'city_asc':     $orderby = 'meta_value';     $order = 'ASC';
                             $meta_key = 'szallas_hely_telepules'; break;
        case 'ferohely_asc': $orderby = 'meta_value_num'; $order = 'ASC';
                             $meta_key = 'szallas_max_ferohely'; break;
        case 'date_asc':     $orderby = 'date';           $order = 'ASC';  break;
    }
}

$query = new WP_Query( array(
    'post_type'      => 'szallasok',
    'posts_per_page' => $per_page,
    'post_status'    => 'publish',
    'orderby'        => $orderby,
    'order'          => $order,
    'meta_key'       => $meta_key,
) );

$total = wp_count_posts( 'szallasok' )->publish;

// Összes szállás adatai a térképhez (koordinátával rendelkezők) – transziens cache
$terkep_adatok = get_transient( 'bsza_terkep_adatok' );

if ( false === $terkep_adatok ) {
    $osszes = get_posts( array( 'post_type' => 'szallasok', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
    $terkep_adatok = array();
    foreach ( $osszes as $sz ) {
        $lat = get_post_meta( $sz->ID, 'szallas_lat', true );
        $lng = get_post_meta( $sz->ID, 'szallas_lng', true );
        if ( ! $lat || ! $lng ) continue;

        $tipus_terms = get_the_terms( $sz->ID, 'szallas_tipus' );
        $terkep_adatok[] = array(
            'id'       => $sz->ID,
            'cim'      => $sz->post_title,
            'lat'      => (float) $lat,
            'lng'      => (float) $lng,
            'telepules'=> get_post_meta( $sz->ID, 'szallas_hely_telepules', true ),
            'tipus'    => ( $tipus_terms && ! is_wp_error( $tipus_terms ) ) ? $tipus_terms[0]->name : '',
            'ferohely' => get_post_meta( $sz->ID, 'szallas_max_ferohely', true ),
            'telefon'  => get_post_meta( $sz->ID, 'szallas_telefon', true ),
            'foglalas' => get_post_meta( $sz->ID, 'szallas_foglalas', true ),
            'kep'      => get_the_post_thumbnail_url( $sz->ID, 'thumbnail' ) ?: '',
            'modal'    => true,
        );
    }
    set_transient( 'bsza_terkep_adatok', $terkep_adatok, 6 * HOUR_IN_SECONDS );
}
?>
<div class="bsza-wrap">
<div class="szallasok-container">

    <!-- ── LISTA NÉZET ── -->
    <div id="bsza-lista-nezet">
        <div class="szallasok-grid">
            <?php
            if ( $query->have_posts() ) :
                while ( $query->have_posts() ) : $query->the_post();
                    include BSZA_PATH . 'templates/card-template.php';
                endwhile;
                wp_reset_postdata();
            else :
                echo '<div class="no-results"><i class="fas fa-search"></i><h3>Nincs találat</h3>';
                echo '<p>A megadott feltételekkel nem található szállás.</p>';
                echo '<button type="button" class="reset-filters">Szűrők törlése</button></div>';
            endif;
            ?>
        </div>

        <?php if ( $total > $per_page ) : ?>
        <div class="load-more-container" id="load-more-container">
            <button type="button" id="load-more-btn" class="load-more-btn"
                    data-offset="<?php echo $per_page; ?>"
                    data-per-page="<?php echo $per_page; ?>"
                    data-total="<?php echo $total; ?>">
                <i class="fas fa-chevron-down"></i>
                További szállások betöltése
                <span class="load-more-info">(<?php echo $per_page; ?> / <?php echo $total; ?>)</span>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── TÉRKÉP NÉZET ── -->
    <div id="bsza-terkep-nezet" style="display:none;">
        <?php if ( empty( $terkep_adatok ) ) : ?>
            <div class="bsza-terkep-no-coords">
                <i class="fas fa-map-marked-alt"></i>
                <h3>Nincs térkép koordináta</h3>
                <p>A szállásokhoz még nem lett GPS koordináta megadva.<br>
                   A szállás szerkesztőben add meg a szélességi és hosszúsági fokot.</p>
            </div>
        <?php else : ?>
            <div id="bsza-google-map"></div>
            <p class="bsza-terkep-info">
                <i class="fas fa-info-circle"></i>
                <?php echo count( $terkep_adatok ); ?> szállás jelenik meg a térképen.
                Kattints a gombostűkre a részletekért!
            </p>
        <?php endif; ?>
    </div>

</div>

</div><!-- /.bsza-wrap -->

<!-- Térkép adatok JS-nek -->
<script>
var bszaTerképAdatok = <?php echo wp_json_encode( $terkep_adatok, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;
</script>
