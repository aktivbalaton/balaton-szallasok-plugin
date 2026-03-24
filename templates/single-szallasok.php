<?php
/**
 * Egyedi szállás oldal sablon
 * Elhelyezés: balaton-szallasok/templates/single-szallasok.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

if ( ! have_posts() ) {
    echo '<p style="text-align:center;padding:60px;">A szállás nem található.</p>';
    get_footer();
    return;
}

the_post();

$post_id      = get_the_ID();
$telepules    = get_post_meta( $post_id, 'szallas_hely_telepules', true );
$cim          = get_post_meta( $post_id, 'szallas_utca_hazszam', true );
$telefon      = get_post_meta( $post_id, 'szallas_telefon', true );
$email        = get_post_meta( $post_id, 'szallas_email', true );
$foglalas_url = get_post_meta( $post_id, 'szallas_foglalas', true );
$max_ferohely = get_post_meta( $post_id, 'szallas_max_ferohely', true );
$lat          = get_post_meta( $post_id, 'szallas_lat', true );
$lng          = get_post_meta( $post_id, 'szallas_lng', true );
$leiras       = get_the_content();

// Típus
$tipus_terms = get_the_terms( $post_id, 'szallas_tipus' );
$tipus_nev   = ( $tipus_terms && ! is_wp_error( $tipus_terms ) ) ? $tipus_terms[0]->name : '';

// Felszereltség
$felszereltseg_terms = get_the_terms( $post_id, 'szallas_felszereltseg_tax' );
$felszereltseg_nevek = array();
if ( $felszereltseg_terms && ! is_wp_error( $felszereltseg_terms ) ) {
    foreach ( $felszereltseg_terms as $term ) {
        $felszereltseg_nevek[] = $term->name;
    }
}

// Galéria képek
$gallery_ids = get_post_meta( $post_id, 'szallas_galeria_ids', true );
if ( ! is_array( $gallery_ids ) ) $gallery_ids = array();

$images = array();
if ( has_post_thumbnail( $post_id ) ) {
    $thumb_id = get_post_thumbnail_id( $post_id );
    $images[] = array(
        'url'   => get_the_post_thumbnail_url( $post_id, 'large' ),
        'thumb' => get_the_post_thumbnail_url( $post_id, 'thumbnail' ),
    );
    foreach ( $gallery_ids as $img_id ) {
        if ( $img_id == $thumb_id ) continue;
        $url = wp_get_attachment_image_url( $img_id, 'large' );
        if ( $url ) $images[] = array(
            'url'   => $url,
            'thumb' => wp_get_attachment_image_url( $img_id, 'thumbnail' ),
        );
    }
} else {
    foreach ( $gallery_ids as $img_id ) {
        $url = wp_get_attachment_image_url( $img_id, 'large' );
        if ( $url ) $images[] = array(
            'url'   => $url,
            'thumb' => wp_get_attachment_image_url( $img_id, 'thumbnail' ),
        );
    }
}

// Visszalépési link (beállításokból)
$lista_url = get_option( 'bsza_lista_oldal_url', '' );
?>

<div class="bsza-single-wrap bsza-wrap">

    <?php if ( $lista_url ) : ?>
    <div class="bsza-single-breadcrumb">
        <a href="<?php echo esc_url( $lista_url ); ?>" class="bsza-single-back">
            <i class="fas fa-arrow-left"></i> Vissza a szállásokhoz
        </a>
    </div>
    <?php endif; ?>

    <div class="bsza-single-inner">

        <!-- ── Galéria (bal) ── -->
        <div class="bsza-single-gallery">
            <?php if ( ! empty( $images ) ) : ?>
                <div class="bsza-single-main-img-wrap">
                    <img id="bsza-single-main-img"
                         src="<?php echo esc_url( $images[0]['url'] ); ?>"
                         alt="<?php echo esc_attr( get_the_title() ); ?>"
                         class="bsza-single-main-img" />
                    <?php if ( count( $images ) > 1 ) : ?>
                        <button class="bsza-lb-arrow bsza-lb-prev" id="bsza-single-prev" aria-label="Előző kép">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="bsza-lb-arrow bsza-lb-next" id="bsza-single-next" aria-label="Következő kép">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                    <button class="bsza-lb-fullscreen" id="bsza-single-fullscreen" aria-label="Teljes képernyő">
                        <i class="fas fa-expand"></i>
                    </button>
                    <?php if ( count( $images ) > 1 ) : ?>
                        <div class="bsza-single-img-counter">
                            <span id="bsza-single-counter-cur">1</span> / <?php echo count( $images ); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ( count( $images ) > 1 ) : ?>
                    <div class="bsza-single-thumbs">
                        <?php foreach ( $images as $i => $img ) : ?>
                            <img src="<?php echo esc_url( $img['thumb'] ); ?>"
                                 class="bsza-modal-thumb<?php echo $i === 0 ? ' active' : ''; ?>"
                                 data-index="<?php echo $i; ?>"
                                 alt="" />
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <div class="bsza-single-no-image">
                    <i class="fas fa-image"></i>
                    <p>Nincs elérhető kép</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── Info (jobb) ── -->
        <div class="bsza-single-info">

            <h1 class="bsza-single-title"><?php the_title(); ?></h1>

            <div class="bsza-single-meta">
                <?php if ( $telepules ) : ?>
                    <div class="bsza-modal-meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo esc_html( $telepules );
                        if ( $cim ) echo ', ' . esc_html( $cim ); ?>
                    </div>
                <?php endif; ?>
                <?php if ( $tipus_nev ) : ?>
                    <div class="bsza-modal-meta-item">
                        <i class="fas fa-home"></i>
                        <?php echo esc_html( $tipus_nev ); ?>
                    </div>
                <?php endif; ?>
                <?php if ( $max_ferohely ) : ?>
                    <div class="bsza-modal-meta-item">
                        <i class="fas fa-users"></i>
                        Max. <?php echo esc_html( $max_ferohely ); ?> fő
                    </div>
                <?php endif; ?>
            </div>

            <?php if ( $leiras ) : ?>
                <div class="bsza-single-leiras">
                    <?php echo wpautop( wp_kses_post( $leiras ) ); ?>
                </div>
            <?php endif; ?>

            <div class="bsza-single-kontakt">
                <?php if ( $telefon ) : ?>
                    <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $telefon ) ); ?>" class="bsza-modal-link">
                        <i class="fas fa-phone"></i> <?php echo esc_html( $telefon ); ?>
                    </a>
                <?php endif; ?>
                <?php if ( $email ) : ?>
                    <a href="mailto:<?php echo esc_attr( $email ); ?>" class="bsza-modal-link">
                        <i class="fas fa-envelope"></i> <?php echo esc_html( $email ); ?>
                    </a>
                <?php endif; ?>
                <?php if ( $foglalas_url ) : ?>
                    <a href="<?php echo esc_url( $foglalas_url ); ?>"
                       class="bsza-modal-link bsza-modal-foglalas"
                       target="_blank" rel="noopener">
                        <i class="fas fa-calendar-check"></i> Foglalás
                    </a>
                <?php endif; ?>
                <?php if ( $lat && $lng ) : ?>
                    <a href="https://www.google.com/maps?q=<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $lng ); ?>"
                       class="bsza-modal-link" target="_blank" rel="noopener">
                        <i class="fas fa-map"></i> Útvonal tervezés
                    </a>
                <?php endif; ?>
            </div>

            <?php if ( ! empty( $felszereltseg_nevek ) ) : ?>
                <div class="bsza-modal-felszereltseg">
                    <h4>Felszereltség</h4>
                    <div class="bsza-modal-felszereltseg-lista">
                        <?php foreach ( $felszereltseg_nevek as $nev ) : ?>
                            <span class="felszereltseg-item">
                                <i class="fas fa-check"></i> <?php echo esc_html( $nev ); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( $lat && $lng ) : ?>
                <div class="bsza-modal-map bsza-single-map">
                    <iframe
                        src="https://maps.google.com/maps?q=<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $lng ); ?>&z=15&output=embed"
                        width="100%" height="280" style="border:0;"
                        allowfullscreen="" loading="lazy">
                    </iframe>
                </div>
            <?php endif; ?>

        </div><!-- .bsza-single-info -->

    </div><!-- .bsza-single-inner -->

</div><!-- .bsza-single-wrap -->

<!-- Képek JS-nek (galéria + lightbox) -->
<script>
var bszaSingleImages = <?php echo wp_json_encode( $images, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;
</script>

<?php get_footer(); ?>
