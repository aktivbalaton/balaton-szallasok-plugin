/* globals jQuery, wp, bszaAdmin */
jQuery(document).ready(function ($) {

    var mediaFrame;

    // ── Képek hozzáadása gomb ──────────────────────────────────────────────────
    $('#bsza-add-images').on('click', function (e) {
        e.preventDefault();

        if (mediaFrame) {
            mediaFrame.open();
            return;
        }

        mediaFrame = wp.media({
            title:    bszaAdmin.mediaTitle,
            button:   { text: bszaAdmin.mediaButton },
            multiple: true,
            library:  { type: 'image' }
        });

        mediaFrame.on('select', function () {
            var selection = mediaFrame.state().get('selection');
            selection.each(function (attachment) {
                var data = attachment.toJSON();
                // Duplikáció elkerülése
                if ($('#bsza-gallery-preview [data-id="' + data.id + '"]').length > 0) return;

                var thumbUrl = (data.sizes && data.sizes.thumbnail)
                    ? data.sizes.thumbnail.url
                    : data.url;

                var $item = $('<div class="bsza-gallery-item" data-id="' + data.id + '">' +
                    '<img src="' + thumbUrl + '" alt="" />' +
                    '<span class="bsza-remove-image" title="Eltávolítás">✕</span>' +
                    '</div>');
                $('#bsza-gallery-preview').append($item);
            });
            bszaUpdateIds();
        });

        mediaFrame.open();
    });

    // ── Kép eltávolítása ───────────────────────────────────────────────────────
    $(document).on('click', '.bsza-remove-image', function () {
        $(this).closest('.bsza-gallery-item').remove();
        bszaUpdateIds();
    });

    // ── Drag & Drop átrendezés ─────────────────────────────────────────────────
    $('#bsza-gallery-preview').sortable({
        items:  '.bsza-gallery-item',
        cursor: 'grab',
        opacity: 0.8,
        placeholder: 'bsza-gallery-placeholder',
        update: function () {
            bszaUpdateIds();
        }
    });

    // ── Rejtett mező frissítése ────────────────────────────────────────────────
    function bszaUpdateIds() {
        var ids = [];
        $('#bsza-gallery-preview .bsza-gallery-item').each(function () {
            ids.push($(this).data('id'));
        });
        $('#bsza_galeria_ids').val(ids.join(','));
    }
});
