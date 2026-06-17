<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$osszes_szallas = get_posts( array( 'post_type' => 'szallasok', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
$telepulesek = array();
foreach ( $osszes_szallas as $s ) {
    $t = get_post_meta( $s->ID, 'szallas_hely_telepules', true );
    if ( $t ) $telepulesek[ $t ] = $t;
}
asort( $telepulesek );

$tipusok         = get_terms( array( 'taxonomy' => 'szallas_tipus',           'hide_empty' => false ) );
$felszereltsegek = get_terms( array( 'taxonomy' => 'szallas_felszereltseg_tax', 'hide_empty' => false, 'orderby' => 'name' ) );
?>
<div class="bsza-wrap">
<div class="szallasok-filters">
    <form id="szallas-filter">

        <!-- Lista / Térkép váltó – segmented control -->
        <div class="bsza-view-toggle" role="group" aria-label="Nézet váltás">
            <a href="#" role="button" id="bsza-view-lista" class="bsza-view-btn active">
                <i class="fas fa-th-large"></i> Lista
            </a>
            <a href="#" role="button" id="bsza-view-terkep" class="bsza-view-btn">
                <i class="fas fa-map-marked-alt"></i> Térkép
            </a>
        </div>

        <div class="bsza-filter-title"><i class="fas fa-sliders-h"></i> Szállások szűrése</div>

        <!-- Szöveges kereső -->
        <div class="filter-group bsza-search-group">
            <label for="bsza-kereses"><i class="fas fa-search"></i> Keresés</label>
            <input type="search" id="bsza-kereses" name="kereses" class="bsza-input"
                   placeholder="Szállás neve, kulcsszó…" />
        </div>

        <!-- Rendezés -->
        <div class="filter-group">
            <label for="sort"><i class="fas fa-sort"></i> Rendezés</label>
            <select id="sort" name="sort" class="sort-select">
                <option value="date_desc">Legújabb elöl</option>
                <option value="date_asc">Legrégebbi elöl</option>
                <option value="name_asc">Név szerint (A-Z)</option>
                <option value="name_desc">Név szerint (Z-A)</option>
                <option value="city_asc">Település szerint (A-Z)</option>
                <option value="ferohely_asc">Legkisebb férőhely elöl</option>
            </select>
        </div>

        <?php if ( ! empty( $telepulesek ) ) : ?>
        <div class="filter-group">
            <label for="telepules"><i class="fas fa-map-marker-alt"></i> Település</label>
            <select id="telepules" name="telepules">
                <option value="">Összes település</option>
                <?php foreach ( $telepulesek as $t ) : ?>
                    <option value="<?php echo esc_attr( $t ); ?>"><?php echo esc_html( $t ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $tipusok ) && ! is_wp_error( $tipusok ) ) : ?>
        <div class="filter-group">
            <label for="tipus"><i class="fas fa-home"></i> Típus</label>
            <select id="tipus" name="tipus">
                <option value="">Összes típus</option>
                <?php foreach ( $tipusok as $tipus ) : ?>
                    <option value="<?php echo esc_attr( $tipus->slug ); ?>"><?php echo esc_html( $tipus->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="filter-group">
            <label for="min_ferohely"><i class="fas fa-users"></i> Min. férőhely</label>
            <select id="min_ferohely" name="min_ferohely">
                <option value="">Bármennyi</option>
                <option value="2">2+ fő</option>
                <option value="4">4+ fő</option>
                <option value="6">6+ fő</option>
                <option value="10">10+ fő</option>
            </select>
        </div>

        <?php if ( ! empty( $felszereltsegek ) && ! is_wp_error( $felszereltsegek ) ) : ?>
        <div class="filter-felszereltseg">
            <label class="felszereltseg-label"><i class="fas fa-couch"></i> Felszereltség</label>
            <div class="felszereltseg-options">
                <?php foreach ( $felszereltsegek as $f ) : ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="felszereltseg[]" value="<?php echo esc_attr( $f->slug ); ?>">
                        <?php echo esc_html( $f->name ); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="bsza-filter-actions">
            <a href="#" role="button" class="filter-button"
               onclick="document.getElementById('szallas-filter').dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));return false;">
                <i class="fas fa-search"></i> Szűrés
            </a>
            <a href="#" role="button" class="bsza-reset-btn reset-filters">
                <i class="fas fa-rotate-left"></i> Visszaállítás
            </a>
        </div>
    </form>
</div>
</div><!-- /.bsza-wrap -->

<div class="loading-overlay">
    <div class="loading-spinner"></div>
</div>
