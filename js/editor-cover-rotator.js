/**
 * AJNanda editor: "Background rotation" panel for core/cover.
 *
 * Adds comment-delimiter attributes only (no save() changes), so Covers that
 * never turn rotation on keep their exact markup. Rendering happens in
 * inc/cover-rotator.php; the rotation itself runs on the live site only.
 */
(function(wp) {
    if (!wp || !wp.hooks || !wp.compose || !wp.element || !wp.components || !wp.blockEditor) {
        return;
    }

    var addFilter = wp.hooks.addFilter;
    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var MediaUpload = wp.blockEditor.MediaUpload;
    var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
    var PanelBody = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var RangeControl = wp.components.RangeControl;
    var Button = wp.components.Button;
    var Notice = wp.components.Notice;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
    var useSelect = wp.data && wp.data.useSelect;

    var ROTATOR_ATTRS = {
        ajnRotateEnabled: { type: 'boolean', default: false },
        ajnRotateImageIds: { type: 'array', default: [], items: { type: 'number' } },
        ajnRotateInterval: { type: 'number', default: 5 },
        ajnRotateFade: { type: 'number', default: 0.8 }
    };

    addFilter('blocks.registerBlockType', 'ajn/cover-rotator-attributes', function(settings, name) {
        if ('core/cover' !== (name || settings.name)) {
            return settings;
        }
        settings.attributes = Object.assign({}, settings.attributes || {}, ROTATOR_ATTRS);
        return settings;
    });

    function RotatorThumbnails(props) {
        var ids = props.ids;
        var media = useSelect ? useSelect(function(select) {
            var core = select('core');
            if (!core || !ids.length) {
                return null;
            }
            return core.getEntityRecords('postType', 'attachment', { include: ids, per_page: ids.length, context: 'view' });
        }, [ids.join(',')]) : null;

        var byId = {};
        (media || []).forEach(function(item) {
            byId[item.id] = item;
        });

        return createElement('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '6px', margin: '0 0 12px' } },
            ids.map(function(id) {
                var item = byId[id];
                var sizes = item && item.media_details && item.media_details.sizes;
                var url = item ? ((sizes && sizes.thumbnail && sizes.thumbnail.source_url) || item.source_url) : '';
                return createElement('div', {
                    key: id,
                    style: { aspectRatio: '1', background: '#f0f0f0', borderRadius: '2px', overflow: 'hidden' }
                }, url ? createElement('img', { src: url, alt: '', style: { width: '100%', height: '100%', objectFit: 'cover', display: 'block' } }) : null);
            })
        );
    }

    addFilter(
        'editor.BlockEdit',
        'ajn/cover-rotator-controls',
        createHigherOrderComponent(function(BlockEdit) {
            return function(props) {
                if ('core/cover' !== props.name) {
                    return createElement(BlockEdit, props);
                }

                var attrs = props.attributes || {};
                var setAttributes = props.setAttributes;
                var ids = Array.isArray(attrs.ajnRotateImageIds) ? attrs.ajnRotateImageIds : [];
                var extraIds = ids.filter(function(id) { return id !== attrs.id; });
                var isVideo = attrs.backgroundType && 'image' !== attrs.backgroundType;

                var settings = attrs.ajnRotateEnabled ? createElement(Fragment, null,
                    isVideo ? createElement(Notice, { status: 'warning', isDismissible: false }, 'Rotation only works with an image background.') : null,
                    createElement('p', { style: { margin: '0 0 8px' } }, 'The Cover\'s own background shows first, then these images in order. Rotation runs on the live site only, and never for visitors who prefer reduced motion.'),
                    ids.length ? createElement(RotatorThumbnails, { ids: ids }) : null,
                    createElement(MediaUploadCheck, null,
                        createElement(MediaUpload, {
                            allowedTypes: ['image'],
                            multiple: true,
                            gallery: true,
                            value: ids,
                            onSelect: function(media) {
                                setAttributes({
                                    ajnRotateImageIds: (media || []).map(function(item) { return parseInt(item.id, 10); }).filter(Boolean)
                                });
                            },
                            render: function(obj) {
                                return createElement('div', { style: { display: 'flex', gap: '8px', marginBottom: '16px' } },
                                    createElement(Button, { variant: 'secondary', onClick: obj.open }, ids.length ? 'Edit / reorder images' : 'Choose images'),
                                    ids.length ? createElement(Button, { variant: 'tertiary', isDestructive: true, onClick: function() { setAttributes({ ajnRotateImageIds: [] }); } }, 'Clear') : null
                                );
                            }
                        })
                    ),
                    !extraIds.length ? createElement(Notice, { status: 'info', isDismissible: false }, 'Add at least one image other than the Cover background to start rotating.') : null,
                    createElement(RangeControl, {
                        label: 'Seconds per image',
                        min: 2,
                        max: 30,
                        step: 0.5,
                        value: typeof attrs.ajnRotateInterval === 'number' ? attrs.ajnRotateInterval : 5,
                        onChange: function(value) { setAttributes({ ajnRotateInterval: typeof value === 'number' ? value : 5 }); }
                    }),
                    createElement(RangeControl, {
                        label: 'Fade duration (seconds)',
                        min: 0,
                        max: 3,
                        step: 0.1,
                        value: typeof attrs.ajnRotateFade === 'number' ? attrs.ajnRotateFade : 0.8,
                        onChange: function(value) { setAttributes({ ajnRotateFade: typeof value === 'number' ? value : 0.8 }); }
                    })
                ) : null;

                return createElement(Fragment, null,
                    createElement(BlockEdit, props),
                    createElement(InspectorControls, null,
                        createElement(PanelBody, { title: 'Background rotation', initialOpen: !!attrs.ajnRotateEnabled },
                            createElement(ToggleControl, {
                                label: 'Rotate background images',
                                checked: !!attrs.ajnRotateEnabled,
                                onChange: function(value) { setAttributes({ ajnRotateEnabled: !!value }); }
                            }),
                            settings
                        )
                    )
                );
            };
        }, 'withAjnCoverRotatorControls')
    );
})(window.wp);
