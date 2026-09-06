(function (wp) {
    'use strict';
    var el = wp.element.createElement, __ = wp.i18n.__;
    var C = wp.components, BE = wp.blockEditor;
    var SSR = wp.serverSideRender.default || wp.serverSideRender;
    var supports = { html: false, align: ['wide', 'full'], color: { text: true, background: true }, spacing: { margin: true, padding: true }, typography: { fontSize: true } };
    ['google-reviews', 'manual-testimonials'].forEach(function (slug) {
        var google = slug === 'google-reviews', name = 'ajnanda/' + slug;
        wp.blocks.registerBlockType(name, {
            apiVersion: 3, title: google ? __('Google Reviews', 'ajnanda') : __('Manual Testimonials', 'ajnanda'),
            description: __('Displays published, featured content managed by AJ Core.', 'ajnanda'),
            icon: 'format-quote', category: 'ajnanda-blocks', attributes: AJNandaReviewsBlocks.attributes, supports: supports,
            edit: function (props) {
                var a = props.attributes, set = props.setAttributes;
                function change(key) { return function (value) { var patch = {}; patch[key] = value; set(patch); }; }
                function text(key, label) { return el(C.TextControl, { key: key, label: label, value: a[key], onChange: change(key) }); }
                function toggle(key, label) { return el(C.ToggleControl, { key: key, label: label, checked: a[key], onChange: change(key) }); }
                function range(key, label, min, max, step) { return el(C.RangeControl, { key: key, label: label, value: a[key], min: min, max: max, step: step || 1, onChange: change(key) }); }
                var controls = [
                    text('heading', __('Heading', 'ajnanda')), text('supportingText', __('Supporting text', 'ajnanda')),
                    el(C.SelectControl, { key: 'layout', label: __('Layout', 'ajnanda'), value: a.layout, onChange: change('layout'), options: [{ label: __('Grid', 'ajnanda'), value: 'grid' }, { label: __('List', 'ajnanda'), value: 'list' }, { label: __('Carousel', 'ajnanda'), value: 'carousel' }, { label: __('Featured single', 'ajnanda'), value: 'featured' }] }),
                    el(C.SelectControl, { key: 'order', label: __('Featured ordering', 'ajnanda'), value: a.order, onChange: change('order'), options: [{ label: __('AJ Core display setting', 'ajnanda'), value: 'configured' }, { label: __('Business display order', 'ajnanda'), value: 'manual' }, { label: __('Publication date, newest first', 'ajnanda'), value: 'date' }] }),
                    range('limit', __('Number of records', 'ajnanda'), 1, 50), range('columns', __('Grid columns', 'ajnanda'), 1, 4),
                    toggle('showAvatar', __('Show reviewer image', 'ajnanda')), toggle('showDate', __('Show date', 'ajnanda')), toggle('showText', __('Show text', 'ajnanda')),
                    range('textLines', __('Preview lines (0 shows full text)', 'ajnanda'), 0, 20)
                ];
                if (google) controls = controls.concat([
                    toggle('showOverallRating', __('Show overall Google rating', 'ajnanda')), toggle('showTotal', __('Show total Google review count', 'ajnanda')),
                    toggle('showViewButton', __('Show View on Google button', 'ajnanda')), text('viewLabel', __('Custom View on Google label', 'ajnanda')),
                    toggle('showWriteButton', __('Show Write a Review button', 'ajnanda')), text('writeLabel', __('Custom Write a Review label', 'ajnanda')),
                    el('p', { key: 'attribution' }, __('Google attribution, supplied source links, and selection disclosure remain visible. Individual review links and write links appear only when Google supplies them.', 'ajnanda'))
                ]);
                else controls.push(toggle('showRating', __('Show rating', 'ajnanda')), toggle('showSource', __('Show source', 'ajnanda')));
                if (a.layout === 'carousel') controls.push(toggle('showDots', __('Show pagination indicators', 'ajnanda')), toggle('autoplay', __('Automatic rotation (off by default)', 'ajnanda')), range('interval', __('Rotation interval (milliseconds)', 'ajnanda'), 3000, 30000, 1000));
                return el(wp.element.Fragment, null,
                    el(BE.InspectorControls, null, el(C.PanelBody, { title: __('Reviews & Testimonials', 'ajnanda') }, controls)),
                    el('div', BE.useBlockProps(), el(SSR, { block: name, attributes: a, httpMethod: 'POST' }))
                );
            },
            save: function () { return null; }
        });
    });
}(window.wp));
