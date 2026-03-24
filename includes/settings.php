<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Beállítások oldal regisztrálása ───────────────────────────────────────────
function bsza_register_settings_page() {
    add_submenu_page(
        'edit.php?post_type=szallasok',
        'Plugin beállítások',
        '⚙️ Beállítások',
        'manage_options',
        'bsza-settings',
        'bsza_render_settings_page'
    );
}
add_action( 'admin_menu', 'bsza_register_settings_page' );

function bsza_register_settings() {
    register_setting( 'bsza_settings_group', 'bsza_google_maps_api_key', array(
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    register_setting( 'bsza_settings_group', 'bsza_lista_oldal_url', array(
        'sanitize_callback' => 'esc_url_raw',
    ) );
}
add_action( 'admin_init', 'bsza_register_settings' );

// ── Beállítások oldal megjelenítése ───────────────────────────────────────────
function bsza_render_settings_page() {
    $api_key   = get_option( 'bsza_google_maps_api_key', '' );
    $lista_url = get_option( 'bsza_lista_oldal_url', '' );
    ?>
    <div class="wrap">
        <h1>🏖️ Balatoni Szállások – Beállítások</h1>

        <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
            <div class="notice notice-success is-dismissible">
                <p>✅ Beállítások elmentve!</p>
            </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'bsza_settings_group' ); ?>

            <table class="form-table">

                <!-- Google Maps API kulcs -->
                <tr>
                    <th scope="row">
                        <label for="bsza_google_maps_api_key">Google Maps API kulcs</label>
                    </th>
                    <td>
                        <input type="text"
                               id="bsza_google_maps_api_key"
                               name="bsza_google_maps_api_key"
                               value="<?php echo esc_attr( $api_key ); ?>"
                               class="regular-text"
                               placeholder="AIzaSy..." />
                        <p class="description">
                            Szükséges a térkép nézet működéséhez.<br>
                            API kulcsot a <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>-ban tudsz létrehozni.<br>
                            Szükséges API-k: <strong>Maps JavaScript API</strong> + <strong>Geocoding API</strong>.
                        </p>
                        <?php if ( ! $api_key ) : ?>
                            <p style="color:#d63638; font-weight:500;">
                                ⚠️ API kulcs nincs megadva – a térkép nézet nem fog működni.
                            </p>
                        <?php else : ?>
                            <p style="color:#00a32a; font-weight:500;">
                                ✅ API kulcs beállítva.
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Szállások lista oldal URL -->
                <tr>
                    <th scope="row">
                        <label for="bsza_lista_oldal_url">Szállások lista oldal URL</label>
                    </th>
                    <td>
                        <input type="url"
                               id="bsza_lista_oldal_url"
                               name="bsza_lista_oldal_url"
                               value="<?php echo esc_attr( $lista_url ); ?>"
                               class="regular-text"
                               placeholder="https://aktivbalaton.hu/balatoni-szallasok/" />
                        <p class="description">
                            Az egyedi szállás oldalon megjelenő „← Vissza a szállásokhoz" gomb linkje.<br>
                            Általában a <code>[balaton_szallasok_lista]</code> shortcode-ot tartalmazó oldal URL-je.
                        </p>
                        <?php if ( ! $lista_url ) : ?>
                            <p style="color:#b45309; font-weight:500;">
                                ⚠️ Nincs megadva – a „Vissza" gomb nem jelenik meg az egyedi szállás oldalakon.
                            </p>
                        <?php else : ?>
                            <p style="color:#00a32a; font-weight:500;">
                                ✅ Lista oldal beállítva: <a href="<?php echo esc_url( $lista_url ); ?>" target="_blank"><?php echo esc_html( $lista_url ); ?></a>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

            </table>

            <?php submit_button( 'Beállítások mentése' ); ?>
        </form>

        <hr>
        <h2>📋 Shortcode-ok</h2>
        <table class="widefat" style="max-width:600px;">
            <thead><tr><th>Shortcode</th><th>Leírás</th></tr></thead>
            <tbody>
                <tr><td><code>[balaton_szallasok_filter]</code></td><td>Szűrő panel + Lista/Térkép váltó</td></tr>
                <tr><td><code>[balaton_szallasok_lista]</code></td><td>Szálláslista (kártyák + térkép)</td></tr>
            </tbody>
        </table>

        <hr>
        <h2>🔗 Egyedi szállás URL-ek</h2>
        <p>Minden szállás automatikusan kap egy önálló SEO-barát oldalt:</p>
        <code>https://aktivbalaton.hu/szallasok/<strong>szallas-neve</strong>/</code>
        <p class="description" style="margin-top:8px;">
            Ha az URL-ek 404-es hibát adnak, menj a <strong>Beállítások → Permalinkek</strong> menübe
            és kattints a „Módosítások mentése" gombra az újraírási szabályok frissítéséhez.
        </p>
    </div>
    <?php
}
