<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id       = get_the_ID();
$telepules     = get_post_meta( $post_id, 'szallas_hely_telepules', true );
$cim           = get_post_meta( $post_id, 'szallas_utca_hazszam', true );
$telefon       = get_post_meta( $post_id, 'szallas_telefon', true );
$email         = get_post_meta( $post_id, 'szallas_email', true );
$foglalas_url  = get_post_meta( $post_id, 'szallas_foglalas', true );
$max_ferohely  = get_post_meta( $post_id, 'szallas_max_ferohely', true );
$lat           = get_post_meta( $post_id, 'szallas_lat', true );
$lng           = get_post_meta( $post_id, 'szallas_lng', true );

// Leírás (kártyán nem jelenik meg, de modalhoz kell)
$leiras_raw    = get_the_content( null, false, $post_id );
$leiras_teljes = $leiras_raw ? wp_strip_all_tags( $leiras_raw ) : '';

// Típus – taxonómiából
$tipus_terms = get_the_terms( $post_id, 'szallas_tipus' );
$tipus_nev   = ( $tipus_terms && ! is_wp_error( $tipus_terms ) ) ? $tipus_terms[0]->name : '';

// Felszereltség – taxonómiából
$felszereltseg_terms = get_the_terms( $post_id, 'szallas_felszereltseg_tax' );
$felszereltseg_nevek = array();
if ( $felszereltseg_terms && ! is_wp_error( $felszereltseg_terms ) ) {
    foreach ( $felszereltseg_terms as $term ) {
        $felszereltseg_nevek[] = $term->name;
    }
}
$kartya_limit = 3;
$tobbi_db     = max( 0, count( $felszereltseg_nevek ) - $kartya_limit );

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

// Schema.org
$schema_type_map = array(
    'hotel'       => 'Hotel',
    'apartman'    => 'ApartmentComplex',
    'vendeghaz'   => 'BedAndBreakfast',
    'panzio'      => 'BedAndBreakfast',
    'kemping'     => 'Campground',
    'szoba-kiado' => 'LodgingBusiness',
);
$tipus_slug  = ( $tipus_terms && ! is_wp_error( $tipus_terms ) ) ? $tipus_terms[0]->slug : '';
$schema_type = isset( $schema_type_map[ $tipus_slug ] ) ? $schema_type_map[ $tipus_slug ] : 'LodgingBusiness';
$schema = array( '@context' => 'https://schema.org', '@type' => $schema_type, 'name' => get_the_title( $post_id ), 'url' => get_permalink( $post_id ) );
if ( $leiras_teljes ) $schema['description'] = $leiras_teljes;
if ( $telepules || $cim ) $schema['address'] = array( '@type' => 'PostalAddress', 'addressLocality' => $telepules ?: '', 'streetAddress' => $cim ?: '', 'addressCountry' => 'HU' );
if ( $lat && $lng )  $schema['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => (float) $lat, 'longitude' => (float) $lng );
if ( $telefon )      $schema['telephone'] = $telefon;
if ( $email )        $schema['email'] = $email;
if ( $foglalas_url ) $schema['reservationUrl'] = $foglalas_url;
if ( ! empty( $images ) ) $schema['image'] = $images[0]['url'];
if ( $max_ferohely ) $schema['amenityFeature'][] = array( '@type' => 'LocationFeatureSpecification', 'name' => 'Max. férőhelyek száma', 'value' => (int) $max_ferohely );
foreach ( $felszereltseg_nevek as $fn ) $schema['amenityFeature'][] = array( '@type' => 'LocationFeatureSpecification', 'name' => $fn, 'value' => true );

// Modal adatok
$modal_data = array(
    'id'            => $post_id,
    'cim'           => get_the_title( $post_id ),
    'telepules'     => $telepules,
    'utca'          => $cim,
    'tipus'         => $tipus_nev,
    'ferohely'      => $max_ferohely,
    'telefon'       => $telefon,
    'email'         => $email,
    'foglalas'      => $foglalas_url,
    'lat'           => $lat,
    'lng'           => $lng,
    'leiras'        => $leiras_teljes,
    'kepek'         => $images,
    'felszereltseg' => $felszereltseg_nevek,
);
?>
<div class="szallas-card"
     data-modal='<?php echo esc_attr( wp_json_encode( $modal_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ); ?>'>

    <script type="application/ld+json">
        <?php echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ); ?>
    </script>

    <div class="szallas-images-container">
        <?php if ( ! empty( $images ) ) : ?>
            <div class="szallas-image">
                <img src="<?php echo esc_url( $images[0]['url'] ); ?>"
                     alt="<?php echo esc_attr( get_the_title() ); ?>"
                     class="main-image" loading="lazy" />
            </div>
            <?php if ( count( $images ) > 1 ) : ?>
                <div class="szallas-thumbnails">
                    <?php foreach ( $images as $i => $img ) : ?>
                        <div class="thumbnail<?php echo $i === 0 ? ' active' : ''; ?>"
                             data-image="<?php echo esc_url( $img['url'] ); ?>">
                            <img src="<?php echo esc_url( $img['thumb'] ); ?>" alt="" loading="lazy" />
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="szallas-content">
        <h3 class="szallas-title"><?php the_title(); ?></h3>

        <?php if ( $telepules || $cim ) : ?>
            <div class="szallas-hely">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo esc_html( $telepules );
                if ( $cim ) echo ', ' . esc_html( $cim ); ?>
            </div>
        <?php endif; ?>

        <?php if ( $tipus_nev ) : ?>
            <div class="szallas-tipus">
                <i class="fas fa-home"></i>
                <?php echo esc_html( $tipus_nev ); ?>
            </div>
        <?php endif; ?>

        <?php if ( $max_ferohely ) : ?>
            <div class="szallas-hely">
                <i class="fas fa-users"></i>
                Max. <?php echo esc_html( $max_ferohely ); ?> fő
            </div>
        <?php endif; ?>

        <div class="szallas-contact">
            <?php if ( $telefon ) : ?>
                <a href="tel:<?php echo esc_attr( preg_replace('/\s+/', '', $telefon) ); ?>" class="contact-link">
                    <i class="fas fa-phone"></i> <?php echo esc_html( $telefon ); ?>
                </a>
            <?php endif; ?>
            <?php if ( $email ) : ?>
                <a href="mailto:<?php echo esc_attr( $email ); ?>" class="contact-link">
                    <i class="fas fa-envelope"></i> <?php echo esc_html( $email ); ?>
                </a>
            <?php endif; ?>
            <?php if ( $foglalas_url ) : ?>
                <a href="<?php echo esc_url( $foglalas_url ); ?>" class="contact-link booking-link" target="_blank" rel="noopener">
                    <i class="fas fa-calendar-check"></i> Foglalás
                </a>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $felszereltseg_nevek ) ) : ?>
            <div class="szallas-felszereltseg">
                <?php foreach ( array_slice( $felszereltseg_nevek, 0, $kartya_limit ) as $nev ) : ?>
                    <span class="felszereltseg-item">
                        <i class="fas fa-check"></i>
                        <?php echo esc_html( $nev ); ?>
                    </span>
                <?php endforeach; ?>
                <?php if ( $tobbi_db > 0 ) : ?>
                    <span class="felszereltseg-tobbi">+<?php echo $tobbi_db; ?> további</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ── Akció gombok: modal + saját oldal link ── -->
        <div class="szallas-card-actions">
            <a href="#" role="button" class="szallas-reszletek-btn">
                <i class="fas fa-expand-alt"></i> Részletek megtekintése
            </a>
            <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
               class="szallas-oldal-link"
               title="Megnyitás önálló oldalon"
               aria-label="Megnyitás önálló oldalon">
                <i class="fas fa-external-link-alt"></i>
            </a>
        </div>

    </div>
</div>
