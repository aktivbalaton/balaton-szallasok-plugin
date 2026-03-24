<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Admin eszközök ─────────────────────────────────────────────────────────────
function bsza_enqueue_admin_assets( $hook ) {
    global $post_type;
    if ( $post_type !== 'szallasok' ) return;

    wp_enqueue_media();
    wp_enqueue_style(
        'balaton-szallasok-admin',
        BSZA_URL . 'assets/css/admin.css',
        array(), BSZA_VERSION
    );
    wp_enqueue_script(
        'balaton-szallasok-admin',
        BSZA_URL . 'assets/js/admin.js',
        array( 'jquery', 'jquery-ui-sortable' ), BSZA_VERSION, true
    );
    wp_localize_script( 'balaton-szallasok-admin', 'bszaAdmin', array(
        'mediaTitle'  => 'Képek kiválasztása',
        'mediaButton' => 'Képek hozzáadása',
    ) );
}
add_action( 'admin_enqueue_scripts', 'bsza_enqueue_admin_assets' );

// ── Meta boxok regisztrálása ───────────────────────────────────────────────────
function bsza_register_meta_boxes() {
    // 1. Helyszín
    add_meta_box(
        'bsza_helyszin', '📍 Helyszín adatok',
        'bsza_render_helyszin_box',
        'szallasok', 'normal', 'high'
    );
    // 2. Kapcsolat
    add_meta_box(
        'bsza_kontakt', '📞 Kapcsolat & foglalás',
        'bsza_render_kontakt_box',
        'szallasok', 'normal', 'high'
    );
    // 3. Típus (taxonómia – bal oldal, meta box)
    add_meta_box(
        'bsza_tipus', '🏠 Szállás típusa',
        'bsza_render_tipus_box',
        'szallasok', 'normal', 'high'
    );
    // 4. Részletek (férőhely + felszereltség taxonómia)
    add_meta_box(
        'bsza_reszletek', '🛎️ Szállás részletei',
        'bsza_render_reszletek_box',
        'szallasok', 'normal', 'default'
    );
    // 5. Térkép
    add_meta_box(
        'bsza_terkep', '🗺️ Térkép / GPS koordináta',
        'bsza_render_terkep_box',
        'szallasok', 'normal', 'default'
    );
    // 6. Galéria
    add_meta_box(
        'bsza_galeria', '🖼️ Képgaléria (drag & drop)',
        'bsza_render_galeria_box',
        'szallasok', 'normal', 'default'
    );
}
add_action( 'add_meta_boxes', 'bsza_register_meta_boxes' );

// ── Helyszín box ───────────────────────────────────────────────────────────────
function bsza_render_helyszin_box( $post ) {
    wp_nonce_field( 'bsza_save_meta', 'bsza_nonce' );
    $telepules = get_post_meta( $post->ID, 'szallas_hely_telepules', true );
    $cim       = get_post_meta( $post->ID, 'szallas_utca_hazszam', true );
    ?>
    <table class="bsza-meta-table">
        <tr>
            <th><label for="szallas_hely_telepules">Település <span class="required">*</span></label></th>
            <td><input type="text" id="szallas_hely_telepules" name="szallas_hely_telepules"
                       value="<?php echo esc_attr( $telepules ); ?>"
                       placeholder="pl. Balatonfüred" class="widefat" /></td>
        </tr>
        <tr>
            <th><label for="szallas_utca_hazszam">Utca, házszám</label></th>
            <td><input type="text" id="szallas_utca_hazszam" name="szallas_utca_hazszam"
                       value="<?php echo esc_attr( $cim ); ?>"
                       placeholder="pl. Petőfi u. 12." class="widefat" /></td>
        </tr>
    </table>
    <?php
}

// ── Kapcsolat box ──────────────────────────────────────────────────────────────
function bsza_render_kontakt_box( $post ) {
    $telefon  = get_post_meta( $post->ID, 'szallas_telefon', true );
    $email    = get_post_meta( $post->ID, 'szallas_email', true );
    $foglalas = get_post_meta( $post->ID, 'szallas_foglalas', true );
    ?>
    <table class="bsza-meta-table">
        <tr>
            <th><label for="szallas_telefon">Telefonszám</label></th>
            <td><input type="text" id="szallas_telefon" name="szallas_telefon"
                       value="<?php echo esc_attr( $telefon ); ?>"
                       placeholder="+36 30 123 4567" class="widefat" /></td>
        </tr>
        <tr>
            <th><label for="szallas_email">E-mail cím</label></th>
            <td><input type="email" id="szallas_email" name="szallas_email"
                       value="<?php echo esc_attr( $email ); ?>"
                       placeholder="info@szallas.hu" class="widefat" /></td>
        </tr>
        <tr>
            <th><label for="szallas_foglalas">Foglalási link</label></th>
            <td>
                <input type="url" id="szallas_foglalas" name="szallas_foglalas"
                       value="<?php echo esc_attr( $foglalas ); ?>"
                       placeholder="https://szallas.hu/..." class="widefat" />
                <p class="description">Booking.com, Airbnb, saját oldal stb.</p>
            </td>
        </tr>
    </table>
    <?php
}

// ── Típus box (taxonómia checkboxok) ──────────────────────────────────────────
function bsza_render_tipus_box( $post ) {
    $tipusok        = get_terms( array( 'taxonomy' => 'szallas_tipus', 'hide_empty' => false ) );
    $kivalasztott   = wp_get_post_terms( $post->ID, 'szallas_tipus', array( 'fields' => 'ids' ) );
    if ( empty( $tipusok ) || is_wp_error( $tipusok ) ) {
        echo '<p>Még nincsenek típusok. <a href="' . admin_url('edit-tags.php?taxonomy=szallas_tipus&post_type=szallasok') . '">Hozzáadás itt</a>.</p>';
        return;
    }
    ?>
    <div class="bsza-checkbox-grid">
        <?php foreach ( $tipusok as $tipus ) : ?>
            <label class="bsza-checkbox-item">
                <input type="radio"
                       name="bsza_tipus_id"
                       value="<?php echo esc_attr( $tipus->term_id ); ?>"
                       <?php checked( in_array( $tipus->term_id, (array) $kivalasztott ) ); ?> />
                <?php echo esc_html( $tipus->name ); ?>
            </label>
        <?php endforeach; ?>
        <label class="bsza-checkbox-item bsza-none-item">
            <input type="radio" name="bsza_tipus_id" value="0"
                   <?php checked( empty( $kivalasztott ) ); ?> />
            — Nincs megadva
        </label>
    </div>
    <p class="description" style="margin-top:8px;">
        <a href="<?php echo admin_url('edit-tags.php?taxonomy=szallas_tipus&post_type=szallasok'); ?>" target="_blank">
            + Új típus hozzáadása
        </a>
    </p>
    <?php
}

// ── Részletek box (férőhely + felszereltség taxonómia) ─────────────────────────
function bsza_render_reszletek_box( $post ) {
    $max_ferohely  = get_post_meta( $post->ID, 'szallas_max_ferohely', true );
    $felszereltseg = get_terms( array( 'taxonomy' => 'szallas_felszereltseg_tax', 'hide_empty' => false, 'orderby' => 'name' ) );
    $kivalasztott  = wp_get_post_terms( $post->ID, 'szallas_felszereltseg_tax', array( 'fields' => 'ids' ) );
    ?>
    <table class="bsza-meta-table">
        <tr>
            <th><label for="szallas_max_ferohely">Max. férőhelyek száma</label></th>
            <td>
                <input type="number" id="szallas_max_ferohely" name="szallas_max_ferohely"
                       value="<?php echo esc_attr( $max_ferohely ); ?>"
                       min="1" max="500" step="1" style="width:100px;" />
                <span class="description">fő</span>
            </td>
        </tr>
        <tr>
            <th style="vertical-align:top;padding-top:12px;"><label>Felszereltség</label></th>
            <td>
                <?php if ( ! empty( $felszereltseg ) && ! is_wp_error( $felszereltseg ) ) : ?>
                    <div class="bsza-checkbox-grid">
                        <?php foreach ( $felszereltseg as $item ) : ?>
                            <label class="bsza-checkbox-item">
                                <input type="checkbox"
                                       name="bsza_felszereltseg_ids[]"
                                       value="<?php echo esc_attr( $item->term_id ); ?>"
                                       <?php checked( in_array( $item->term_id, (array) $kivalasztott ) ); ?> />
                                <?php echo esc_html( $item->name ); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="description" style="margin-top:8px;">
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=szallas_felszereltseg_tax&post_type=szallasok'); ?>" target="_blank">
                            + Új felszereltség hozzáadása / kezelése
                        </a>
                    </p>
                <?php else : ?>
                    <p>Még nincsenek felszereltség opciók.
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=szallas_felszereltseg_tax&post_type=szallasok'); ?>">Hozzáadás itt</a>.
                    </p>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php
}

// ── Térkép box ─────────────────────────────────────────────────────────────────
function bsza_render_terkep_box( $post ) {
    $lat = get_post_meta( $post->ID, 'szallas_lat', true );
    $lng = get_post_meta( $post->ID, 'szallas_lng', true );
    ?>
    <table class="bsza-meta-table">
        <tr>
            <th><label for="szallas_lat">Szélességi fok (lat)</label></th>
            <td><input type="text" id="szallas_lat" name="szallas_lat"
                       value="<?php echo esc_attr( $lat ); ?>"
                       placeholder="pl. 46.9536" class="bsza-gps-input" /></td>
        </tr>
        <tr>
            <th><label for="szallas_lng">Hosszúsági fok (lng)</label></th>
            <td><input type="text" id="szallas_lng" name="szallas_lng"
                       value="<?php echo esc_attr( $lng ); ?>"
                       placeholder="pl. 17.8922" class="bsza-gps-input" /></td>
        </tr>
    </table>
    <p class="description" style="margin-top:8px;">
        💡 Koordinátákat a <a href="https://www.google.com/maps" target="_blank">Google Maps</a>-en
        jobb kattintással tudod másolni. Formátum: tizedes ponttal, pl. <code>46.9536, 17.8922</code>
    </p>
    <?php if ( $lat && $lng ) : ?>
        <div style="margin-top:10px;">
            <a href="https://www.google.com/maps?q=<?php echo esc_attr($lat); ?>,<?php echo esc_attr($lng); ?>"
               target="_blank" class="button button-small">🗺️ Ellenőrzés Google Maps-en</a>
        </div>
    <?php endif; ?>
    <?php
}

// ── Galéria box ────────────────────────────────────────────────────────────────
function bsza_render_galeria_box( $post ) {
    $image_ids = get_post_meta( $post->ID, 'szallas_galeria_ids', true );
    if ( ! is_array( $image_ids ) ) $image_ids = array();
    ?>
    <div id="bsza-gallery-wrapper">
        <div id="bsza-gallery-preview" class="bsza-gallery-preview">
            <?php foreach ( $image_ids as $img_id ) :
                $img_url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
                if ( ! $img_url ) continue;
            ?>
                <div class="bsza-gallery-item" data-id="<?php echo intval( $img_id ); ?>">
                    <img src="<?php echo esc_url( $img_url ); ?>" alt="" />
                    <span class="bsza-remove-image" title="Eltávolítás">✕</span>
                </div>
            <?php endforeach; ?>
        </div>
        <input type="hidden" id="bsza_galeria_ids" name="bsza_galeria_ids"
               value="<?php echo esc_attr( implode( ',', $image_ids ) ); ?>" />
        <button type="button" id="bsza-add-images" class="button button-primary">
            + Képek hozzáadása / szerkesztése
        </button>
        <p class="description" style="margin-top:6px;">
            A képeket húzással átrendezd. Az első kép lesz a borítókép (ha nincs kiemelt kép beállítva).
        </p>
    </div>
    <?php
}

// ── Meta adatok mentése ────────────────────────────────────────────────────────
function bsza_save_meta( $post_id ) {
    if ( ! isset( $_POST['bsza_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['bsza_nonce'], 'bsza_save_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Szöveges mezők
    foreach ( array( 'szallas_hely_telepules', 'szallas_utca_hazszam', 'szallas_telefon',
                     'szallas_email', 'szallas_foglalas', 'szallas_lat', 'szallas_lng' ) as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
        }
    }

    // Max férőhelyek
    if ( isset( $_POST['szallas_max_ferohely'] ) ) {
        update_post_meta( $post_id, 'szallas_max_ferohely', absint( $_POST['szallas_max_ferohely'] ) );
    }

    // Típus mentése (taxonómia – radio)
    if ( isset( $_POST['bsza_tipus_id'] ) ) {
        $tipus_id = absint( $_POST['bsza_tipus_id'] );
        if ( $tipus_id > 0 ) {
            wp_set_post_terms( $post_id, array( $tipus_id ), 'szallas_tipus' );
        } else {
            wp_set_post_terms( $post_id, array(), 'szallas_tipus' );
        }
    }

    // Felszereltség mentése (taxonómia – checkbox)
    $felszereltseg_ids = array();
    if ( ! empty( $_POST['bsza_felszereltseg_ids'] ) && is_array( $_POST['bsza_felszereltseg_ids'] ) ) {
        foreach ( $_POST['bsza_felszereltseg_ids'] as $id ) {
            $felszereltseg_ids[] = absint( $id );
        }
    }
    wp_set_post_terms( $post_id, $felszereltseg_ids, 'szallas_felszereltseg_tax' );

    // Galéria képek
    $gallery_ids = array();
    if ( ! empty( $_POST['bsza_galeria_ids'] ) ) {
        foreach ( explode( ',', sanitize_text_field( $_POST['bsza_galeria_ids'] ) ) as $id ) {
            $id = absint( trim( $id ) );
            if ( $id > 0 ) $gallery_ids[] = $id;
        }
    }
    update_post_meta( $post_id, 'szallas_galeria_ids', $gallery_ids );

    // Térkép adatok cache törlése
    delete_transient( 'bsza_terkep_adatok' );
}
add_action( 'save_post_szallasok', 'bsza_save_meta' );

// ── Admin lista oszlopok ───────────────────────────────────────────────────────
function bsza_admin_columns( $columns ) {
    return array(
        'cb'               => $columns['cb'],
        'title'            => 'Szállás neve',
        'szallas_kep'      => 'Kép',
        'szallas_hely'     => 'Helyszín',
        'szallas_tipus'    => 'Típus',
        'szallas_ferohely' => 'Férőhely',
        'date'             => $columns['date'],
    );
}
add_filter( 'manage_szallasok_posts_columns', 'bsza_admin_columns' );

function bsza_admin_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'szallas_kep':
            echo has_post_thumbnail( $post_id )
                ? get_the_post_thumbnail( $post_id, array( 60, 60 ) )
                : '—';
            break;
        case 'szallas_hely':
            $t = get_post_meta( $post_id, 'szallas_hely_telepules', true );
            echo $t ? esc_html( $t ) : '—';
            break;
        case 'szallas_tipus':
            $terms = get_the_terms( $post_id, 'szallas_tipus' );
            echo ( $terms && ! is_wp_error( $terms ) ) ? esc_html( $terms[0]->name ) : '—';
            break;
        case 'szallas_ferohely':
            $f = get_post_meta( $post_id, 'szallas_max_ferohely', true );
            echo $f ? esc_html( $f ) . ' fő' : '—';
            break;
    }
}
add_action( 'manage_szallasok_posts_custom_column', 'bsza_admin_column_content', 10, 2 );
