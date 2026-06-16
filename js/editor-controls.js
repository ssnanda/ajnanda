/**
 * AJNanda editor controls
 * Responsive layout controls with live editor preview.
 */
(function(wp) {
    if (!wp || !wp.hooks || !wp.compose || !wp.element || !wp.components || !wp.blockEditor) {
        return;
    }

    var addFilter = wp.hooks.addFilter;
    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var Notice = wp.components.Notice;
    var ToggleControl = wp.components.ToggleControl;
    var SelectControl = wp.components.SelectControl;
    var RangeControl = wp.components.RangeControl;
    var CheckboxControl = wp.components.CheckboxControl;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
    var useEffect = wp.element.useEffect;
    var useState = wp.element.useState;
    var registerBlockVariation = wp.blocks.registerBlockVariation;
    var useSelect = wp.data && wp.data.useSelect;

    var LAYOUT_ATTRS = {
        ajnMinHeightDesktop: { type: 'string', default: '' },
        ajnMinHeightTablet: { type: 'string', default: '' },
        ajnMinHeightMobile: { type: 'string', default: '' },

        ajnUseFixedHeight: { type: 'boolean', default: false },
        ajnHeightDesktop: { type: 'string', default: '' },
        ajnHeightTablet: { type: 'string', default: '' },
        ajnHeightMobile: { type: 'string', default: '' },
        ajnHeightOverflowHidden: { type: 'boolean', default: false },

        ajnPaddingTopDesktop: { type: 'string', default: '' },
        ajnPaddingBottomDesktop: { type: 'string', default: '' },
        ajnPaddingLeftDesktop: { type: 'string', default: '' },
        ajnPaddingRightDesktop: { type: 'string', default: '' },

        ajnPaddingTopTablet: { type: 'string', default: '' },
        ajnPaddingBottomTablet: { type: 'string', default: '' },
        ajnPaddingLeftTablet: { type: 'string', default: '' },
        ajnPaddingRightTablet: { type: 'string', default: '' },

        ajnPaddingTopMobile: { type: 'string', default: '' },
        ajnPaddingBottomMobile: { type: 'string', default: '' },
        ajnPaddingLeftMobile: { type: 'string', default: '' },
        ajnPaddingRightMobile: { type: 'string', default: '' }
    };

    var LAYOUT_BLOCKS = {
        'core/group': true,
        'core/columns': true,
        'core/column': true,
        'core/cover': true,
        'core/media-text': true,
        'core/spacer': true,
        'ajnanda/div-block': true,
        'ajnanda/flexbox': true,
        'ajnanda/container': true,
        'ajnanda/grid': true,
        'ajnanda/form': true,
        'ajnanda/tabs': true,
        'ajnanda/accordion': true,
        'ajnanda/image-box': true,
        'ajnanda/icon-box': true,
        'ajnanda/basic-gallery': true,
        'ajnanda/image-gallery': true,
        'ajnanda/info-box': true,
        'ajnanda/call-to-action': true,
        'ajnanda/buttons': true
    };

    var BUTTON_LAYOUT_ATTRS = {
        ajnButtonLayoutDesktop: { type: 'string', default: 'row' },
        ajnButtonLayoutTablet: { type: 'string', default: 'row' },
        ajnButtonLayoutMobile: { type: 'string', default: 'stack' },
        ajnButtonGapDesktop: { type: 'number', default: 12 },
        ajnButtonGapTablet: { type: 'number', default: 12 },
        ajnButtonGapMobile: { type: 'number', default: 12 },
        ajnButtonsWidthDesktop: { type: 'string', default: 'auto' },
        ajnButtonsWidthTablet: { type: 'string', default: 'auto' },
        ajnButtonsWidthMobile: { type: 'string', default: 'auto' },
        ajnButtonsCustomWidthDesktop: { type: 'string', default: '' },
        ajnButtonsCustomWidthTablet: { type: 'string', default: '' },
        ajnButtonsCustomWidthMobile: { type: 'string', default: '' },
        ajnBtnJustify: { type: 'string', default: 'center' },
        ajnBtnSharedBg: { type: 'string', default: '' },
        ajnBtnSharedColor: { type: 'string', default: '' },
        ajnBtnSharedBorderColor: { type: 'string', default: '' },
        ajnBtnSharedBorderWidth: { type: 'number', default: 0 },
        ajnBtnSharedBorderRadius: { type: 'number', default: 0 },
        ajnBtnSharedPaddingX: { type: 'number', default: 0 },
        ajnBtnSharedPaddingY: { type: 'number', default: 0 },
        ajnBtnColor1: { type: 'string', default: '' },
        ajnBtnColor2: { type: 'string', default: '' },
        ajnBtnColor3: { type: 'string', default: '' },
        ajnBtnColor4: { type: 'string', default: '' },
        ajnBtnColor5: { type: 'string', default: '' },
        ajnBtnColor6: { type: 'string', default: '' },
        ajnBtnColorSchema: { type: 'string', default: '' },
        ajnBtnStyle: { type: 'string', default: '' }
    };

    // Format: "Shape • Color style"  — each preset sets ALL of bg, color, border, radius, padding
    var AJN_BUTTON_STYLES = [
        // ── Rounded corners (6 px) ──────────────────────────────────────────
        { value: 'rounded-blue',     label: 'Rounded • Solid Blue',       bg: '#2563eb',    color: '#ffffff', borderColor: '',        borderWidth: 0, borderRadius: 6,   paddingX: 24, paddingY: 12 },
        { value: 'rounded-dark',     label: 'Rounded • Solid Dark',       bg: '#0f172a',    color: '#ffffff', borderColor: '',        borderWidth: 0, borderRadius: 6,   paddingX: 24, paddingY: 12 },
        { value: 'rounded-green',    label: 'Rounded • Solid Green',      bg: '#16a34a',    color: '#ffffff', borderColor: '',        borderWidth: 0, borderRadius: 6,   paddingX: 24, paddingY: 12 },
        { value: 'rounded-red',      label: 'Rounded • Solid Red',        bg: '#dc2626',    color: '#ffffff', borderColor: '',        borderWidth: 0, borderRadius: 6,   paddingX: 24, paddingY: 12 },
        { value: 'rounded-orange',   label: 'Rounded • Solid Orange',     bg: '#f97316',    color: '#ffffff', borderColor: '',        borderWidth: 0, borderRadius: 6,   paddingX: 24, paddingY: 12 },
        { value: 'rounded-gray',     label: 'Rounded • Solid Gray',       bg: '#6b7280',    color: '#ffffff', borderColor: '',        borderWidth: 0, borderRadius: 6,   paddingX: 24, paddingY: 12 },
        // ── Pill / fully rounded ────────────────────────────────────────────
        { value: 'pill-blue',        label: 'Pill • Solid Blue',          bg: '#2563eb',    color: '#ffffff', borderColor: '',        borderWidth: 0, borderRadius: 999, paddingX: 28, paddingY: 12 },
        { value: 'pill-dark',        label: 'Pill • Solid Dark',          bg: '#0f172a',    color: '#ffffff', borderColor: '',        borderWidth: 0, borderRadius: 999, paddingX: 28, paddingY: 12 },
        { value: 'pill-green',       label: 'Pill • Solid Green',         bg: '#16a34a',    color: '#ffffff', borderColor: '',        borderWidth: 0, borderRadius: 999, paddingX: 28, paddingY: 12 },
        { value: 'pill-red',         label: 'Pill • Solid Red',           bg: '#dc2626',    color: '#ffffff', borderColor: '',        borderWidth: 0, borderRadius: 999, paddingX: 28, paddingY: 12 },
        // ── Square corners (0 radius) ───────────────────────────────────────
        { value: 'square-blue',      label: 'Square • Solid Blue',        bg: '#2563eb',    color: '#ffffff', borderColor: '',        borderWidth: 0, borderRadius: 0,   paddingX: 24, paddingY: 12 },
        { value: 'square-dark',      label: 'Square • Solid Dark',        bg: '#0f172a',    color: '#ffffff', borderColor: '',        borderWidth: 0, borderRadius: 0,   paddingX: 24, paddingY: 12 },
        // ── Outline / bordered (rounded) ────────────────────────────────────
        { value: 'outline-blue',     label: 'Outline • Blue Border',      bg: 'transparent', color: '#2563eb', borderColor: '#2563eb', borderWidth: 2, borderRadius: 6,   paddingX: 22, paddingY: 10 },
        { value: 'outline-dark',     label: 'Outline • Dark Border',      bg: 'transparent', color: '#0f172a', borderColor: '#0f172a', borderWidth: 2, borderRadius: 6,   paddingX: 22, paddingY: 10 },
        { value: 'outline-white',    label: 'Outline • White Border',     bg: 'transparent', color: '#ffffff', borderColor: '#ffffff', borderWidth: 2, borderRadius: 6,   paddingX: 22, paddingY: 10 },
        // ── Outline pill ────────────────────────────────────────────────────
        { value: 'pill-outline-blue', label: 'Pill Outline • Blue',       bg: 'transparent', color: '#2563eb', borderColor: '#2563eb', borderWidth: 2, borderRadius: 999, paddingX: 26, paddingY: 10 },
        { value: 'pill-outline-dark', label: 'Pill Outline • Dark',       bg: 'transparent', color: '#0f172a', borderColor: '#0f172a', borderWidth: 2, borderRadius: 999, paddingX: 26, paddingY: 10 },
        { value: 'pill-outline-white', label: 'Pill Outline • White',     bg: 'transparent', color: '#ffffff', borderColor: '#ffffff', borderWidth: 2, borderRadius: 999, paddingX: 26, paddingY: 10 }
    ];

    var AJN_COLOR_SCHEMES = [
        { value: 'brand',   label: 'Brand Blues',   colors: ['#2563eb', '#3b82f6', '#60a5fa', '#1d4ed8', '#1e40af', '#93c5fd'] },
        { value: 'sunset',  label: 'Sunset Warm',   colors: ['#f97316', '#ef4444', '#ec4899', '#f59e0b', '#eab308', '#dc2626'] },
        { value: 'forest',  label: 'Nature Green',  colors: ['#16a34a', '#059669', '#84cc16', '#15803d', '#65a30d', '#4ade80'] },
        { value: 'ocean',   label: 'Ocean Blue',    colors: ['#0ea5e9', '#06b6d4', '#0284c7', '#0891b2', '#0369a1', '#38bdf8'] },
        { value: 'royal',   label: 'Royal Purple',  colors: ['#7c3aed', '#8b5cf6', '#a855f7', '#6d28d9', '#c026d3', '#9333ea'] },
        { value: 'random',  label: 'Random Colors', colors: ['#f43f5e', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#3b82f6'] }
    ];

    var SINGLE_BUTTON_ATTRS = {
        ajnSingleButtonWidthDesktop: { type: 'string', default: 'auto' },
        ajnSingleButtonWidthTablet: { type: 'string', default: 'auto' },
        ajnSingleButtonWidthMobile: { type: 'string', default: 'auto' },
        ajnSingleButtonCustomWidthDesktop: { type: 'string', default: '' },
        ajnSingleButtonCustomWidthTablet: { type: 'string', default: '' },
        ajnSingleButtonCustomWidthMobile: { type: 'string', default: '' },
        ajnSingleBtnBg: { type: 'string', default: '' },
        ajnSingleBtnColor: { type: 'string', default: '' },
        ajnSingleBtnBorderColor: { type: 'string', default: '' }
    };

    function hasLayoutControls(blockName) {
        return !!LAYOUT_BLOCKS[blockName];
    }

    function normalizeSize(value) {
        value = (value || '').trim();

        if (!value) {
            return '';
        }

        if (/^\d+$/.test(value)) {
            return value + 'px';
        }

        return value;
    }

    function setVar(style, name, value) {
        value = normalizeSize(value);
        if (value) {
            style[name] = value;
        }
    }

    function getLayoutStyles(attrs) {
        var style = {};

        setVar(style, '--ajn-min-height-desktop', attrs.ajnMinHeightDesktop || attrs.ajnHeightDesktop);
        setVar(style, '--ajn-min-height-tablet', attrs.ajnMinHeightTablet || attrs.ajnHeightTablet);
        setVar(style, '--ajn-min-height-mobile', attrs.ajnMinHeightMobile || attrs.ajnHeightMobile);

        setVar(style, '--ajn-height-desktop', attrs.ajnHeightDesktop);
        setVar(style, '--ajn-height-tablet', attrs.ajnHeightTablet);
        setVar(style, '--ajn-height-mobile', attrs.ajnHeightMobile);

        setVar(style, '--ajn-padding-top-desktop', attrs.ajnPaddingTopDesktop);
        setVar(style, '--ajn-padding-right-desktop', attrs.ajnPaddingRightDesktop);
        setVar(style, '--ajn-padding-bottom-desktop', attrs.ajnPaddingBottomDesktop);
        setVar(style, '--ajn-padding-left-desktop', attrs.ajnPaddingLeftDesktop);

        setVar(style, '--ajn-padding-top-tablet', attrs.ajnPaddingTopTablet);
        setVar(style, '--ajn-padding-right-tablet', attrs.ajnPaddingRightTablet);
        setVar(style, '--ajn-padding-bottom-tablet', attrs.ajnPaddingBottomTablet);
        setVar(style, '--ajn-padding-left-tablet', attrs.ajnPaddingLeftTablet);

        setVar(style, '--ajn-padding-top-mobile', attrs.ajnPaddingTopMobile);
        setVar(style, '--ajn-padding-right-mobile', attrs.ajnPaddingRightMobile);
        setVar(style, '--ajn-padding-bottom-mobile', attrs.ajnPaddingBottomMobile);
        setVar(style, '--ajn-padding-left-mobile', attrs.ajnPaddingLeftMobile);

        return style;
    }

    function hasLayout(attrs) {
        return Object.keys(LAYOUT_ATTRS).some(function(key) {
            return attrs[key] !== undefined && attrs[key] !== '' && attrs[key] !== false;
        });
    }

    function mergeClassName(className, classToAdd) {
        className = className || '';

        if (className.split(/\s+/).indexOf(classToAdd) !== -1) {
            return className;
        }

        return (className + ' ' + classToAdd).trim();
    }

    var AJN_PRESET_CLASSES = [
        'builder-hero-section',
        'hero-height-compact',
        'hero-height-standard',
        'hero-height-tall',
        'hero-height-full',
        'hero-width-full',
        'hero-width-narrow',
        'hero-width-standard',
        'hero-width-wide',
        'hero-text-left',
        'builder-section',
        'builder-section-soft',
        'builder-card',
        'builder-card-grid'
    ];

    var HERO_HEIGHT_CLASSES = [
        'hero-height-compact',
        'hero-height-standard',
        'hero-height-tall',
        'hero-height-full'
    ];

    var HERO_WIDTH_CLASSES = [
        'hero-width-full',
        'hero-width-narrow',
        'hero-width-standard',
        'hero-width-wide'
    ];

    function removeClasses(className, classes) {
        var remove = {};

        classes.forEach(function(item) {
            remove[item] = true;
        });

        return (className || '')
            .split(/\s+/)
            .filter(function(item) {
                return item && !remove[item];
            })
            .join(' ');
    }

    function hasClass(className, classToFind) {
        return (className || '').split(/\s+/).indexOf(classToFind) !== -1;
    }

    function addClasses(className, classes) {
        classes.forEach(function(item) {
            if (item) {
                className = mergeClassName(className, item);
            }
        });

        return className;
    }

    function getHeroHeight(className) {
        if (hasClass(className, 'hero-height-compact')) {
            return 'compact';
        }
        if (hasClass(className, 'hero-height-tall')) {
            return 'tall';
        }
        if (hasClass(className, 'hero-height-full')) {
            return 'full';
        }
        if (hasClass(className, 'hero-height-standard')) {
            return 'standard';
        }

        return 'auto';
    }

    function getHeroWidth(className) {
        if (hasClass(className, 'hero-width-full')) {
            return 'full';
        }
        if (hasClass(className, 'hero-width-narrow')) {
            return 'narrow';
        }
        if (hasClass(className, 'hero-width-wide')) {
            return 'wide';
        }
        if (hasClass(className, 'hero-width-standard')) {
            return 'standard';
        }

        return 'full';
    }

    function getDesignPreset(className) {
        if (hasClass(className, 'builder-hero-section')) {
            return 'hero';
        }
        if (hasClass(className, 'builder-section-soft')) {
            return 'soft-section';
        }
        if (hasClass(className, 'builder-section')) {
            return 'section';
        }
        if (hasClass(className, 'builder-card')) {
            return 'card';
        }
        if (hasClass(className, 'builder-card-grid')) {
            return 'card-grid';
        }

        return '';
    }

    function setDesignPreset(className, preset) {
        className = removeClasses(className, AJN_PRESET_CLASSES);

        if (preset === 'hero') {
            return addClasses(className, ['builder-hero-section', 'hero-width-full']);
        }
        if (preset === 'section') {
            return addClasses(className, ['builder-section']);
        }
        if (preset === 'soft-section') {
            return addClasses(className, ['builder-section', 'builder-section-soft']);
        }
        if (preset === 'card') {
            return addClasses(className, ['builder-card']);
        }
        if (preset === 'card-grid') {
            return addClasses(className, ['builder-card-grid']);
        }

        return className;
    }

    function setHeroHeightClass(className, value) {
        className = removeClasses(className, HERO_HEIGHT_CLASSES);

        if (value && value !== 'auto') {
            className = mergeClassName(className, 'hero-height-' + value);
        }

        return className;
    }

    function setHeroWidthClass(className, value) {
        className = removeClasses(className, HERO_WIDTH_CLASSES);

        if (value) {
            className = mergeClassName(className, 'hero-width-' + value);
        }

        return className;
    }

    function setHeroTextClass(className, value) {
        className = removeClasses(className, ['hero-text-left']);

        if (value === 'left') {
            className = mergeClassName(className, 'hero-text-left');
        }

        return className;
    }

    var AJN_CLASS_OPTIONS = [
        {
            label: 'Hero',
            classes: [
                { label: 'Hero section', value: 'builder-hero-section' },
                { label: 'Height: compact', value: 'hero-height-compact' },
                { label: 'Height: standard', value: 'hero-height-standard' },
                { label: 'Height: tall', value: 'hero-height-tall' },
                { label: 'Height: full screen', value: 'hero-height-full' },
                { label: 'Width: full page', value: 'hero-width-full' },
                { label: 'Width: narrow content', value: 'hero-width-narrow' },
                { label: 'Width: standard content', value: 'hero-width-standard' },
                { label: 'Width: wide content', value: 'hero-width-wide' },
                { label: 'Text: left aligned', value: 'hero-text-left' }
            ]
        },
        {
            label: 'Sections and Cards',
            classes: [
                { label: 'Content section', value: 'builder-section' },
                { label: 'Soft content section', value: 'builder-section-soft' },
                { label: 'Card', value: 'builder-card' },
                { label: 'Card grid wrapper', value: 'builder-card-grid' },
                { label: 'Section intro text', value: 'builder-section-intro' },
                { label: 'Split content layout', value: 'builder-split' },
                { label: 'CTA panel', value: 'builder-cta-panel' }
            ]
        }
    ];

    function checkboxClassToggle(className, cssClass, checked) {
        var exclusiveClasses = [cssClass];

        if (HERO_HEIGHT_CLASSES.indexOf(cssClass) !== -1) {
            exclusiveClasses = HERO_HEIGHT_CLASSES;
        }

        if (HERO_WIDTH_CLASSES.indexOf(cssClass) !== -1) {
            exclusiveClasses = HERO_WIDTH_CLASSES;
        }

        className = removeClasses(className, exclusiveClasses);

        if (checked) {
            className = mergeClassName(className, cssClass);
        }

        return className;
    }

    function classCheckboxes(props) {
        var attrs = props.attributes || {};
        var className = attrs.className || '';

        return AJN_CLASS_OPTIONS.map(function(group) {
            return createElement(
                PanelBody,
                {
                    key: group.label,
                    title: group.label + ' CSS Classes',
                    initialOpen: false
                },
                group.classes.map(function(option) {
                    return createElement(CheckboxControl, {
                        key: option.value,
                        label: option.label,
                        checked: hasClass(className, option.value),
                        onChange: function(checked) {
                            props.setAttributes({
                                className: checkboxClassToggle((props.attributes && props.attributes.className) || '', option.value, checked)
                            });
                        }
                    });
                })
            );
        });
    }

    function designPresetControls(props) {
        var attrs = props.attributes || {};
        var className = attrs.className || '';
        var preset = getDesignPreset(className);

        function updateClassName(callback) {
            props.setAttributes({ className: callback((props.attributes && props.attributes.className) || '') });
        }

        function updateHeroWidth(value) {
            props.setAttributes({
                align: value === 'full' ? 'full' : undefined,
                className: setHeroWidthClass((props.attributes && props.attributes.className) || '', value)
            });
        }

        if (props.name !== 'core/group') {
            return null;
        }

        return createElement(
            PanelBody,
            {
                title: 'AJNanda CSS Preset',
                initialOpen: true
            },
            createElement(SelectControl, {
                label: 'Preset',
                value: preset,
                options: [
                    { label: 'None', value: '' },
                    { label: 'Hero section', value: 'hero' },
                    { label: 'Content section', value: 'section' },
                    { label: 'Soft content section', value: 'soft-section' },
                    { label: 'Card', value: 'card' },
                    { label: 'Card grid wrapper', value: 'card-grid' }
                ],
                onChange: function(value) {
                    props.setAttributes(value === 'hero' ? {
                        align: 'full',
                        className: setDesignPreset((props.attributes && props.attributes.className) || '', value)
                    } : {
                        className: setDesignPreset((props.attributes && props.attributes.className) || '', value)
                    });
                }
            }),
            preset === 'hero' ? createElement(SelectControl, {
                label: 'Hero height',
                help: 'Auto grows with the amount of text. Fixed presets are optional.',
                value: getHeroHeight(className),
                options: [
                    { label: 'Auto / content height', value: 'auto' },
                    { label: 'Compact', value: 'compact' },
                    { label: 'Standard', value: 'standard' },
                    { label: 'Tall', value: 'tall' },
                    { label: 'Full screen', value: 'full' }
                ],
                onChange: function(value) {
                    updateClassName(function(currentClassName) {
                        return setHeroHeightClass(currentClassName, value);
                    });
                }
            }) : null,
            preset === 'hero' ? createElement(SelectControl, {
                label: 'Hero width',
                value: getHeroWidth(className),
                options: [
                    { label: 'Full page width', value: 'full' },
                    { label: 'Narrow content', value: 'narrow' },
                    { label: 'Standard content', value: 'standard' },
                    { label: 'Wide content', value: 'wide' }
                ],
                onChange: function(value) {
                    updateHeroWidth(value);
                }
            }) : null,
            preset === 'hero' ? createElement(SelectControl, {
                label: 'Hero text alignment',
                value: hasClass(className, 'hero-text-left') ? 'left' : 'center',
                options: [
                    { label: 'Center', value: 'center' },
                    { label: 'Left', value: 'left' }
                ],
                onChange: function(value) {
                    updateClassName(function(currentClassName) {
                        return setHeroTextClass(currentClassName, value);
                    });
                }
            }) : null
        );
    }

    function getLayoutClass(attrs, className) {
        className = mergeClassName(className, 'ajn-layout-control');

        if (
            attrs.ajnMinHeightDesktop ||
            attrs.ajnMinHeightTablet ||
            attrs.ajnMinHeightMobile ||
            attrs.ajnHeightDesktop ||
            attrs.ajnHeightTablet ||
            attrs.ajnHeightMobile
        ) {
            className = mergeClassName(className, 'ajn-has-height-override');
        }

        if (
            attrs.ajnPaddingTopDesktop ||
            attrs.ajnPaddingRightDesktop ||
            attrs.ajnPaddingBottomDesktop ||
            attrs.ajnPaddingLeftDesktop ||
            attrs.ajnPaddingTopTablet ||
            attrs.ajnPaddingRightTablet ||
            attrs.ajnPaddingBottomTablet ||
            attrs.ajnPaddingLeftTablet ||
            attrs.ajnPaddingTopMobile ||
            attrs.ajnPaddingRightMobile ||
            attrs.ajnPaddingBottomMobile ||
            attrs.ajnPaddingLeftMobile
        ) {
            className = mergeClassName(className, 'ajn-has-padding-override');
        }

        if (attrs.ajnUseFixedHeight) {
            className = mergeClassName(className, 'ajn-fixed-height');
        }

        if (attrs.ajnHeightOverflowHidden) {
            className = mergeClassName(className, 'ajn-overflow-hidden');
        }

        return className;
    }

    function getButtonLayoutClass(attrs, className) {
        className = mergeClassName(className || '', 'aj-buttons-control');
        className = removeClasses(className, [
            'aj-buttons-desktop-row',
            'aj-buttons-desktop-stack',
            'aj-buttons-desktop-grid',
            'aj-buttons-desktop-featured',
            'aj-buttons-tablet-row',
            'aj-buttons-tablet-stack',
            'aj-buttons-tablet-grid',
            'aj-buttons-tablet-featured',
            'aj-buttons-mobile-row',
            'aj-buttons-mobile-stack',
            'aj-buttons-mobile-grid',
            'aj-buttons-mobile-featured',
            'aj-buttons-width-desktop-auto',
            'aj-buttons-width-desktop-narrow',
            'aj-buttons-width-desktop-standard',
            'aj-buttons-width-desktop-wide',
            'aj-buttons-width-desktop-full',
            'aj-buttons-width-desktop-custom',
            'aj-buttons-width-tablet-auto',
            'aj-buttons-width-tablet-narrow',
            'aj-buttons-width-tablet-standard',
            'aj-buttons-width-tablet-wide',
            'aj-buttons-width-tablet-full',
            'aj-buttons-width-tablet-custom',
            'aj-buttons-width-mobile-auto',
            'aj-buttons-width-mobile-narrow',
            'aj-buttons-width-mobile-standard',
            'aj-buttons-width-mobile-wide',
            'aj-buttons-width-mobile-full',
            'aj-buttons-width-mobile-custom',
            'aj-buttons-stretch',
            'aj-has-btn-shared-styles',
            'aj-has-btn-per-colors'
        ]);

        className = mergeClassName(className, 'aj-buttons-desktop-' + (attrs.ajnButtonLayoutDesktop || 'row'));
        className = mergeClassName(className, 'aj-buttons-tablet-' + (attrs.ajnButtonLayoutTablet || attrs.ajnButtonLayoutDesktop || 'row'));
        className = mergeClassName(className, 'aj-buttons-mobile-' + (attrs.ajnButtonLayoutMobile || attrs.ajnButtonLayoutTablet || attrs.ajnButtonLayoutDesktop || 'stack'));
        className = mergeClassName(className, 'aj-buttons-width-desktop-' + (attrs.ajnButtonsWidthDesktop || 'auto'));
        className = mergeClassName(className, 'aj-buttons-width-tablet-' + (attrs.ajnButtonsWidthTablet || attrs.ajnButtonsWidthDesktop || 'auto'));
        className = mergeClassName(className, 'aj-buttons-width-mobile-' + (attrs.ajnButtonsWidthMobile || attrs.ajnButtonsWidthTablet || attrs.ajnButtonsWidthDesktop || 'auto'));

        if (attrs.ajnBtnJustify === 'stretch') {
            className = mergeClassName(className, 'aj-buttons-stretch');
        }

        var hasShared = !!attrs.ajnBtnStyle ||  // style preset always enables shared styles
            attrs.ajnBtnSharedBg || attrs.ajnBtnSharedColor || attrs.ajnBtnSharedBorderColor ||
            attrs.ajnBtnSharedBorderWidth || attrs.ajnBtnSharedBorderRadius ||
            attrs.ajnBtnSharedPaddingX || attrs.ajnBtnSharedPaddingY;
        if (hasShared) {
            className = mergeClassName(className, 'aj-has-btn-shared-styles');
        }

        var hasPerColors = attrs.ajnBtnColor1 || attrs.ajnBtnColor2 || attrs.ajnBtnColor3 || attrs.ajnBtnColor4 || attrs.ajnBtnColor5 || attrs.ajnBtnColor6;
        if (hasPerColors) {
            className = mergeClassName(className, 'aj-has-btn-per-colors');
        }

        return className;
    }

    function getButtonLayoutStyles(attrs) {
        var layoutDesktop = attrs.ajnButtonLayoutDesktop || 'row';
        var style = {
            '--aj-buttons-gap-desktop': (attrs.ajnButtonGapDesktop || 12) + 'px',
            '--aj-buttons-gap-tablet': (attrs.ajnButtonGapTablet || attrs.ajnButtonGapDesktop || 12) + 'px',
            '--aj-buttons-gap-mobile': (attrs.ajnButtonGapMobile || attrs.ajnButtonGapTablet || attrs.ajnButtonGapDesktop || 12) + 'px',
            // CSS var consumed by editor.css so the editor canvas shows the correct direction
            '--aj-editor-direction': layoutDesktop === 'stack' ? 'column' : 'row'
        };

        setVar(style, '--aj-buttons-custom-width-desktop', attrs.ajnButtonsCustomWidthDesktop);
        setVar(style, '--aj-buttons-custom-width-tablet', attrs.ajnButtonsCustomWidthTablet);
        setVar(style, '--aj-buttons-custom-width-mobile', attrs.ajnButtonsCustomWidthMobile);

        var justify = attrs.ajnBtnJustify || 'center';
        if (justify !== 'stretch') {
            style['--aj-btn-justify'] = justify;
        }

        // When a style preset is active, output ALL shared vars even when value is 0
        // (to correctly handle e.g. Square preset with borderRadius=0, Square preset
        //  needs to explicitly output 0px to override any inherited rounded corners)
        if (attrs.ajnBtnStyle) {
            style['--aj-btn-shared-bg']           = attrs.ajnBtnSharedBg || 'initial';
            style['--aj-btn-shared-color']         = attrs.ajnBtnSharedColor || 'inherit';
            style['--aj-btn-shared-border-color']  = attrs.ajnBtnSharedBorderColor || 'transparent';
            style['--aj-btn-shared-border-width']  = (attrs.ajnBtnSharedBorderWidth  || 0) + 'px';
            style['--aj-btn-shared-border-radius'] = (attrs.ajnBtnSharedBorderRadius || 0) + 'px';
            style['--aj-btn-shared-padding-x']     = (attrs.ajnBtnSharedPaddingX     || 0) + 'px';
            style['--aj-btn-shared-padding-y']     = (attrs.ajnBtnSharedPaddingY     || 0) + 'px';
        } else {
            if (attrs.ajnBtnSharedBg)           style['--aj-btn-shared-bg']           = attrs.ajnBtnSharedBg;
            if (attrs.ajnBtnSharedColor)         style['--aj-btn-shared-color']         = attrs.ajnBtnSharedColor;
            if (attrs.ajnBtnSharedBorderColor)   style['--aj-btn-shared-border-color']  = attrs.ajnBtnSharedBorderColor;
            if (attrs.ajnBtnSharedBorderWidth)   style['--aj-btn-shared-border-width']  = attrs.ajnBtnSharedBorderWidth  + 'px';
            if (attrs.ajnBtnSharedBorderRadius)  style['--aj-btn-shared-border-radius'] = attrs.ajnBtnSharedBorderRadius + 'px';
            if (attrs.ajnBtnSharedPaddingX)      style['--aj-btn-shared-padding-x']     = attrs.ajnBtnSharedPaddingX     + 'px';
            if (attrs.ajnBtnSharedPaddingY)      style['--aj-btn-shared-padding-y']     = attrs.ajnBtnSharedPaddingY     + 'px';
        }

        if (attrs.ajnBtnColor1) style['--aj-btn-color-1'] = attrs.ajnBtnColor1;
        if (attrs.ajnBtnColor2) style['--aj-btn-color-2'] = attrs.ajnBtnColor2;
        if (attrs.ajnBtnColor3) style['--aj-btn-color-3'] = attrs.ajnBtnColor3;
        if (attrs.ajnBtnColor4) style['--aj-btn-color-4'] = attrs.ajnBtnColor4;
        if (attrs.ajnBtnColor5) style['--aj-btn-color-5'] = attrs.ajnBtnColor5;
        if (attrs.ajnBtnColor6) style['--aj-btn-color-6'] = attrs.ajnBtnColor6;

        return style;
    }

    function hasButtonLayout(attrs) {
        var className = attrs.className || '';

        return className.split(/\s+/).indexOf('aj-buttons-control') !== -1 ||
            (attrs.ajnButtonLayoutDesktop && attrs.ajnButtonLayoutDesktop !== 'row') ||
            (attrs.ajnButtonLayoutTablet && attrs.ajnButtonLayoutTablet !== 'row') ||
            (attrs.ajnButtonLayoutMobile && attrs.ajnButtonLayoutMobile !== 'stack') ||
            (attrs.ajnButtonGapDesktop && attrs.ajnButtonGapDesktop !== 12) ||
            (attrs.ajnButtonGapTablet && attrs.ajnButtonGapTablet !== 12) ||
            (attrs.ajnButtonGapMobile && attrs.ajnButtonGapMobile !== 12) ||
            (attrs.ajnButtonsWidthDesktop && attrs.ajnButtonsWidthDesktop !== 'auto') ||
            (attrs.ajnButtonsWidthTablet && attrs.ajnButtonsWidthTablet !== 'auto') ||
            (attrs.ajnButtonsWidthMobile && attrs.ajnButtonsWidthMobile !== 'auto') ||
            !!attrs.ajnButtonsCustomWidthDesktop ||
            !!attrs.ajnButtonsCustomWidthTablet ||
            !!attrs.ajnButtonsCustomWidthMobile ||
            (attrs.ajnBtnJustify && attrs.ajnBtnJustify !== 'center') ||
            !!attrs.ajnBtnSharedBg || !!attrs.ajnBtnSharedColor || !!attrs.ajnBtnSharedBorderColor ||
            !!attrs.ajnBtnSharedBorderWidth || !!attrs.ajnBtnSharedBorderRadius ||
            !!attrs.ajnBtnColor1 || !!attrs.ajnBtnColor2 || !!attrs.ajnBtnColor3 ||
            !!attrs.ajnBtnColor4 || !!attrs.ajnBtnColor5 || !!attrs.ajnBtnColor6 ||
            !!attrs.ajnBtnStyle;
    }

    function getSingleButtonClass(attrs, className) {
        className = mergeClassName(className || '', 'aj-button-width-control');
        className = removeClasses(className, [
            'aj-button-width-desktop-auto',
            'aj-button-width-desktop-small',
            'aj-button-width-desktop-medium',
            'aj-button-width-desktop-large',
            'aj-button-width-desktop-full',
            'aj-button-width-desktop-custom',
            'aj-button-width-tablet-auto',
            'aj-button-width-tablet-small',
            'aj-button-width-tablet-medium',
            'aj-button-width-tablet-large',
            'aj-button-width-tablet-full',
            'aj-button-width-tablet-custom',
            'aj-button-width-mobile-auto',
            'aj-button-width-mobile-small',
            'aj-button-width-mobile-medium',
            'aj-button-width-mobile-large',
            'aj-button-width-mobile-full',
            'aj-button-width-mobile-custom',
            'aj-has-btn-item-color'
        ]);

        className = mergeClassName(className, 'aj-button-width-desktop-' + (attrs.ajnSingleButtonWidthDesktop || 'auto'));
        className = mergeClassName(className, 'aj-button-width-tablet-' + (attrs.ajnSingleButtonWidthTablet || attrs.ajnSingleButtonWidthDesktop || 'auto'));
        className = mergeClassName(className, 'aj-button-width-mobile-' + (attrs.ajnSingleButtonWidthMobile || attrs.ajnSingleButtonWidthTablet || attrs.ajnSingleButtonWidthDesktop || 'auto'));

        if (attrs.ajnSingleBtnBg || attrs.ajnSingleBtnColor || attrs.ajnSingleBtnBorderColor) {
            className = mergeClassName(className, 'aj-has-btn-item-color');
        }

        return className;
    }

    function getSingleButtonStyles(attrs) {
        var style = {};

        setVar(style, '--aj-button-custom-width-desktop', attrs.ajnSingleButtonCustomWidthDesktop);
        setVar(style, '--aj-button-custom-width-tablet', attrs.ajnSingleButtonCustomWidthTablet);
        setVar(style, '--aj-button-custom-width-mobile', attrs.ajnSingleButtonCustomWidthMobile);

        if (attrs.ajnSingleBtnBg) style['--aj-btn-item-bg'] = attrs.ajnSingleBtnBg;
        if (attrs.ajnSingleBtnColor) style['--aj-btn-item-color'] = attrs.ajnSingleBtnColor;
        if (attrs.ajnSingleBtnBorderColor) style['--aj-btn-item-border-color'] = attrs.ajnSingleBtnBorderColor;

        return style;
    }

    function hasSingleButtonLayout(attrs) {
        var className = attrs.className || '';

        return className.split(/\s+/).indexOf('aj-button-width-control') !== -1 ||
            (attrs.ajnSingleButtonWidthDesktop && attrs.ajnSingleButtonWidthDesktop !== 'auto') ||
            (attrs.ajnSingleButtonWidthTablet && attrs.ajnSingleButtonWidthTablet !== 'auto') ||
            (attrs.ajnSingleButtonWidthMobile && attrs.ajnSingleButtonWidthMobile !== 'auto') ||
            !!attrs.ajnSingleButtonCustomWidthDesktop ||
            !!attrs.ajnSingleButtonCustomWidthTablet ||
            !!attrs.ajnSingleButtonCustomWidthMobile ||
            !!attrs.ajnSingleBtnBg ||
            !!attrs.ajnSingleBtnColor ||
            !!attrs.ajnSingleBtnBorderColor;
    }

    function buttonLayoutControl(props, attr, label, fallback) {
        return createElement(SelectControl, {
            label: label,
            value: props.attributes[attr] || fallback,
            options: [
                { label: 'Horizontal row', value: 'row' },
                { label: 'Stacked', value: 'stack' },
                { label: 'Equal grid', value: 'grid' },
                { label: 'First wide, rest below', value: 'featured' }
            ],
            onChange: function(value) {
                var update = {};
                update[attr] = value;
                // Rebuild className so both the saved HTML and editor wrapperProps
                // immediately get the correct layout class (e.g. aj-buttons-desktop-stack).
                var newAttrs = Object.assign({}, props.attributes, update);
                update.className = getButtonLayoutClass(newAttrs, props.attributes.className || '');
                // Sync WP's native layout for the DESKTOP control so the editor's own
                // flex rendering matches (vertical = stack, horizontal = row/featured/grid).
                if (attr === 'ajnButtonLayoutDesktop') {
                    var justify = props.attributes.ajnBtnJustify || 'center';
                    var wpJustify = justify === 'flex-start' ? 'left' : justify === 'flex-end' ? 'right' : justify === 'space-between' ? 'space-between' : 'center';
                    update.layout = value === 'stack'
                        ? { type: 'flex', flexWrap: 'nowrap', orientation: 'vertical' }
                        : { type: 'flex', flexWrap: 'wrap', justifyContent: wpJustify };
                }
                props.setAttributes(update);
            }
        });
    }

    function buttonGapControl(props, attr, label, fallback) {
        return createElement(RangeControl, {
            label: label,
            min: 0,
            max: 60,
            value: props.attributes[attr] || fallback,
            onChange: function(value) {
                var update = {};
                update[attr] = value;
                props.setAttributes(update);
            }
        });
    }

    function widthControl(props, attr, label, fallback) {
        return createElement(SelectControl, {
            label: label,
            value: props.attributes[attr] || fallback,
            options: [
                { label: 'Auto', value: 'auto' },
                { label: 'Narrow', value: 'narrow' },
                { label: 'Standard', value: 'standard' },
                { label: 'Wide', value: 'wide' },
                { label: 'Full width', value: 'full' },
                { label: 'Custom', value: 'custom' }
            ],
            onChange: function(value) {
                var update = {};
                update[attr] = value;
                props.setAttributes(update);
            }
        });
    }

    function singleButtonWidthControl(props, attr, label, fallback) {
        return createElement(SelectControl, {
            label: label,
            value: props.attributes[attr] || fallback,
            options: [
                { label: 'Auto', value: 'auto' },
                { label: 'Small', value: 'small' },
                { label: 'Medium', value: 'medium' },
                { label: 'Large', value: 'large' },
                { label: 'Full row', value: 'full' },
                { label: 'Custom', value: 'custom' }
            ],
            onChange: function(value) {
                var update = {};
                update[attr] = value;
                props.setAttributes(update);
            }
        });
    }

    function useMeasuredBlockHeight(clientId) {
        var state = useState('');
        var measuredHeight = state[0];
        var setMeasuredHeight = state[1];

        useEffect(function() {
            if (!clientId || typeof document === 'undefined') {
                return;
            }

            var block = document.querySelector('[data-block="' + clientId + '"]');
            if (!block) {
                setMeasuredHeight('');
                return;
            }

            function updateHeight() {
                var rect = block.getBoundingClientRect();
                if (rect && rect.height) {
                    setMeasuredHeight(Math.round(rect.height) + 'px');
                }
            }

            updateHeight();

            if (typeof ResizeObserver !== 'undefined') {
                var observer = new ResizeObserver(updateHeight);
                observer.observe(block);

                return function() {
                    observer.disconnect();
                };
            }

            var interval = setInterval(updateHeight, 500);

            return function() {
                clearInterval(interval);
            };
        }, [clientId]);

        return measuredHeight;
    }

    function field(label, value, placeholder, help, change) {
        return createElement(TextControl, {
            label: label,
            value: value || '',
            placeholder: placeholder || '',
            help: help || '',
            onChange: change
        });
    }

    function registerHeroBlockVariation() {
        if (!wp.blocks || !wp.blocks.registerBlockVariation) {
            return;
        }

        wp.blocks.registerBlockVariation('core/group', {
            name: 'ajnanda-hero',
            title: 'AJNanda Hero',
            description: 'Add a centered theme hero section.',
            icon: 'cover-image',
            keywords: ['hero', 'page header', 'post header'],
            attributes: {
                align: 'full',
                className: 'builder-hero-section hero-width-full',
                layout: {
                    type: 'flex',
                    orientation: 'vertical',
                    justifyContent: 'center',
                    verticalAlignment: 'center',
                    flexWrap: 'nowrap'
                }
            },
            innerBlocks: [
                [
                    'core/heading',
                    {
                        textAlign: 'center',
                        level: 1,
                        content: 'Page Hero'
                    },
                    []
                ]
            ],
            scope: ['inserter'],
            isActive: function(blockAttributes) {
                var className = blockAttributes.className || '';
                return className.split(/\s+/).indexOf('builder-hero-section') !== -1;
            }
        });
    }

    registerHeroBlockVariation();

    function registerButtonsBlockVariation() {
        if (!registerBlockVariation) {
            return;
        }

        registerBlockVariation('core/buttons', {
            name: 'ajnanda-buttons',
            title: 'AJ Buttons',
            description: 'Native WordPress buttons with AJNanda responsive layout controls.',
            icon: 'button',
            category: 'ajnanda-blocks',
            attributes: {
                className: 'aj-buttons-control aj-buttons-desktop-row aj-buttons-tablet-row aj-buttons-mobile-featured',
                ajnButtonLayoutDesktop: 'row',
                ajnButtonLayoutTablet: 'row',
                ajnButtonLayoutMobile: 'featured',
                ajnButtonGapDesktop: 12,
                ajnButtonGapTablet: 12,
                ajnButtonGapMobile: 12
            },
            innerBlocks: [
                ['core/button', { text: 'Button', className: 'aj-button-width-control aj-button-width-desktop-medium aj-button-width-tablet-medium aj-button-width-mobile-full', ajnSingleButtonWidthDesktop: 'medium', ajnSingleButtonWidthTablet: 'medium', ajnSingleButtonWidthMobile: 'full' }],
                ['core/button', { text: 'Button', className: 'aj-button-width-control aj-button-width-desktop-medium aj-button-width-tablet-medium aj-button-width-mobile-medium', ajnSingleButtonWidthDesktop: 'medium', ajnSingleButtonWidthTablet: 'medium', ajnSingleButtonWidthMobile: 'medium' }],
                ['core/button', { text: 'Button', className: 'aj-button-width-control aj-button-width-desktop-medium aj-button-width-tablet-medium aj-button-width-mobile-medium', ajnSingleButtonWidthDesktop: 'medium', ajnSingleButtonWidthTablet: 'medium', ajnSingleButtonWidthMobile: 'medium' }]
            ],
            scope: ['inserter'],
            isActive: function(blockAttributes) {
                return (blockAttributes.className || '').split(/\s+/).indexOf('aj-buttons-control') !== -1;
            }
        });
    }

    registerButtonsBlockVariation();

    addFilter('blocks.registerBlockType', 'ajn/block-layout-attributes', function(settings, name) {
        var blockName = name || settings.name;

        if ('core/buttons' === blockName) {
            settings.attributes = Object.assign({}, settings.attributes || {}, BUTTON_LAYOUT_ATTRS);
        }

        if ('core/button' === blockName) {
            settings.attributes = Object.assign({}, settings.attributes || {}, SINGLE_BUTTON_ATTRS);
        }

        if (!hasLayoutControls(blockName)) {
            return settings;
        }

        settings.attributes = Object.assign({}, settings.attributes || {}, LAYOUT_ATTRS);
        return settings;
    });

    addFilter(
        'editor.BlockEdit',
        'ajn/block-layout-controls',
        createHigherOrderComponent(function(BlockEdit) {
            return function(props) {
                if (!hasLayoutControls(props.name)) {
                    if ('core/buttons' !== props.name && 'core/button' !== props.name) {
                        return createElement(BlockEdit, props);
                    }
                }

                var attrs = props.attributes || {};
                var setAttributes = props.setAttributes;
                var measuredHeight = useMeasuredBlockHeight(props.clientId);
                var innerBlockCount = useSelect ? useSelect(function(select) {
                    return select('core/block-editor').getBlockCount(props.clientId);
                }, [props.clientId]) : 0;

                if ('core/buttons' === props.name) {
                    var deviceTabState = useState('desktop');
                    var activeDevice = deviceTabState[0];
                    var setActiveDevice = deviceTabState[1];

                    var gapForDevice = activeDevice === 'mobile'
                        ? (attrs.ajnButtonGapMobile || attrs.ajnButtonGapTablet || attrs.ajnButtonGapDesktop || 12)
                        : activeDevice === 'tablet'
                        ? (attrs.ajnButtonGapTablet || attrs.ajnButtonGapDesktop || 12)
                        : (attrs.ajnButtonGapDesktop || 12);

                    var widthAttr = activeDevice === 'mobile' ? 'ajnButtonsWidthMobile'
                        : activeDevice === 'tablet' ? 'ajnButtonsWidthTablet'
                        : 'ajnButtonsWidthDesktop';
                    var customWidthAttr = activeDevice === 'mobile' ? 'ajnButtonsCustomWidthMobile'
                        : activeDevice === 'tablet' ? 'ajnButtonsCustomWidthTablet'
                        : 'ajnButtonsCustomWidthDesktop';
                    var widthFallback = activeDevice === 'mobile'
                        ? (attrs.ajnButtonsWidthMobile || attrs.ajnButtonsWidthTablet || attrs.ajnButtonsWidthDesktop || 'auto')
                        : activeDevice === 'tablet'
                        ? (attrs.ajnButtonsWidthTablet || attrs.ajnButtonsWidthDesktop || 'auto')
                        : (attrs.ajnButtonsWidthDesktop || 'auto');
                    var layoutAttr = activeDevice === 'mobile' ? 'ajnButtonLayoutMobile'
                        : activeDevice === 'tablet' ? 'ajnButtonLayoutTablet'
                        : 'ajnButtonLayoutDesktop';
                    var layoutFallback = activeDevice === 'mobile'
                        ? (attrs.ajnButtonLayoutMobile || attrs.ajnButtonLayoutTablet || attrs.ajnButtonLayoutDesktop || 'stack')
                        : activeDevice === 'tablet'
                        ? (attrs.ajnButtonLayoutTablet || attrs.ajnButtonLayoutDesktop || 'row')
                        : (attrs.ajnButtonLayoutDesktop || 'row');

                    var deviceTabsEl = createElement('div', { className: 'ajn-device-tabs' },
                        ['desktop', 'tablet', 'mobile'].map(function(d) {
                            return createElement('button', {
                                key: d,
                                type: 'button',
                                className: 'ajn-device-tab' + (activeDevice === d ? ' is-selected' : ''),
                                onClick: function() { setActiveDevice(d); }
                            }, d.charAt(0).toUpperCase() + d.slice(1));
                        })
                    );

                    var justify = attrs.ajnBtnJustify || 'center';

                    return createElement(
                        Fragment,
                        {},
                        createElement(BlockEdit, props),
                        createElement(
                            InspectorControls,
                            {},
                            createElement(
                                PanelBody,
                                { title: 'AJ Buttons — Layout & Spacing', initialOpen: true },
                                deviceTabsEl,
                                buttonLayoutControl(props, layoutAttr, 'Arrangement', layoutFallback),
                                createElement(SelectControl, {
                                    label: 'Justify content',
                                    value: justify,
                                    options: [
                                        { label: 'Center', value: 'center' },
                                        { label: 'Left', value: 'flex-start' },
                                        { label: 'Right', value: 'flex-end' },
                                        { label: 'Space between', value: 'space-between' },
                                        { label: 'Space evenly', value: 'space-evenly' },
                                        { label: 'Stretch (fill row)', value: 'stretch' }
                                    ],
                                    onChange: function(value) {
                                        var update = { ajnBtnJustify: value };
                                        // Keep WP native layout in sync so the editor canvas shows correct justify
                                        var currentLayout = attrs.layout || {};
                                        if (!currentLayout.orientation || currentLayout.orientation !== 'vertical') {
                                            var wpJustify = value === 'flex-start' ? 'left' : value === 'flex-end' ? 'right' : value === 'space-between' ? 'space-between' : 'center';
                                            update.layout = { type: 'flex', flexWrap: 'wrap', justifyContent: wpJustify };
                                        }
                                        setAttributes(update);
                                    }
                                }),
                                createElement(RangeControl, {
                                    label: 'Gap (' + activeDevice.charAt(0).toUpperCase() + activeDevice.slice(1) + ')',
                                    min: 0,
                                    max: 60,
                                    value: gapForDevice,
                                    onChange: function(value) {
                                        var update = {};
                                        update['ajnButtonGap' + activeDevice.charAt(0).toUpperCase() + activeDevice.slice(1)] = value;
                                        setAttributes(update);
                                    }
                                }),
                                createElement(SelectControl, {
                                    label: 'Area width (' + activeDevice.charAt(0).toUpperCase() + activeDevice.slice(1) + ')',
                                    value: widthFallback,
                                    options: [
                                        { label: 'Auto (shrink to content)', value: 'auto' },
                                        { label: 'Narrow — 480px', value: 'narrow' },
                                        { label: 'Standard — 720px', value: 'standard' },
                                        { label: 'Wide — 960px', value: 'wide' },
                                        { label: 'Full width', value: 'full' },
                                        { label: 'Custom', value: 'custom' }
                                    ],
                                    onChange: function(value) {
                                        var update = {};
                                        update[widthAttr] = value;
                                        setAttributes(update);
                                    }
                                }),
                                attrs[widthAttr] === 'custom' ? field('Custom width', attrs[customWidthAttr], 'e.g. 760px or 80%', '', function(value) {
                                    var update = {};
                                    update[customWidthAttr] = value;
                                    setAttributes(update);
                                }) : null
                            ),
                            createElement(
                                PanelBody,
                                { title: 'AJ Buttons — Button Style', initialOpen: false },
                                createElement('p', { style: { fontSize: '12px', color: '#6b7280', marginBottom: '12px' } },
                                    'Choose a preset style for all buttons — sets background, text color, border, radius and padding in one step.'
                                ),
                                createElement(SelectControl, {
                                    label: 'Style preset',
                                    value: attrs.ajnBtnStyle || '',
                                    options: (function() {
                                        var opts = [{ label: '— WP default (no override) —', value: '' }];
                                        AJN_BUTTON_STYLES.forEach(function(s) { opts.push({ label: s.label, value: s.value }); });
                                        return opts;
                                    })(),
                                    onChange: function(styleValue) {
                                        var style = null;
                                        AJN_BUTTON_STYLES.forEach(function(s) { if (s.value === styleValue) { style = s; } });
                                        if (!style || !styleValue) {
                                            setAttributes({ ajnBtnStyle: '', ajnBtnSharedBg: '', ajnBtnSharedColor: '', ajnBtnSharedBorderColor: '', ajnBtnSharedBorderWidth: 0, ajnBtnSharedBorderRadius: 0, ajnBtnSharedPaddingX: 0, ajnBtnSharedPaddingY: 0 });
                                            return;
                                        }
                                        setAttributes({
                                            ajnBtnStyle: styleValue,
                                            ajnBtnSharedBg: style.bg || '',
                                            ajnBtnSharedColor: style.color || '',
                                            ajnBtnSharedBorderColor: style.borderColor || '',
                                            ajnBtnSharedBorderWidth: style.borderWidth || 0,
                                            ajnBtnSharedBorderRadius: style.borderRadius || 0,
                                            ajnBtnSharedPaddingX: style.paddingX || 0,
                                            ajnBtnSharedPaddingY: style.paddingY || 0
                                        });
                                    }
                                }),
                                attrs.ajnBtnStyle ? createElement('p', { style: { fontSize: '11px', color: '#059669', margin: '8px 0 0', fontStyle: 'italic' } },
                                    'Active: ' + (AJN_BUTTON_STYLES.filter(function(s) { return s.value === attrs.ajnBtnStyle; })[0] || {}).label
                                ) : null
                            ),
                            createElement(
                                PanelBody,
                                { title: 'AJ Buttons — Individual Colors', initialOpen: false },
                                createElement('p', { style: { fontSize: '12px', color: '#6b7280', marginBottom: '12px' } },
                                    'Set a unique background color for each button by position. Leave blank to use shared style or WP default.'
                                ),
                                createElement(SelectControl, {
                                    label: 'Color scheme',
                                    value: attrs.ajnBtnColorSchema || '',
                                    options: (function() {
                                        var opts = [{ label: '— Pick a preset scheme —', value: '' }];
                                        AJN_COLOR_SCHEMES.forEach(function(s) { opts.push({ label: s.label, value: s.value }); });
                                        return opts;
                                    })(),
                                    onChange: function(schemeValue) {
                                        var scheme = null;
                                        AJN_COLOR_SCHEMES.forEach(function(s) { if (s.value === schemeValue) { scheme = s; } });
                                        if (!scheme || !schemeValue) {
                                            setAttributes({ ajnBtnColorSchema: '' });
                                            return;
                                        }
                                        var count = innerBlockCount > 0 ? Math.min(innerBlockCount, 6) : 6;
                                        var update = { ajnBtnColorSchema: schemeValue };
                                        for (var i = 1; i <= 6; i++) {
                                            update['ajnBtnColor' + i] = i <= count
                                                ? (scheme.colors[i - 1] || scheme.colors[(i - 1) % scheme.colors.length])
                                                : '';
                                        }
                                        setAttributes(update);
                                    }
                                }),
                                (function() {
                                    var btnCount = innerBlockCount > 0 ? Math.min(innerBlockCount, 6) : 6;
                                    return Array.from({ length: btnCount }, function(_, i) { return i + 1; }).map(function(n) {
                                        var attr = 'ajnBtnColor' + n;
                                        return createElement('div', { key: n, className: 'ajn-color-row' },
                                            createElement('label', { className: 'ajn-color-label' }, 'Button ' + n),
                                            createElement('input', {
                                                type: 'color',
                                                value: attrs[attr] || '#2563eb',
                                                onChange: function(e) {
                                                    var update = {};
                                                    update[attr] = e.target.value;
                                                    setAttributes(update);
                                                }
                                            }),
                                            createElement(TextControl, {
                                                value: attrs[attr] || '',
                                                placeholder: 'inherit',
                                                onChange: function(v) {
                                                    var update = {};
                                                    update[attr] = v;
                                                    setAttributes(update);
                                                }
                                            }),
                                            attrs[attr] ? createElement('button', {
                                                type: 'button', className: 'ajn-clear-btn',
                                                onClick: function() {
                                                    var update = {};
                                                    update[attr] = '';
                                                    setAttributes(update);
                                                }
                                            }, '✕') : null
                                        );
                                    });
                                })()
                            )
                        )
                    );
                }

                if ('core/button' === props.name) {
                    var btnDeviceState = useState('desktop');
                    var activeBtnDevice = btnDeviceState[0];
                    var setActiveBtnDevice = btnDeviceState[1];

                    var btnWidthAttr = activeBtnDevice === 'mobile' ? 'ajnSingleButtonWidthMobile'
                        : activeBtnDevice === 'tablet' ? 'ajnSingleButtonWidthTablet'
                        : 'ajnSingleButtonWidthDesktop';
                    var btnCustomWidthAttr = activeBtnDevice === 'mobile' ? 'ajnSingleButtonCustomWidthMobile'
                        : activeBtnDevice === 'tablet' ? 'ajnSingleButtonCustomWidthTablet'
                        : 'ajnSingleButtonCustomWidthDesktop';
                    var btnWidthFallback = activeBtnDevice === 'mobile'
                        ? (attrs.ajnSingleButtonWidthMobile || attrs.ajnSingleButtonWidthTablet || attrs.ajnSingleButtonWidthDesktop || 'auto')
                        : activeBtnDevice === 'tablet'
                        ? (attrs.ajnSingleButtonWidthTablet || attrs.ajnSingleButtonWidthDesktop || 'auto')
                        : (attrs.ajnSingleButtonWidthDesktop || 'auto');

                    var btnDeviceTabsEl = createElement('div', { className: 'ajn-device-tabs' },
                        ['desktop', 'tablet', 'mobile'].map(function(d) {
                            return createElement('button', {
                                key: d,
                                type: 'button',
                                className: 'ajn-device-tab' + (activeBtnDevice === d ? ' is-selected' : ''),
                                onClick: function() { setActiveBtnDevice(d); }
                            }, d.charAt(0).toUpperCase() + d.slice(1));
                        })
                    );

                    return createElement(
                        Fragment,
                        {},
                        createElement(BlockEdit, props),
                        createElement(
                            InspectorControls,
                            {},
                            createElement(
                                PanelBody,
                                { title: 'AJNanda Button Width', initialOpen: true },
                                btnDeviceTabsEl,
                                singleButtonWidthControl(props, btnWidthAttr, 'Width (' + activeBtnDevice.charAt(0).toUpperCase() + activeBtnDevice.slice(1) + ')', btnWidthFallback),
                                attrs[btnWidthAttr] === 'custom' ? field('Custom width', attrs[btnCustomWidthAttr], 'e.g. 220px or 50%', '', function(value) {
                                    var update = {};
                                    update[btnCustomWidthAttr] = value;
                                    setAttributes(update);
                                }) : null
                            ),
                            createElement(
                                PanelBody,
                                { title: 'AJNanda Button Color', initialOpen: false },
                                createElement('p', { style: { fontSize: '12px', color: '#6b7280', marginBottom: '12px' } },
                                    'Override the color of this specific button. Overrides shared group styles.'
                                ),
                                createElement('div', { className: 'ajn-color-row' },
                                    createElement('label', { className: 'ajn-color-label' }, 'Background'),
                                    createElement('input', {
                                        type: 'color',
                                        value: attrs.ajnSingleBtnBg || '#2563eb',
                                        onChange: function(e) { setAttributes({ ajnSingleBtnBg: e.target.value }); }
                                    }),
                                    createElement(TextControl, {
                                        value: attrs.ajnSingleBtnBg || '',
                                        placeholder: 'inherit',
                                        onChange: function(v) { setAttributes({ ajnSingleBtnBg: v }); }
                                    }),
                                    attrs.ajnSingleBtnBg ? createElement('button', {
                                        type: 'button', className: 'ajn-clear-btn',
                                        onClick: function() { setAttributes({ ajnSingleBtnBg: '' }); }
                                    }, '✕') : null
                                ),
                                createElement('div', { className: 'ajn-color-row' },
                                    createElement('label', { className: 'ajn-color-label' }, 'Text color'),
                                    createElement('input', {
                                        type: 'color',
                                        value: attrs.ajnSingleBtnColor || '#ffffff',
                                        onChange: function(e) { setAttributes({ ajnSingleBtnColor: e.target.value }); }
                                    }),
                                    createElement(TextControl, {
                                        value: attrs.ajnSingleBtnColor || '',
                                        placeholder: 'inherit',
                                        onChange: function(v) { setAttributes({ ajnSingleBtnColor: v }); }
                                    }),
                                    attrs.ajnSingleBtnColor ? createElement('button', {
                                        type: 'button', className: 'ajn-clear-btn',
                                        onClick: function() { setAttributes({ ajnSingleBtnColor: '' }); }
                                    }, '✕') : null
                                ),
                                createElement('div', { className: 'ajn-color-row' },
                                    createElement('label', { className: 'ajn-color-label' }, 'Border color'),
                                    createElement('input', {
                                        type: 'color',
                                        value: attrs.ajnSingleBtnBorderColor || '#1d4ed8',
                                        onChange: function(e) { setAttributes({ ajnSingleBtnBorderColor: e.target.value }); }
                                    }),
                                    createElement(TextControl, {
                                        value: attrs.ajnSingleBtnBorderColor || '',
                                        placeholder: 'inherit',
                                        onChange: function(v) { setAttributes({ ajnSingleBtnBorderColor: v }); }
                                    }),
                                    attrs.ajnSingleBtnBorderColor ? createElement('button', {
                                        type: 'button', className: 'ajn-clear-btn',
                                        onClick: function() { setAttributes({ ajnSingleBtnBorderColor: '' }); }
                                    }, '✕') : null
                                )
                            )
                        )
                    );
                }

                return createElement(
                    Fragment,
                    {},
                    createElement(BlockEdit, props),
                    createElement(
                        InspectorControls,
                        {},
                        designPresetControls(props),
                        classCheckboxes(props),
                        createElement(
                            PanelBody,
                            {
                                title: 'Block Layout',
                                initialOpen: true
                            },
                            measuredHeight ? createElement(Notice, {
                                status: 'info',
                                isDismissible: false
                            }, 'Current editor height: ' + measuredHeight) : null,

                            field('Minimum height - Desktop', attrs.ajnMinHeightDesktop || attrs.ajnHeightDesktop, measuredHeight || 'auto', 'Use this for page hero sections. Example: 350px, 60vh, 40rem.', function(value) {
                                setAttributes({ ajnMinHeightDesktop: value });
                            }),
                            field('Minimum height - Tablet', attrs.ajnMinHeightTablet || attrs.ajnHeightTablet, attrs.ajnMinHeightDesktop || attrs.ajnHeightDesktop || measuredHeight || 'auto', 'Leave blank to use desktop value.', function(value) {
                                setAttributes({ ajnMinHeightTablet: value });
                            }),
                            field('Minimum height - Mobile', attrs.ajnMinHeightMobile || attrs.ajnHeightMobile, attrs.ajnMinHeightTablet || attrs.ajnMinHeightDesktop || attrs.ajnHeightDesktop || measuredHeight || 'auto', 'Leave blank to use tablet or desktop value.', function(value) {
                                setAttributes({ ajnMinHeightMobile: value });
                            }),

                            createElement(ToggleControl, {
                                label: 'Use fixed height instead of minimum height',
                                checked: !!attrs.ajnUseFixedHeight,
                                onChange: function(value) {
                                    setAttributes({ ajnUseFixedHeight: value });
                                }
                            }),
                            attrs.ajnUseFixedHeight ? createElement(Notice, {
                                status: 'warning',
                                isDismissible: false
                            }, 'Fixed height can cut off text. Use minimum height for hero/text sections.') : null,
                            attrs.ajnUseFixedHeight ? field('Fixed height - Desktop', attrs.ajnHeightDesktop, measuredHeight || 'auto', 'Use mostly for image boxes or empty spacers.', function(value) {
                                setAttributes({ ajnHeightDesktop: value });
                            }) : null,
                            attrs.ajnUseFixedHeight ? field('Fixed height - Tablet', attrs.ajnHeightTablet, attrs.ajnHeightDesktop || measuredHeight || 'auto', '', function(value) {
                                setAttributes({ ajnHeightTablet: value });
                            }) : null,
                            attrs.ajnUseFixedHeight ? field('Fixed height - Mobile', attrs.ajnHeightMobile, attrs.ajnHeightTablet || attrs.ajnHeightDesktop || measuredHeight || 'auto', '', function(value) {
                                setAttributes({ ajnHeightMobile: value });
                            }) : null,
                            attrs.ajnUseFixedHeight ? createElement(ToggleControl, {
                                label: 'Hide overflow',
                                checked: !!attrs.ajnHeightOverflowHidden,
                                onChange: function(value) {
                                    setAttributes({ ajnHeightOverflowHidden: value });
                                }
                            }) : null,

                            createElement(PanelBody, { title: 'Padding - Desktop', initialOpen: false },
                                field('Top', attrs.ajnPaddingTopDesktop, 'Example: 3rem', '', function(value) { setAttributes({ ajnPaddingTopDesktop: value }); }),
                                field('Bottom', attrs.ajnPaddingBottomDesktop, 'Example: 3rem', '', function(value) { setAttributes({ ajnPaddingBottomDesktop: value }); }),
                                field('Left', attrs.ajnPaddingLeftDesktop, 'Example: 1.5rem', '', function(value) { setAttributes({ ajnPaddingLeftDesktop: value }); }),
                                field('Right', attrs.ajnPaddingRightDesktop, 'Example: 1.5rem', '', function(value) { setAttributes({ ajnPaddingRightDesktop: value }); })
                            ),
                            createElement(PanelBody, { title: 'Padding - Tablet', initialOpen: false },
                                field('Top', attrs.ajnPaddingTopTablet, 'Leave blank to use desktop', '', function(value) { setAttributes({ ajnPaddingTopTablet: value }); }),
                                field('Bottom', attrs.ajnPaddingBottomTablet, 'Leave blank to use desktop', '', function(value) { setAttributes({ ajnPaddingBottomTablet: value }); }),
                                field('Left', attrs.ajnPaddingLeftTablet, 'Leave blank to use desktop', '', function(value) { setAttributes({ ajnPaddingLeftTablet: value }); }),
                                field('Right', attrs.ajnPaddingRightTablet, 'Leave blank to use desktop', '', function(value) { setAttributes({ ajnPaddingRightTablet: value }); })
                            ),
                            createElement(PanelBody, { title: 'Padding - Mobile', initialOpen: false },
                                field('Top', attrs.ajnPaddingTopMobile, 'Leave blank to use tablet/desktop', '', function(value) { setAttributes({ ajnPaddingTopMobile: value }); }),
                                field('Bottom', attrs.ajnPaddingBottomMobile, 'Leave blank to use tablet/desktop', '', function(value) { setAttributes({ ajnPaddingBottomMobile: value }); }),
                                field('Left', attrs.ajnPaddingLeftMobile, 'Leave blank to use tablet/desktop', '', function(value) { setAttributes({ ajnPaddingLeftMobile: value }); }),
                                field('Right', attrs.ajnPaddingRightMobile, 'Leave blank to use tablet/desktop', '', function(value) { setAttributes({ ajnPaddingRightMobile: value }); })
                            )
                        )
                    )
                );
            };
        }, 'withAjnBlockLayoutControls')
    );

    addFilter(
        'editor.BlockListBlock',
        'ajn/live-block-layout-preview',
        createHigherOrderComponent(function(BlockListBlock) {
            return function(props) {
                var attrs = props.attributes || {};
                var wrapperProps = Object.assign({}, props.wrapperProps || {});
                var existingStyle = Object.assign({}, wrapperProps.style || {});

                if (hasLayoutControls(props.name) && hasLayout(attrs)) {
                    wrapperProps.className = getLayoutClass(attrs, wrapperProps.className);
                    wrapperProps.style = Object.assign(existingStyle, getLayoutStyles(attrs));
                }

                if ('core/buttons' === props.name && hasButtonLayout(attrs)) {
                    wrapperProps.className = getButtonLayoutClass(attrs, wrapperProps.className);
                    wrapperProps.style = Object.assign(existingStyle, getButtonLayoutStyles(attrs));
                }

                if ('core/button' === props.name && hasSingleButtonLayout(attrs)) {
                    wrapperProps.className = getSingleButtonClass(attrs, wrapperProps.className);
                    wrapperProps.style = Object.assign(existingStyle, getSingleButtonStyles(attrs));
                }

                return createElement(BlockListBlock, Object.assign({}, props, { wrapperProps: wrapperProps }));
            };
        }, 'withAjnLiveBlockLayoutPreview')
    );

    addFilter('blocks.getSaveContent.extraProps', 'ajn/save-block-layout-props', function(extraProps, blockType, attrs) {
        attrs = attrs || {};

        if (!hasLayoutControls(blockType.name) || !hasLayout(attrs)) {
            if (('core/buttons' !== blockType.name || !hasButtonLayout(attrs)) && ('core/button' !== blockType.name || !hasSingleButtonLayout(attrs))) {
                return extraProps;
            }
        }

        if (hasLayoutControls(blockType.name) && hasLayout(attrs)) {
            extraProps.className = getLayoutClass(attrs, extraProps.className);
            extraProps.style = Object.assign({}, extraProps.style || {}, getLayoutStyles(attrs));
        }

        if ('core/buttons' === blockType.name && hasButtonLayout(attrs)) {
            extraProps.className = getButtonLayoutClass(attrs, extraProps.className);
            extraProps.style = Object.assign({}, extraProps.style || {}, getButtonLayoutStyles(attrs));
        }

        if ('core/button' === blockType.name && hasSingleButtonLayout(attrs)) {
            extraProps.className = getSingleButtonClass(attrs, extraProps.className);
            extraProps.style = Object.assign({}, extraProps.style || {}, getSingleButtonStyles(attrs));
        }

        return extraProps;
    });

})(window.wp);
