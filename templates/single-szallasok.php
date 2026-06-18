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
                            <svg class="bsza-arrow-ikon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path style="fill:#E8943A !important" fill-rule="evenodd" clip-rule="evenodd" d="M12.0574 1.25H11.9426C9.63424 1.24999 7.82519 1.24998 6.41371 1.43975C4.96897 1.63399 3.82895 2.03933 2.93414 2.93414C2.03933 3.82895 1.63399 4.96897 1.43975 6.41371C1.24998 7.82519 1.24999 9.63422 1.25 11.9426V12.0574C1.24999 14.3658 1.24998 16.1748 1.43975 17.5863C1.63399 19.031 2.03933 20.1711 2.93414 21.0659C3.82895 21.9607 4.96897 22.366 6.41371 22.5603C7.82519 22.75 9.63423 22.75 11.9426 22.75H12.0574C14.3658 22.75 16.1748 22.75 17.5863 22.5603C19.031 22.366 20.1711 21.9607 21.0659 21.0659C21.9607 20.1711 22.366 19.031 22.5603 17.5863C22.75 16.1748 22.75 14.3658 22.75 12.0574V11.9426C22.75 9.63423 22.75 7.82519 22.5603 6.41371C22.366 4.96897 21.9607 3.82895 21.0659 2.93414C20.1711 2.03933 19.031 1.63399 17.5863 1.43975C16.1748 1.24998 14.3658 1.24999 12.0574 1.25ZM3.9948 3.9948C4.56445 3.42514 5.33517 3.09825 6.61358 2.92637C7.91356 2.75159 9.62177 2.75 12 2.75C14.3782 2.75 16.0864 2.75159 17.3864 2.92637C18.6648 3.09825 19.4355 3.42514 20.0052 3.9948C20.5749 4.56445 20.9018 5.33517 21.0736 6.61358C21.2484 7.91356 21.25 9.62177 21.25 12C21.25 14.3782 21.2484 16.0864 21.0736 17.3864C20.9018 18.6648 20.5749 19.4355 20.0052 20.0052C19.4355 20.5749 18.6648 20.9018 17.3864 21.0736C16.0864 21.2484 14.3782 21.25 12 21.25C9.62177 21.25 7.91356 21.2484 6.61358 21.0736C5.33517 20.9018 4.56445 20.5749 3.9948 20.0052C3.42514 19.4355 3.09825 18.6648 2.92637 17.3864C2.75159 16.0864 2.75 14.3782 2.75 12C2.75 9.62177 2.75159 7.91356 2.92637 6.61358C3.09825 5.33517 3.42514 4.56445 3.9948 3.9948Z"/><path style="fill:#E8943A !important" d="M16.5 11H11.5V8.8L7.2 12L11.5 15.2V13H16.5Z"/></svg>
                        </button>
                        <button class="bsza-lb-arrow bsza-lb-next" id="bsza-single-next" aria-label="Következő kép">
                            <svg class="bsza-arrow-ikon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path style="fill:#E8943A !important" fill-rule="evenodd" clip-rule="evenodd" d="M12.0574 1.25H11.9426C9.63424 1.24999 7.82519 1.24998 6.41371 1.43975C4.96897 1.63399 3.82895 2.03933 2.93414 2.93414C2.03933 3.82895 1.63399 4.96897 1.43975 6.41371C1.24998 7.82519 1.24999 9.63422 1.25 11.9426V12.0574C1.24999 14.3658 1.24998 16.1748 1.43975 17.5863C1.63399 19.031 2.03933 20.1711 2.93414 21.0659C3.82895 21.9607 4.96897 22.366 6.41371 22.5603C7.82519 22.75 9.63423 22.75 11.9426 22.75H12.0574C14.3658 22.75 16.1748 22.75 17.5863 22.5603C19.031 22.366 20.1711 21.9607 21.0659 21.0659C21.9607 20.1711 22.366 19.031 22.5603 17.5863C22.75 16.1748 22.75 14.3658 22.75 12.0574V11.9426C22.75 9.63423 22.75 7.82519 22.5603 6.41371C22.366 4.96897 21.9607 3.82895 21.0659 2.93414C20.1711 2.03933 19.031 1.63399 17.5863 1.43975C16.1748 1.24998 14.3658 1.24999 12.0574 1.25ZM3.9948 3.9948C4.56445 3.42514 5.33517 3.09825 6.61358 2.92637C7.91356 2.75159 9.62177 2.75 12 2.75C14.3782 2.75 16.0864 2.75159 17.3864 2.92637C18.6648 3.09825 19.4355 3.42514 20.0052 3.9948C20.5749 4.56445 20.9018 5.33517 21.0736 6.61358C21.2484 7.91356 21.25 9.62177 21.25 12C21.25 14.3782 21.2484 16.0864 21.0736 17.3864C20.9018 18.6648 20.5749 19.4355 20.0052 20.0052C19.4355 20.5749 18.6648 20.9018 17.3864 21.0736C16.0864 21.2484 14.3782 21.25 12 21.25C9.62177 21.25 7.91356 21.2484 6.61358 21.0736C5.33517 20.9018 4.56445 20.5749 3.9948 20.0052C3.42514 19.4355 3.09825 18.6648 2.92637 17.3864C2.75159 16.0864 2.75 14.3782 2.75 12C2.75 9.62177 2.75159 7.91356 2.92637 6.61358C3.09825 5.33517 3.42514 4.56445 3.9948 3.9948Z"/><path style="fill:#E8943A !important" d="M7.5 11H12.5V8.8L16.8 12L12.5 15.2V13H7.5Z"/></svg>
                        </button>
                    <?php endif; ?>
                    <button class="bsza-lb-fullscreen" id="bsza-single-fullscreen" aria-label="Teljes képernyő">
                        <i class="fas fa-expand" style="color:#E8943A !important"></i>
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
