/* global szallasokAjax, bszaTerképAdatok, bszaSingleImages, google */

// ── Google Maps callback (globális) ───────────────────────────────────────────
var bszaMap        = null;
var bszaMarkers    = [];
var bszaInfoWindow = null;
var bszaMapReady   = false;

function bszaInitMap() {
    // Ha már fut, ne fusson újra
    if ( bszaMapReady ) return;
    bszaMapReady = true;

    if ( typeof bszaTerképAdatok === 'undefined' || ! bszaTerképAdatok.length ) return;
    if ( ! document.getElementById('bsza-google-map') ) return;

    bszaMap = new google.maps.Map( document.getElementById('bsza-google-map'), {
        center: { lat: szallasokAjax.mapCenter.lat, lng: szallasokAjax.mapCenter.lng },
        zoom:   parseInt( szallasokAjax.mapZoom ),
        mapTypeControl: false,
        fullscreenControl: true,
        streetViewControl: false,
        styles: [
            { featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] }
        ]
    });

    bszaInfoWindow = new google.maps.InfoWindow();

    bszaTerképAdatok.forEach( function( sz ) {
        var marker = new google.maps.Marker({
            position: { lat: sz.lat, lng: sz.lng },
            map:      bszaMap,
            title:    sz.cim,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 10,
                fillColor:    '#4299e1',
                fillOpacity:  1,
                strokeColor:  '#fff',
                strokeWeight: 2,
            }
        });

        marker.szallasData = sz;
        marker.addListener('click', function() { bszaOpenInfoWindow( marker ); });
        bszaMarkers.push( marker );
    });

    // Térkép igazítása markerekhez
    if ( bszaMarkers.length > 1 ) {
        var bounds = new google.maps.LatLngBounds();
        bszaMarkers.forEach( function(m) { bounds.extend( m.getPosition() ); });
        bszaMap.fitBounds( bounds );
    } else if ( bszaMarkers.length === 1 ) {
        bszaMap.setCenter( bszaMarkers[0].getPosition() );
        bszaMap.setZoom(13);
    }
}

function bszaOpenInfoWindow( marker ) {
    var sz = marker.szallasData;
    var kepHtml = sz.kep
        ? '<img src="' + sz.kep + '" style="width:100%;height:100px;object-fit:cover;border-radius:6px;margin-bottom:8px;" />'
        : '';

    var content =
        '<div class="bsza-infowindow">' +
        kepHtml +
        '<strong>' + sz.cim + '</strong>' +
        ( sz.telepules ? '<div><i class="fas fa-map-marker-alt"></i> ' + sz.telepules + '</div>' : '' ) +
        ( sz.tipus     ? '<div><i class="fas fa-home"></i> ' + sz.tipus + '</div>' : '' ) +
        ( sz.ferohely  ? '<div><i class="fas fa-users"></i> Max. ' + sz.ferohely + ' fő</div>' : '' ) +
        '<div class="bsza-iw-actions">' +
        ( sz.foglalas  ? '<a href="' + sz.foglalas + '" target="_blank" class="bsza-iw-btn bsza-iw-foglalas">Foglalás</a>' : '' ) +
        '<button type="button" class="bsza-iw-btn bsza-iw-reszletek" data-id="' + sz.id + '">Részletek</button>' +
        '</div></div>';

    bszaInfoWindow.setContent( content );
    bszaInfoWindow.open( bszaMap, marker );
}

// ── Ha a Maps API már be volt töltve másik plugin által,
//    akkor a window.google objektum már létezik – inicializálunk manuálisan ───
function bszaMaybeInitMap() {
    if ( typeof google !== 'undefined' && typeof google.maps !== 'undefined' && ! bszaMapReady ) {
        bszaInitMap();
    }
}

jQuery(document).ready(function ($) {

    var activeFilters = {
        telepules: '', tipus: '', min_ferohely: '', felszereltseg: [], sort: 'date_desc'
    };
    var currentView = 'lista';

    // ── Lista / Térkép váltó ──────────────────────────────────────────────────
    $(document).on('click', '#bsza-view-lista', function () {
        if ( currentView === 'lista' ) return;
        currentView = 'lista';
        $(this).addClass('active');
        $('#bsza-view-terkep').removeClass('active');
        $('#bsza-terkep-nezet').hide();
        $('#bsza-lista-nezet').show();
    });

    $(document).on('click', '#bsza-view-terkep', function () {
        if ( currentView === 'terkep' ) return;
        currentView = 'terkep';
        $(this).addClass('active');
        $('#bsza-view-lista').removeClass('active');
        $('#bsza-lista-nezet').hide();
        $('#bsza-terkep-nezet').show();

        // Térkép inicializálás – akár saját, akár más plugin töltötte be a Maps API-t
        if ( ! bszaMapReady ) {
            if ( typeof google !== 'undefined' && typeof google.maps !== 'undefined' ) {
                bszaInitMap();
            }
        }

        // Resize – néha rossz méretet vesz fel
        if ( bszaMap ) {
            setTimeout(function() {
                google.maps.event.trigger( bszaMap, 'resize' );
                if ( bszaMarkers.length > 1 ) {
                    var bounds = new google.maps.LatLngBounds();
                    bszaMarkers.forEach(function(m) { bounds.extend(m.getPosition()); });
                    bszaMap.fitBounds(bounds);
                }
            }, 250);
        }
    });

    // ── InfoWindow Részletek gomb ─────────────────────────────────────────────
    $(document).on('click', '.bsza-iw-reszletek', function () {
        var postId = parseInt( $(this).data('id') );
        var $card = $('.szallas-card').filter(function() {
            var d = $(this).data('modal');
            return d && d.id == postId;
        });
        if ( $card.length ) {
            bszaInfoWindow.close();
            $card.find('.szallas-reszletek-btn').trigger('click');
        } else {
            bszaOpenModalById( postId );
        }
    });

    function bszaOpenModalById( postId ) {
        $.post( szallasokAjax.ajaxurl, {
            action: 'bsza_get_modal_data',
            nonce:  szallasokAjax.nonce,
            id:     postId
        }, function( resp ) {
            if ( resp.success ) openModal( resp.data );
        });
    }

    // ── Szűrés ────────────────────────────────────────────────────────────────
    $('#szallas-filter').on('submit', function (e) {
        e.preventDefault();
        activeFilters = collectFilters();
        doFilter(0, true);
    });
    $('#sort').on('change', function () {
        activeFilters = collectFilters();
        doFilter(0, true);
    });
    $(document).on('click', '.reset-filters', function () {
        $('#szallas-filter')[0].reset();
        activeFilters = collectFilters();
        doFilter(0, true);
    });
    $(document).on('click', '#load-more-btn', function () {
        doFilter( parseInt($(this).data('offset')), false );
    });

    function collectFilters() {
        var felszereltseg = [];
        $('input[name="felszereltseg[]"]:checked').each(function () {
            felszereltseg.push($(this).val());
        });
        return {
            kereses:       $('#bsza-kereses').val() || '',
            telepules:     $('#telepules').val()    || '',
            tipus:         $('#tipus').val()        || '',
            min_ferohely:  $('#min_ferohely').val() || '',
            felszereltseg: felszereltseg,
            sort:          $('#sort').val()         || 'date_desc'
        };
    }

    function doFilter(offset, replace) {
        var data = $.extend({}, activeFilters, {
            action: 'filter_szallasok', nonce: szallasokAjax.nonce, offset: offset
        });
        if (replace) {
            $('.szallasok-grid').css('opacity', '0.4');
            $('.loading-overlay').fadeIn(200);
        } else {
            $('#load-more-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Betöltés...');
        }
        $.ajax({
            url: szallasokAjax.ajaxurl, type: 'POST', data: data,
            success: function (response) {
                if (!response.success) return;
                var res = response.data;
                if (replace) {
                    $('.szallasok-grid').html(res.html || '<div class="no-results"><i class="fas fa-search"></i><h3>Nincs találat</h3><p>Próbáld meg módosítani a szűrési feltételeket</p><button type="button" class="reset-filters">Szűrők törlése</button></div>');
                    updateLoadMoreBtn(res);
                } else {
                    $('.szallasok-grid').append(res.html);
                    updateLoadMoreBtn(res);
                }
            },
            error: function (xhr, status, error) { console.error('Szállások szűrés hiba:', error); },
            complete: function () {
                if (replace) {
                    $('.szallasok-grid').css('opacity', '1');
                    $('.loading-overlay').fadeOut(200);
                }
            }
        });
    }

    function updateLoadMoreBtn(res) {
        var $container = $('#load-more-container');
        if (res.has_more) {
            if (!$container.length) {
                $('.szallasok-container').append('<div class="load-more-container" id="load-more-container"></div>');
                $container = $('#load-more-container');
            }
            $container.html('<button type="button" id="load-more-btn" class="load-more-btn" data-offset="' + res.loaded + '" data-per-page="9" data-total="' + res.total + '"><i class="fas fa-chevron-down"></i> További szállások betöltése <span class="load-more-info">(' + res.loaded + ' / ' + res.total + ')</span></button>');
        } else if ($container.length) {
            $container.html('<div class="all-loaded"><i class="fas fa-check-circle"></i> Minden szállás betöltve (' + res.total + ' db)</div>');
        }
    }

    // ── Thumbnail váltás ──────────────────────────────────────────────────────
    $(document).on('click', '.thumbnail', function () {
        var $container = $(this).closest('.szallas-images-container');
        var $mainImage = $container.find('.szallas-image img');
        var newImageUrl = $(this).data('image');
        $mainImage.fadeOut(200, function () {
            $(this).attr('src', newImageUrl).on('load', function () { $(this).fadeIn(200); });
        });
        $container.find('.thumbnail').removeClass('active');
        $(this).addClass('active');
    });

    // ══════════════════════════════════════════════════════════════
    // ── MODAL
    // ══════════════════════════════════════════════════════════════
    var currentLightboxIndex = 0;
    var currentImages = [];

    $(document).on('click', '.szallas-reszletek-btn', function () {
        var data = $(this).closest('.szallas-card').data('modal');
        if (data) openModal(data);
    });
    $(document).on('click', '#bsza-modal-close, #bsza-modal-overlay', function (e) {
        if ($(e.target).is('#bsza-modal-overlay') || $(e.target).is('#bsza-modal-close')) closeModal();
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            if ($('#bsza-lightbox-fs').length) {
                $('#bsza-fs-close').trigger('click');
            } else {
                closeModal();
            }
        }
        if (e.key === 'ArrowRight') lightboxNext();
        if (e.key === 'ArrowLeft')  lightboxPrev();
    });

    function openModal(d) {
        currentImages = d.kepek || [];
        currentLightboxIndex = 0;
        $('body').append( buildModalHTML(d) );
        $('body').addClass('bsza-modal-open');
        if (d.lat && d.lng) {
            $('#bsza-modal-map iframe').attr('src',
                'https://maps.google.com/maps?q=' + d.lat + ',' + d.lng + '&z=15&output=embed'
            );
        }
        setTimeout(function () { $('#bsza-modal-overlay').addClass('active'); }, 10);
    }

    function closeModal() {
        $('#bsza-modal-overlay').removeClass('active');
        setTimeout(function () {
            $('#bsza-modal-overlay').remove();
            $('body').removeClass('bsza-modal-open');
        }, 300);
    }

    function buildModalHTML(d) {
        var kepekHTML = '';
        if (d.kepek && d.kepek.length > 0) {
            kepekHTML += '<div class="bsza-modal-gallery"><div class="bsza-modal-main-img-wrap">';
            kepekHTML += '<img src="' + escHtml(d.kepek[0].url) + '" class="bsza-modal-main-img" id="bsza-modal-main-img" alt="' + escHtml(d.cim) + '" />';
            if (d.kepek.length > 1) {
                kepekHTML += '<button class="bsza-lb-arrow bsza-lb-prev" id="bsza-lb-prev"><i class="fas fa-chevron-left"></i></button>';
                kepekHTML += '<button class="bsza-lb-arrow bsza-lb-next" id="bsza-lb-next"><i class="fas fa-chevron-right"></i></button>';
            }
            kepekHTML += '<button class="bsza-lb-fullscreen" id="bsza-lb-fullscreen"><i class="fas fa-expand"></i></button>';
            kepekHTML += '</div>';
            if (d.kepek.length > 1) {
                kepekHTML += '<div class="bsza-modal-thumbs">';
                for (var i = 0; i < d.kepek.length; i++) {
                    kepekHTML += '<img src="' + escHtml(d.kepek[i].thumb) + '" class="bsza-modal-thumb' + (i === 0 ? ' active' : '') + '" data-index="' + i + '" alt="" />';
                }
                kepekHTML += '</div>';
            }
            kepekHTML += '</div>';
        }

        var adatokHTML = '<div class="bsza-modal-info"><div class="bsza-modal-meta">';
        if (d.telepules) adatokHTML += '<div class="bsza-modal-meta-item"><i class="fas fa-map-marker-alt"></i> ' + escHtml(d.telepules) + (d.utca ? ', ' + escHtml(d.utca) : '') + '</div>';
        if (d.tipus)     adatokHTML += '<div class="bsza-modal-meta-item"><i class="fas fa-home"></i> ' + escHtml(d.tipus) + '</div>';
        if (d.ferohely)  adatokHTML += '<div class="bsza-modal-meta-item"><i class="fas fa-users"></i> Max. ' + escHtml(String(d.ferohely)) + ' fő</div>';
        adatokHTML += '</div>';
        if (d.leiras) adatokHTML += '<div class="bsza-modal-leiras"><p>' + escHtml(d.leiras).replace(/\n/g, '<br>') + '</p></div>';
        adatokHTML += '<div class="bsza-modal-kontakt">';
        if (d.telefon)  adatokHTML += '<a href="tel:' + escHtml(d.telefon.replace(/\s/g,'')) + '" class="bsza-modal-link"><i class="fas fa-phone"></i> ' + escHtml(d.telefon) + '</a>';
        if (d.email)    adatokHTML += '<a href="mailto:' + escHtml(d.email) + '" class="bsza-modal-link"><i class="fas fa-envelope"></i> ' + escHtml(d.email) + '</a>';
        if (d.foglalas) adatokHTML += '<a href="' + escHtml(d.foglalas) + '" class="bsza-modal-link bsza-modal-foglalas" target="_blank" rel="noopener"><i class="fas fa-calendar-check"></i> Foglalás</a>';
        if (d.lat && d.lng) adatokHTML += '<a href="https://www.google.com/maps?q=' + d.lat + ',' + d.lng + '" class="bsza-modal-link" target="_blank" rel="noopener"><i class="fas fa-map"></i> Útvonal tervezés</a>';
        adatokHTML += '</div>';
        if (d.felszereltseg && d.felszereltseg.length > 0) {
            adatokHTML += '<div class="bsza-modal-felszereltseg"><h4>Felszereltség</h4><div class="bsza-modal-felszereltseg-lista">';
            for (var j = 0; j < d.felszereltseg.length; j++) {
                adatokHTML += '<span class="felszereltseg-item"><i class="fas fa-check"></i> ' + escHtml(d.felszereltseg[j]) + '</span>';
            }
            adatokHTML += '</div></div>';
        }
        if (d.lat && d.lng) adatokHTML += '<div class="bsza-modal-map" id="bsza-modal-map"><iframe src="" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe></div>';
        adatokHTML += '</div>';

        return '<div id="bsza-modal-overlay"><div id="bsza-modal" role="dialog">' +
            '<div class="bsza-modal-header"><h2 class="bsza-modal-title">' + escHtml(d.cim) + '</h2>' +
            '<button id="bsza-modal-close" aria-label="Bezárás">✕</button></div>' +
            '<div class="bsza-modal-body">' + kepekHTML + adatokHTML + '</div></div></div>';
    }

    $(document).on('click', '.bsza-modal-thumb', function () {
        currentLightboxIndex = parseInt($(this).data('index'));
        updateLightbox();
    });
    $(document).on('click', '#bsza-lb-next', function (e) { e.stopPropagation(); lightboxNext(); });
    $(document).on('click', '#bsza-lb-prev', function (e) { e.stopPropagation(); lightboxPrev(); });
    $(document).on('click', '#bsza-lb-fullscreen', function (e) {
        e.stopPropagation();
        if (currentImages.length) openLightboxFullscreen(currentLightboxIndex);
    });
    $(document).on('click', '#bsza-modal-main-img', function () { openLightboxFullscreen(currentLightboxIndex); });

    function lightboxNext() {
        if (!currentImages.length) return;
        currentLightboxIndex = (currentLightboxIndex + 1) % currentImages.length;
        // Modal vagy single oldal kontextus
        if ($('#bsza-modal-overlay').length) {
            updateLightbox();
        } else if ($('#bsza-single-main-img').length) {
            updateSingleGallery();
        }
    }
    function lightboxPrev() {
        if (!currentImages.length) return;
        currentLightboxIndex = (currentLightboxIndex - 1 + currentImages.length) % currentImages.length;
        if ($('#bsza-modal-overlay').length) {
            updateLightbox();
        } else if ($('#bsza-single-main-img').length) {
            updateSingleGallery();
        }
    }
    function updateLightbox() {
        var img = currentImages[currentLightboxIndex];
        if (!img) return;
        var $main = $('#bsza-modal-main-img');
        $main.css('opacity', 0);
        setTimeout(function () { $main.attr('src', img.url).css('opacity', 1); }, 150);
        $('.bsza-modal-thumb').removeClass('active');
        $('.bsza-modal-thumb[data-index="' + currentLightboxIndex + '"]').addClass('active');
    }

    function openLightboxFullscreen(index) {
        if (!currentImages.length) return;
        var $fs = $('<div id="bsza-lightbox-fs">' +
            '<button id="bsza-fs-close">✕</button>' +
            '<button class="bsza-fs-arrow" id="bsza-fs-prev"><i class="fas fa-chevron-left"></i></button>' +
            '<img id="bsza-fs-img" src="' + currentImages[index].url + '" alt="" />' +
            '<button class="bsza-fs-arrow" id="bsza-fs-next"><i class="fas fa-chevron-right"></i></button>' +
            '<div id="bsza-fs-counter">' + (index + 1) + ' / ' + currentImages.length + '</div>' +
            '</div>');
        $('body').append($fs);
        $fs.data('index', index);
        setTimeout(function () { $fs.addClass('active'); }, 10);
    }

    $(document).on('click', '#bsza-fs-close', function () {
        $('#bsza-lightbox-fs').removeClass('active');
        setTimeout(function () { $('#bsza-lightbox-fs').remove(); }, 250);
    });
    $(document).on('click', '#bsza-fs-next', function (e) {
        e.stopPropagation();
        var idx = ($('#bsza-lightbox-fs').data('index') + 1) % currentImages.length;
        $('#bsza-lightbox-fs').data('index', idx);
        $('#bsza-fs-img').attr('src', currentImages[idx].url);
        $('#bsza-fs-counter').text((idx + 1) + ' / ' + currentImages.length);
    });
    $(document).on('click', '#bsza-fs-prev', function (e) {
        e.stopPropagation();
        var idx = ($('#bsza-lightbox-fs').data('index') - 1 + currentImages.length) % currentImages.length;
        $('#bsza-lightbox-fs').data('index', idx);
        $('#bsza-fs-img').attr('src', currentImages[idx].url);
        $('#bsza-fs-counter').text((idx + 1) + ' / ' + currentImages.length);
    });
    $(document).on('click', '#bsza-lightbox-fs', function (e) {
        if ($(e.target).is('#bsza-lightbox-fs') || $(e.target).is('#bsza-fs-img')) {
            $('#bsza-fs-close').trigger('click');
        }
    });

    // ══════════════════════════════════════════════════════════════
    // ── EGYEDI SZÁLLÁS OLDAL GALÉRIA
    // ══════════════════════════════════════════════════════════════
    if ( typeof bszaSingleImages !== 'undefined' && bszaSingleImages.length && $('#bsza-single-main-img').length ) {

        // Feltöltjük a megosztott currentImages tömböt
        currentImages         = bszaSingleImages;
        currentLightboxIndex  = 0;

        // Thumbnail kattintás
        $(document).on('click', '.bsza-single-thumbs .bsza-modal-thumb', function () {
            currentLightboxIndex = parseInt( $(this).data('index') );
            updateSingleGallery();
        });

        // Nyíl gombok
        $(document).on('click', '#bsza-single-prev', function (e) {
            e.stopPropagation();
            currentLightboxIndex = ( currentLightboxIndex - 1 + currentImages.length ) % currentImages.length;
            updateSingleGallery();
        });
        $(document).on('click', '#bsza-single-next', function (e) {
            e.stopPropagation();
            currentLightboxIndex = ( currentLightboxIndex + 1 ) % currentImages.length;
            updateSingleGallery();
        });

        // Fullscreen gomb + főképre kattintás
        $(document).on('click', '#bsza-single-fullscreen, #bsza-single-main-img', function (e) {
            e.stopPropagation();
            openLightboxFullscreen( currentLightboxIndex );
        });
    }

    function updateSingleGallery() {
        var img = currentImages[ currentLightboxIndex ];
        if ( ! img ) return;
        var $main = $( '#bsza-single-main-img' );
        $main.css( 'opacity', 0 );
        setTimeout( function () { $main.attr( 'src', img.url ).css( 'opacity', 1 ); }, 150 );
        $( '.bsza-single-thumbs .bsza-modal-thumb' ).removeClass( 'active' );
        $( '.bsza-single-thumbs .bsza-modal-thumb[data-index="' + currentLightboxIndex + '"]' ).addClass( 'active' );
        $( '#bsza-single-counter-cur' ).text( currentLightboxIndex + 1 );
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
});
