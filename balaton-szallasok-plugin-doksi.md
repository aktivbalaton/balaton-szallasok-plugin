# Balaton Szállások WordPress Plugin – Fejlesztési dokumentáció

**Verzió:** 2.9.6  
**Webhely:** aktivbalaton.hu  
**Plugin mappa a szerveren:** `/wp-content/plugins/balaton-szallasok/`  
**Plugin mappa a fejlesztői gépen:** `E:\aktivbalaton.hu\Saját pluginok\balaton-szallasok\`

> ⚠️ **Megjegyzés a verziószámról:** A `balaton-szallasok.php` fejlécében (`* Version:`) és a `define('BSZA_VERSION', ...)` sorban mindig ugyanaz a verzió legyen, és pontozott formátum (pl. `2.9.6`). A WP a fejléc verziót mutatja a Bővítmények oldalon, a `BSZA_VERSION` konstans a CSS/JS cache-töréshez kell. Ha eltérnek, a böngésző régi stílust tölthet be.

---

## 1. Plugin célja

Balatoni szállások kezelése és megjelenítése az aktivbalaton.hu WordPress weboldalon. Teljesen önálló plugin, **nem függ az ACF-től** (Advanced Custom Fields). A szállások egy dedikált WordPress oldalon jelennek meg (`/balatoni-szallasok`), shortcode-ok segítségével. Minden szállás emellett kap egy önálló SEO-barát URL-t is (`/szallasok/szallas-neve/`).

---

## 2. Fájlstruktúra

```
balaton-szallasok/
├── balaton-szallasok.php          ← Fő plugin fájl (konstansok, Gutenberg blokkolás, single_template filter, asset betöltés)
├── .cpanel.yml                    ← cPanel automatikus deploy konfig (git push → szerver frissül)
├── deploy.bat                     ← Egyszerűsített deploy script (Windows, dupla kattintás → git add+commit+push)
├── includes/
│   ├── cpt.php                    ← Custom Post Type + 2 taxonómia regisztrálása
│   ├── meta-boxes.php             ← Admin szerkesztő mezők + mentés + admin oszlopok
│   ├── shortcodes.php             ← 2 shortcode regisztrálása
│   ├── ajax-handlers.php          ← AJAX szűrő + modal adat endpoint
│   └── settings.php               ← Plugin beállítások oldal (Google Maps API kulcs + lista oldal URL)
├── templates/
│   ├── card-template.php          ← Egy szállás kártyája (Schema.org + modal adat + saját oldal link)
│   ├── filter-template.php        ← Szűrő panel + Lista/Térkép váltó gombok
│   ├── lista-template.php         ← Szálláslista grid + Load More gomb + Térkép konténer
│   └── single-szallasok.php       ← Egyedi szállás oldal sablon (SEO oldal)
├── assets/
│   ├── css/
│   │   ├── frontend.css           ← Minden frontend stílus (kártyák, szűrő, modal, térkép, single oldal)
│   │   └── admin.css              ← Admin stílusok (meta boxok, galéria drag-drop)
│   └── js/
│       ├── frontend.js            ← Szűrés AJAX, Load More, Modal, Lightbox, Google Maps, single oldal galéria
│       └── admin.js               ← Drag-drop képgaléria (WordPress Media Library)
```

---

## 3. Custom Post Type és Taxonómiák

**CPT neve:** `szallasok`  
**Slug:** `/szallasok/`  
**Gutenberg:** kikapcsolva (klasszikus szerkesztő fut)  
**Supports:** `title`, `editor` (leírás), `thumbnail` (kiemelt kép)

### Taxonómiák

| Taxonómia | Slug | Típus | Admin menü |
|-----------|------|-------|-----------|
| `szallas_tipus` | `szallas-tipus` | hierarchikus | My Szállások → Típusok |
| `szallas_felszereltseg_tax` | `szallas-felszereltseg` | lapos | My Szállások → Felszereltségek |

**Alap típusok:** Apartman, Hotel, Vendégház, Kemping, Panzió, Szoba kiadó  
**Alap felszereltségek:** WiFi, Parkoló, Medence, Légkondicionálás, Kisállat fogadása, Reggeli, Kert / Terasz, Mosógép, TV, Pótágy lehetséges, Grillterasz, Kerékpártároló, Szauna

> Új típus/felszereltség hozzáadása: WordPress admin → My Szállások → Típusok / Felszereltségek

---

## 4. Admin szerkesztő mezők (Meta Boxok)

A szállás szerkesztőjében az alábbi saját meta boxok jelennek meg (felülről lefelé):

| Meta Box | Mezők | Mentési kulcs |
|----------|-------|--------------|
| 📍 Helyszín adatok | Település *, Utca/házszám | `szallas_hely_telepules`, `szallas_utca_hazszam` |
| 📞 Kapcsolat & foglalás | Telefonszám, E-mail, Foglalási link | `szallas_telefon`, `szallas_email`, `szallas_foglalas` |
| 🏠 Szállás típusa | Radio button lista (taxonómiából) | `szallas_tipus` taxonómia |
| 🛎️ Szállás részletei | Max. férőhelyek, Felszereltség checkboxok | `szallas_max_ferohely`, `szallas_felszereltseg_tax` taxonómia |
| 🗺️ Térkép / GPS koordináta | Lat, Lng | `szallas_lat`, `szallas_lng` |
| 🖼️ Képgaléria (drag & drop) | WordPress Media Library képek | `szallas_galeria_ids` (ID tömb) |

**Leírás mező:** a WordPress beépített szerkesztője (post_content) – nincs külön meta box.  
**Kiemelt kép:** WordPress beépített funkció, ez lesz a főkép.

> ⚠️ Az „Egyedi mezők" panel el van rejtve – ez szándékos, a saját meta boxok kezelik az adatokat.

---

## 5. Shortcode-ok

```
[balaton_szallasok_filter]   ← Szűrő panel + Lista/Térkép váltó
[balaton_szallasok_lista]    ← Szálláslista kártyák + térkép nézet
```

A `Balatoni szállások` WordPress oldalon mindkét shortcode egymás után van elhelyezve Gutenberg Shortcode blokkban.

---

## 6. Frontend funkciók

### 6.1 Szállás kártya (lista nézet)
- Fix 160px magasságú főkép, `object-fit: cover`, `object-position: center center` – lying képekhez optimalizálva
- Thumbnail sor (34×34px) ha több kép van
- Cím, helyszín, típus, max. férőhely, telefon, email, foglalás link
- Max. 3 felszereltség tag, `+N további` jelzés
- **Akció sor:** „Részletek megtekintése" gomb (modal) + kis körös ⤢ ikon (egyedi oldal megnyitása)
- 3 oszlopos grid desktop >1100px, 2 oszlop 768–1100px, 1 oszlop <768px

### 6.2 Egyedi szállás oldal (`/szallasok/szallas-neve/`)
- Plugin saját sablonja (`single-szallasok.php`), `single_template` filterrel töltődik be
- Kétoszlopos elrendezés: galéria bal oldalon (lightbox + thumbnail sor + képszámláló), adatok jobb oldalon
- Tartalom: cím, helyszín/típus/férőhely meta, teljes leírás, telefon/email/foglalás/útvonal linkek, felszereltség, Google Maps iframe
- „← Vissza a szállásokhoz" gomb (ha be van állítva a lista oldal URL)
- Mobilon: egymás alá rendezett egységes elrendezés

### 6.3 Modal ablak
- Bal oldal: képgaléria lightbox (nyilak, thumbnail sor, teljes képernyős nézet)
- Jobb oldal: minden adat, teljes leírás, foglalás gomb, Google Maps iframe
- Bezárás: X gomb, háttérre kattintás, ESC
- Nyíl billentyűk: képek lapozása

### 6.4 Szűrő panel
- Lista/Térkép váltó gombok (outline stílusú, kerekített, `!important` az Elementor kit felülírás ellen)
- Cím: `.bsza-filter-title` osztályú `<div>` – **nem** `<h2>`, mert a téma h2 stílusa felülírná
- Rendezés (6 opció)
- Település dropdown (a feltöltött szállásokból töltődik)
- Típus dropdown (taxonómiából, `hide_empty: false`)
- Min. férőhelyek (2/4/6/10+ fő)
- Felszereltség checkboxok 2 oszlopos gridben (taxonómiából, `hide_empty: false`)
- Szűrés gomb → AJAX lekérdezés

### 6.5 Load More gomb
- Első betöltés: 9 szállás (`$per_page = 9` – módosítható a `lista-template.php`-ban)
- Gombra kattintva: következő 9 betöltése, számlálóval `(9 / 47)`
- Minden betöltve: zöld „✓ Minden szállás betöltve" üzenet

### 6.6 Lista / Térkép váltás
- Pill-stílusú váltó gombok a szűrő panel tetején
- **Lista nézet:** kártyák grid + Load More
- **Térkép nézet:** Google Maps, minden szállás kék gombostűvel
- Gombostűre kattintva: InfoWindow (borítókép, adatok, Foglalás + Részletek gomb)
- Részletek gomb → ugyanaz a Modal nyílik

---

## 7. Google Maps integráció

**API kulcs beállítása:** WordPress admin → My Szállások → ⚙️ Beállítások  
**Option neve:** `bsza_google_maps_api_key`  
**Szükséges Google API-k:** Maps JavaScript API

**Fontos:** az EventON plugin szintén betölti a Google Maps API-t az oldalon. A plugin ezt detektálja és nem tölti be kétszer. Ha mégis dupla betöltési hiba jelentkezik, a `balaton-szallasok.php`-ban lévő `$maps_already_loaded` ellenőrző logika felelős ezért.

**GPS koordináták megadása:** szállás szerkesztője → Térkép / GPS koordináta mező  
Format: tizedes ponttal, pl. `46.9536` / `17.8922`  
Csak azok a szállások jelennek meg a térképen, amelyeknek van koordinátája.

---

## 8. Schema.org struktúrált adat

Minden kártyához JSON-LD formátumban kerül beillesztésre.

| Szállás típus | Schema @type |
|--------------|-------------|
| Apartman | `ApartmentComplex` |
| Hotel | `Hotel` |
| Vendégház / Panzió | `BedAndBreakfast` |
| Kemping | `Campground` |
| Egyéb | `LodgingBusiness` |

Tartalmazza: név, URL, leírás, cím, GPS koordináta, telefon, email, foglalási URL, borítókép, felszereltség lista.

---

## 9. AJAX végpontok

| Action | Függvény | Leírás |
|--------|----------|--------|
| `filter_szallasok` | `bsza_filter_szallasok()` | Szűrés + lapozás, HTML kártyákat ad vissza |
| `bsza_get_modal_data` | `bsza_get_modal_data()` | Egy szállás összes adata JSON-ban (térkép markerekhez) |

Mindkét endpoint nonce-szal védett (`szallasok_nonce`).

---

## 10. Ismert problémák és megoldásaik

| Probléma | Ok | Megoldás |
|----------|-----|----------|
| Gutenberg szerkesztő jelenik meg, meta boxok nem látszanak | Gutenberg filter nem fut le | A filter a `balaton-szallasok.php` legelején van, require-ok ELŐTT |
| Yoast SEO kritikus hiba szerkesztőben | `cpt.php`-ban maradhat régi Yoast callback kód | `cpt.php`-ban NE legyen semmilyen `WPSEO_*` hivatkozás |
| Egyedi mezők panel megjelenik | `remove_meta_box` hook nem futott | A hook `balaton-szallasok.php`-ban ÉS `cpt.php`-ban is benne van, priority: 99 |
| Google Maps dupla betöltés | EventON plugin is betölti | `balaton-szallasok.php`-ban `$maps_already_loaded` ellenőrzés |
| Térkép nézet üres, nincs térkép | Dupla betöltés miatt callback ütközés | `frontend.js`-ben: ha `google.maps` már létezik, manuálisan hívjuk `bszaInitMap()` |
| Álló kép rosszul néz ki a kártyán | Álló tájolású kép a fix 160px keretben | A főképeket **fekvő tájolásban** kell feltölteni; a plugin `object-fit: cover`-t használ |
| Gombok túl nagyok, nem reagálnak a CSS-re | Elementor kit (`post-1739.css`) globálisan felülírja a `button` elemeket `padding: 14px 28px`-szel | Minden gomb CSS-szabályán `!important` szükséges a `.bsza-view-btn`, `.filter-button`, `.szallas-reszletek-btn` osztályokon |
| Verziószám nem frissül a Bővítmények oldalon | A `* Version:` fejléc és `define('BSZA_VERSION')` eltér | Mindig mindkettőt frissíteni kell ugyanarra az értékre, pontozott formátumban (pl. `2.9.6`) |
| 404-es hiba az egyedi szállás URL-en | Permalink szabályok nem frissültek | Beállítások → Permalinkek → Módosítások mentése |

---

## 11. CSS osztályok – gyors referencia

### Frontend (frontend.css)

| Elem | CSS osztály |
|------|------------|
| Szűrő panel | `.szallasok-filters` |
| Szűrő cím (nem h2!) | `.bsza-filter-title` |
| Lista/Térkép váltó konténer | `.bsza-view-toggle` |
| Lista/Térkép váltó gombok | `.bsza-view-btn`, `.bsza-view-btn.active` |
| Lista nézet konténer | `#bsza-lista-nezet` |
| Térkép nézet konténer | `#bsza-terkep-nezet` |
| Google Map div | `#bsza-google-map` |
| Kártya grid | `.szallasok-grid` |
| Kártya | `.szallas-card` |
| Kártya kép | `.szallas-image` (fix 160px, cover) |
| Thumbnails | `.szallas-thumbnails`, `.thumbnail` |
| Kártya akció sor | `.szallas-card-actions` |
| Részletek gomb (modal) | `.szallas-reszletek-btn` |
| Saját oldal ikon link | `.szallas-oldal-link` |
| Modal overlay | `#bsza-modal-overlay` |
| Modal | `#bsza-modal` |
| Modal galéria | `.bsza-modal-gallery` |
| Modal infó | `.bsza-modal-info` |
| Lightbox fullscreen | `#bsza-lightbox-fs` |
| Load More gomb | `.load-more-btn` |
| Single oldal wrapper | `.bsza-single-wrap` |
| Single oldal belső grid | `.bsza-single-inner` |
| Single oldal galéria | `.bsza-single-gallery` |
| Single oldal info | `.bsza-single-info` |
| Single visszalépés link | `.bsza-single-back` |

### Admin (admin.css)

| Elem | CSS osztály |
|------|------------|
| Meta tábla | `.bsza-meta-table` |
| Checkbox grid (típus/felszereltség) | `.bsza-checkbox-grid`, `.bsza-checkbox-item` |
| Galéria preview | `.bsza-gallery-preview` |
| Galéria elem | `.bsza-gallery-item`, `.bsza-remove-image` |

---

## 12. JS globális változók és függvények

### szallasokAjax (wp_localize_script)
```javascript
szallasokAjax.ajaxurl    // admin-ajax.php URL
szallasokAjax.nonce      // szallasok_nonce
szallasokAjax.hasMapKey  // '1' ha van API kulcs, '0' ha nincs
szallasokAjax.mapCenter  // { lat: 46.8389, lng: 17.8868 } – Balaton közepe
szallasokAjax.mapZoom    // 10
```

### bszaTerképAdatok (lista-template.php inline script)
```javascript
// Tömb: minden koordinátával rendelkező szállás adatai
bszaTerképAdatok = [{ id, cim, lat, lng, telepules, tipus, ferohely, telefon, foglalas, kep }]
```

### bszaSingleImages (single-szallasok.php inline script)
```javascript
// Tömb: az aktuális egyedi szállás oldal képei
bszaSingleImages = [{ url, thumb }]
```

### Globális térkép változók (frontend.js)
```javascript
bszaMap         // google.maps.Map instance
bszaMarkers[]   // google.maps.Marker tömb
bszaInfoWindow  // google.maps.InfoWindow instance
bszaMapReady    // boolean – megakadályozza a dupla init-et
```

### Globális callback (Google Maps)
```javascript
bszaInitMap()   // A Google Maps API ezt hívja a betöltés után
```

---

## 13. PHP konstansok

```php
BSZA_VERSION   // pl. '2.9.6'
BSZA_PATH      // plugin_dir_path(__FILE__)
BSZA_URL       // plugin_dir_url(__FILE__)
```

---

## 14. WordPress Options (adatbázis)

| Option neve | Tartalom |
|-------------|---------|
| `bsza_google_maps_api_key` | Google Maps API kulcs (string) |
| `bsza_lista_oldal_url` | A szállások lista oldal URL-je – a single oldalon megjelenő „Vissza" gombhoz |

---

## 15. Post Meta kulcsok

| Meta kulcs | Típus | Leírás |
|------------|-------|--------|
| `szallas_hely_telepules` | string | Település neve |
| `szallas_utca_hazszam` | string | Utca, házszám |
| `szallas_telefon` | string | Telefonszám |
| `szallas_email` | string | E-mail cím |
| `szallas_foglalas` | string (URL) | Foglalási link |
| `szallas_max_ferohely` | int | Max. férőhelyek száma |
| `szallas_lat` | float (string) | GPS szélességi fok |
| `szallas_lng` | float (string) | GPS hosszúsági fok |
| `szallas_galeria_ids` | array (int[]) | Galéria kép attachment ID-k |

**Taxonómiák** (nem post meta, hanem `wp_term_relationships`):
- `szallas_tipus` – szállás típusa (radio, 1 db)
- `szallas_felszereltseg_tax` – felszereltség (checkbox, több db)

---

## 16. Elementor kit felülírás – fontos tudnivaló

Az aktivbalaton.hu oldalon az Elementor kit (`post-1739.css`) globálisan felülírja az összes `button` elemet:

```css
.elementor-kit-1739 button { padding: 14px 28px; font-size: 15px; }
```

Ez erősebb specificitású mint egy sima osztályszabály, ezért a plugin összes gombjára **`!important`** szükséges a padding, font-size, background, border, border-radius és line-height tulajdonságokon. Érintett osztályok: `.bsza-view-btn`, `.filter-button`, `.szallas-reszletek-btn`.

Ha új gombot adsz a pluginhoz, rögtön adj hozzá `!important`-ot a padding és font-size értékekre.

---

## 17. Technikai fejlesztések

Ez a szekció olyan fejlesztési feladatokat tartalmaz, amelyek egy új Claude-beszélgetésben önállóan elvégezhetők. Minden feladathoz megadjuk a pontos problémát, az okát és a javasolt megoldást.

---

### 17.1 `!important` áradat eltávolítása – wrapper osztály bevezetése ✅ KÉSZ (v3.0.0)

**Prioritás:** Magas  
**Érintett fájlok:** `assets/css/frontend.css`, `templates/filter-template.php`, `templates/lista-template.php`, `templates/card-template.php`, `templates/single-szallasok.php`

**A probléma:**  
Az aktivbalaton.hu Elementor kit (`post-1739.css`) globálisan felülírja az összes `button` elemet:
```css
.elementor-kit-1739 button { padding: 14px 28px; font-size: 15px; }
```
Ennek legyőzésére jelenleg 15+ `!important` szerepel a `frontend.css`-ben, ami törékeny – ha az Elementor kit erősebb selectort kap, megint elcsúszik minden.

**A megoldás:**  
Vezess be egy `.bsza-wrap` konténer osztályt a plugin összes frontend kimenetére. A `.bsza-wrap button` selector specificitása magasabb mint `.elementor-kit-1739 button`, ezért az összes `!important` elhagyható.

**Pontos teendők:**

1. `filter-template.php`: a legkülső `<div class="szallasok-filters">` elé adj egy `<div class="bsza-wrap">` nyitót, a fájl végén zárd be.
2. `lista-template.php`: a `<div class="szallasok-container">` köré adj `<div class="bsza-wrap">` wrapper-t.
3. `single-szallasok.php`: a `<div class="bsza-single-wrap">` már jó konténer, csak add hozzá a `bsza-wrap` osztályt: `<div class="bsza-single-wrap bsza-wrap">`.
4. `frontend.css`-ben:
   - Adj hozzá alap wrapper reset-et:
     ```css
     .bsza-wrap button { all: unset; }
     ```
     Vagy célzottabban, csak a problémás property-kre:
     ```css
     .bsza-wrap button { padding: revert; font-size: revert; }
     ```
   - Ezután az összes `.bsza-view-btn`, `.filter-button`, `.szallas-reszletek-btn` szabályból töröld ki az `!important` jelölőket.
   - A specificitás ellenőrzéséhez: `.bsza-wrap .bsza-view-btn` (0,2,0) > `.elementor-kit-1739 button` (0,1,1) ✓

**Megvalósítás:** A `.bsza-wrap` wrapper osztály bevezetése helyett a gombok `<button>` elemről `<a role="button">` elemre lettek cserélve – ez garantáltan kivédi az Elementor kit `button` selectorát, semmilyen specificitás-trükk nem szükséges.

**Érintett fájlok:** `frontend.css`, `filter-template.php`, `card-template.php`

---

### 17.2 Verziószám automatizálása – egyetlen forrás ✅ KÉSZ (v3.0.0)

**Prioritás:** Magas  
**Érintett fájlok:** `balaton-szallasok.php`

**A probléma:**  
Jelenleg a verziószám két helyen szerepel a `balaton-szallasok.php`-ban:
```php
 * Version: 2.9.6          ← WP ezt olvassa (Bővítmények oldal)
define('BSZA_VERSION', '2.9.6');  ← CSS/JS cache-töréshez
```
Ha csak az egyiket módosítjuk (ami többször megtörtént), a WP régi verziót mutat vagy a böngésző régi CSS-t tölt be.

**A megoldás:**  
Olvasd ki a verziót programozottan a plugin fejlécéből, hogy csak egy helyen kelljen módosítani:

```php
// balaton-szallasok.php - a define() sort cseréld erre:
$bsza_plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
define( 'BSZA_VERSION', $bsza_plugin_data['Version'] );
```

Ezután csak a `* Version: X.X.X` fejlécsort kell módosítani, a `define()` automatikusan felveszi az értéket.

**Fontos:** A `get_file_data()` WordPress függvény, csak WP környezetben érhető el – de mivel a plugin csak WP-ben fut, ez nem probléma. A függvény az `ABSPATH . 'wp-includes/functions.php'`-ban van definiálva, ami betöltődik mire a plugin fut.

**Megvalósítás:** A `get_file_data()` alapú automatikus verzióolvasás be van vezetve. Mostantól csak a `* Version:` fejlécsort kell módosítani.

---

### 17.3 Térkép teljesítmény – nagy szállásszám esetén

**Prioritás:** Közepes (de időben kritikus – nyár előtt megoldandó)  
**Érintett fájlok:** `templates/lista-template.php`, `includes/ajax-handlers.php`

**A probléma:**  
A `lista-template.php` jelenleg az összes szállást lekérdezi a térképadatokhoz:
```php
$osszes = get_posts( array( 'post_type' => 'szallasok', 'posts_per_page' => -1, ... ) );
```
Minden oldalletöltéskor lefut ez a lekérdezés, majd minden egyes szálláshoz külön `get_post_meta()` hívások mennek (N+1 probléma). 50 szállásnál már érezhető, 200+ szállásnál komoly lassulás várható.

**Javasolt megoldás – Transziens cache:**

```php
// lista-template.php-ban csere:
$terkep_adatok = get_transient( 'bsza_terkep_adatok' );

if ( false === $terkep_adatok ) {
    $osszes = get_posts( array( 'post_type' => 'szallasok', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
    $terkep_adatok = array();
    foreach ( $osszes as $sz ) {
        $lat = get_post_meta( $sz->ID, 'szallas_lat', true );
        $lng = get_post_meta( $sz->ID, 'szallas_lng', true );
        if ( ! $lat || ! $lng ) continue;
        // ... (meglévő logika)
        $terkep_adatok[] = array( ... );
    }
    set_transient( 'bsza_terkep_adatok', $terkep_adatok, 6 * HOUR_IN_SECONDS );
}
```

A cache törlése automatikusan, szállás mentésekor:
```php
// meta-boxes.php bsza_save_meta() függvény végére:
delete_transient( 'bsza_terkep_adatok' );
```

És új szállás publikálásakor:
```php
// balaton-szallasok.php-ba (vagy meta-boxes.php-ba):
add_action( 'publish_szallasok', function() {
    delete_transient( 'bsza_terkep_adatok' );
});
add_action( 'trash_szallasok', function() {
    delete_transient( 'bsza_terkep_adatok' );
});
```

**Eredmény:** Az első látogató felépíti a cache-t, a többi látogató adatbázis-lekérdezés nélkül kapja a térképadatokat. Szállás mentésekor automatikusan frissül.

**Opcionális további lépés – ha 200+ szállás lesz:**  
A markerekhez küldött adatok mennyiségét csökkenteni lehet: csak `id, lat, lng, cim, telepules` mezőket küldeni, és a részletes adatokat (fotó, telefon stb.) csak InfoWindow megnyitáskor AJAX-szal lekérni. Ez viszont már egy nagyobb refaktor, most nem szükséges.

---

### 17.3 Térkép teljesítmény – nagy szállásszám esetén ✅ KÉSZ (v3.0.0)

**Prioritás:** Közepes

**Megvalósítás:** Transziens cache bevezetve (`bsza_terkep_adatok`, 6 óra). Cache törlés automatikusan történik szállás mentésekor (`save_post_szallasok`), publikálásakor (`publish_szallasok`), kukába helyezésekor (`trash_szallasok`) és törlésekor (`delete_post`).

---

## 18. Tervezett / lehetséges jövőbeli fejlesztések

- [ ] **Ár / éjszaka mező** – tól-ig ársáv, ár szerinti szűrő
- [ ] **Duplikálás gomb** – szállás másolása egy kattintással az admin listában
- [ ] **Import/Export CSV** – tömeges szállás feltöltés
- [ ] **Értékelések / csillagok** – vendég vélemények
- [x] ~~**Saját szállás URL**~~ – elkészült (v2.7), `/szallasok/szallas-neve/` URL, `single-szallasok.php` sablon
- [ ] **Portál API integráció** – külső alkalmazásból (pl. az aktivbalaton.hu portálból) szállás adatok feltöltése REST API-n keresztül. Részletes terv lent.

### Portál API integráció – részletes terv

**Cél:** Az aktivbalaton.hu portálból (ahol jelenleg eseményeket lehet kezelni) közvetlenül lehessen új szállást felvinni és meglévőt szerkeszteni, anélkül hogy a WordPress adminba kellene belépni.

**Hogyan működne:**
A portál egy HTTP POST kérést küld a WordPress-nek → a plugin fogadja és elmenti az adatokat pontosan úgy, mintha az adminban töltötték volna fel.

**Szükséges fejlesztések:**

1. **Új fájl: `includes/rest-api.php`** – REST API endpoint regisztrálása:
   ```php
   add_action( 'rest_api_init', function() {
       register_rest_route( 'bsza/v1', '/szallas', array(
           'methods'             => 'POST',
           'callback'            => 'bsza_rest_create_szallas',
           'permission_callback' => 'bsza_rest_auth',
       ));
       register_rest_route( 'bsza/v1', '/szallas/(?P<id>\d+)', array(
           'methods'             => 'PUT',
           'callback'            => 'bsza_rest_update_szallas',
           'permission_callback' => 'bsza_rest_auth',
       ));
   });
   ```
   Az endpoint ugyanazokat a meta kulcsokat használja, amelyek a 15. szekcióban dokumentálva vannak.

2. **Hitelesítés:** WordPress Application Passwords (5.6+ óta beépített) – a portál egy API kulccsal azonosítja magát, nem kell jelszót tárolni.

3. **Képfeltöltés:** A WordPress Media API-n keresztül (`/wp/v2/media` endpoint) – a portál először feltölti a képet, visszakapja az attachment ID-t, majd azt küldi a szállás adataival együtt.

4. **`balaton-szallasok.php`-ba** felvenni a require-t:
   ```php
   require_once BSZA_PATH . 'includes/rest-api.php';
   ```

**API végpontok a kész rendszerben:**

| Metódus | URL | Leírás |
|---------|-----|--------|
| POST | `/wp-json/bsza/v1/szallas` | Új szállás létrehozása |
| PUT | `/wp-json/bsza/v1/szallas/{id}` | Meglévő szállás frissítése |
| GET | `/wp-json/bsza/v1/szallas/{id}` | Szállás adatainak lekérdezése |

**Megjegyzés:** A 15. szekcióban lévő post meta kulcsok listája lesz az API request body dokumentációja – ezeket kell a portálnak JSON-ben küldenie.

---

## 18. Telepítési / frissítési útmutató

### Első telepítés
1. ZIP feltöltése cPanel → Fájlkezelő → `/wp-content/plugins/` → Kicsomagolás
2. WordPress admin → Bővítmények → Aktiválás
3. Admin → My Szállások → ⚙️ Beállítások → Google Maps API kulcs megadása
4. Admin → My Szállások → ⚙️ Beállítások → Szállások lista oldal URL megadása (pl. `https://aktivbalaton.hu/balatoni-szallasok/`)
5. **Beállítások → Permalinkek → Módosítások mentése** (fontos az egyedi szállás URL-ekhez!)
6. Shortcode-ok elhelyezése a „Balatoni szállások" oldalon:
   ```
   [balaton_szallasok_filter]
   [balaton_szallasok_lista]
   ```

### Fájl frissítésekor
- cPanel fájlkezelőben közvetlenül felül lehet írni az egyes fájlokat
- Vagy: ZIP feltöltés → kicsomagolás (felülírja a régit)
- Plugin **deaktiválása NEM szükséges** egyszerű fájlcseréhez
- **Verziószám bumpolása kötelező** CSS/JS módosításkor – a `BSZA_VERSION` konstans cache-törésre szolgál. A `* Version:` fejlécet és a `define('BSZA_VERSION', ...)` értéket mindig szinkronban kell tartani

### Fejlesztői munkafolyamat (jelenlegi)
1. Módosítás a `E:\aktivbalaton.hu\Saját pluginok\balaton-szallasok\` mappában
2. Claude közvetlenül írja a fájlokat a Filesystem tool segítségével
3. cPanel-ben felülírás
4. Böngészőben tesztelés – Claude in Chrome eszközzel élőben ellenőrizhető a computed style

### Fejlesztői munkafolyamat – GitHub + cPanel Git ✅ KÉSZ

**GitHub repó:** https://github.com/aktivbalaton/balaton-szallasok-plugin (privát)  
**Lokális mappa:** `E:\aktivbalaton.hu\Saját pluginok\balaton-szallasok`  
**Szerver mappa:** `/home/aktivbal/public_html/wp-content/plugins/balaton-szallasok`

**Beállítás összefoglalója:**
1. GitHub privát repó létrehozva (`aktivbalaton` fiók)
2. Lokális Git inicializálva, GitHub-hoz kapcsolva
3. cPanel → Git™ Version Control → repó összekapcsolva a szerver plugin mappájával
4. GitHub webhook beállítva → minden `git push` után a cPanel automatikusan frissíti a szervert
5. `.cpanel.yml` deploy konfig hozzáadva
6. `deploy.bat` script hozzáadva az egyszerűsített deployhoz

**Munkafolyamat:**
1. Claude módosítja a fájlokat a Filesystem tool-lal
2. Dupla kattintás a `deploy.bat`-ra → commit üzenet megadása → automatikus push → szerver frissül
3. Böngészőben tesztelés

**Fontos technikai adatok:**
- cPanel API token neve: `github-webhook` (Manage API Tokens-ban)
- GitHub Personal Access Token neve: `cpanel-deploy` (no expiration, repo scope)
- Jailed SSH: bekapcsolva
- Git repó helye a szerveren: `/home/aktivbal/balaton-szallasok-git/` (KÍVÜL a public_html-en!)
- Plugin mappa: `/home/aktivbal/public_html/wp-content/plugins/balaton-szallasok/`
- `.cpanel.yml` a fájlokat a git repóból a plugin mappába másolja + chmod 755/644 jogosultságot állít be
- **Fontos:** A git repót NEM szabad a `public_html`-en belülre rakni – az Imunify360 biztonsági szoftver blokkolja a `.git` mappát tartalmazó könyvtárakat web-elérhető helyen!

**Előnyök:**
- Nincs kézi cPanel feltöltés
- Teljes verziókövetés – bármikor visszaállítható egy korábbi állapot
- Gyors deploy: `deploy.bat` dupla kattintás → kész

---

## 19. Függőségek és kompatibilitás

| Elem | Verzió / Megjegyzés |
|------|-------------------|
| WordPress | 5.0+ |
| PHP | 7.4+ |
| jQuery | WordPress beépített |
| Font Awesome | 6.5.0 (CDN) |
| Google Maps JS API | Maps JavaScript API szükséges |
| Yoast SEO | Kompatibilis – NE legyen WPSEO_* callback a plugin kódjában |
| EventON | Szintén betölti a Google Maps API-t – dupla betöltés védelem aktív |
| Elementor | Az Elementor kit globális button stílusai felülírják a gombokat – minden gombon `!important` szükséges |
| ACF | Nem szükséges, a plugin teljesen önálló |

---

## 20. Hibakeresési tippek

- **Fehér oldal szerkesztőben:** Valószínűleg PHP fatális hiba. Ellenőrizd a `cpt.php`-t – ne legyen benne `WPSEO_*` hivatkozás.
- **Gutenberg jelenik meg:** A `use_block_editor_for_post_type` filter nem fut le. Ellenőrizd, hogy a `balaton-szallasok.php` legelején (require-ok ELŐTT) van-e a filter.
- **Egyedi mezők panel látszik:** `remove_meta_box('postcustom', 'szallasok', 'normal')` hiányzik vagy nem fut le. Priority 99-cel kell.
- **Térkép nem jelenik meg:** 1) Nincs API kulcs beállítva, 2) Nincs GPS koordináta a szállásoknál, 3) Dupla Maps API betöltés.
- **AJAX szűrés nem működik:** Ellenőrizd a `szallasok_nonce`-t és az `admin-ajax.php` elérhetőségét.
- **Gomb mérete nem változik CSS módosítás után:** Az Elementor kit felülírja. Minden gomb property-re `!important` kell. Ellenőrzés Claude in Chrome eszközzel: `getComputedStyle(document.querySelector('.bsza-view-btn')).padding`
- **CSS változtatás nem látszik:** Verziószám nem lett bumpolva, vagy a LiteSpeed cache nem lett törölve. Bumpolj verziót, töröld a cache-t.
- **Egyedi szállás oldal 404-et ad:** Permalinkek nem lettek frissítve. Beállítások → Permalinkek → Mentés.
- **`* Version:` és `define('BSZA_VERSION')` eltér:** A WP a fejlécet olvassa (Bővítmények oldal), a konstans a cache-törésre kell. Mindig tartsd szinkronban.
- **Deploy után a szerver nem frissül:** Ellenőrizd a GitHub webhook `repository_root` paraméterét – a helyes érték: `/home/aktivbal/balaton-szallasok-git/`. GitHub → Settings → Webhooks → Edit. A Recent Deliveries fülön a zöld pipa jelzi a sikeres kézbesítést.