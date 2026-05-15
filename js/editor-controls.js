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
        ajnButtonsCustomWidthMobile: { type: 'string', default: '' }
    };

    var SINGLE_BUTTON_ATTRS = {
        ajnSingleButtonWidthDesktop: { type: 'string', default: 'auto' },
        ajnSingleButtonWidthTablet: { type: 'string', default: 'auto' },
        ajnSingleButtonWidthMobile: { type: 'string', default: 'auto' },
        ajnSingleButtonCustomWidthDesktop: { type: 'string', default: '' },
        ajnSingleButtonCustomWidthTablet: { type: 'string', default: '' },
        ajnSingleButtonCustomWidthMobile: { type: 'string', default: '' }
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
            'aj-buttons-width-mobile-custom'
        ]);

        className = mergeClassName(className, 'aj-buttons-desktop-' + (attrs.ajnButtonLayoutDesktop || 'row'));
        className = mergeClassName(className, 'aj-buttons-tablet-' + (attrs.ajnButtonLayoutTablet || attrs.ajnButtonLayoutDesktop || 'row'));
        className = mergeClassName(className, 'aj-buttons-mobile-' + (attrs.ajnButtonLayoutMobile || attrs.ajnButtonLayoutTablet || attrs.ajnButtonLayoutDesktop || 'stack'));
        className = mergeClassName(className, 'aj-buttons-width-desktop-' + (attrs.ajnButtonsWidthDesktop || 'auto'));
        className = mergeClassName(className, 'aj-buttons-width-tablet-' + (attrs.ajnButtonsWidthTablet || attrs.ajnButtonsWidthDesktop || 'auto'));
        className = mergeClassName(className, 'aj-buttons-width-mobile-' + (attrs.ajnButtonsWidthMobile || attrs.ajnButtonsWidthTablet || attrs.ajnButtonsWidthDesktop || 'auto'));

        return className;
    }

    function getButtonLayoutStyles(attrs) {
        var style = {
            '--aj-buttons-gap-desktop': (attrs.ajnButtonGapDesktop || 12) + 'px',
            '--aj-buttons-gap-tablet': (attrs.ajnButtonGapTablet || attrs.ajnButtonGapDesktop || 12) + 'px',
            '--aj-buttons-gap-mobile': (attrs.ajnButtonGapMobile || attrs.ajnButtonGapTablet || attrs.ajnButtonGapDesktop || 12) + 'px'
        };

        setVar(style, '--aj-buttons-custom-width-desktop', attrs.ajnButtonsCustomWidthDesktop);
        setVar(style, '--aj-buttons-custom-width-tablet', attrs.ajnButtonsCustomWidthTablet);
        setVar(style, '--aj-buttons-custom-width-mobile', attrs.ajnButtonsCustomWidthMobile);

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
            !!attrs.ajnButtonsCustomWidthMobile;
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
            'aj-button-width-mobile-custom'
        ]);

        className = mergeClassName(className, 'aj-button-width-desktop-' + (attrs.ajnSingleButtonWidthDesktop || 'auto'));
        className = mergeClassName(className, 'aj-button-width-tablet-' + (attrs.ajnSingleButtonWidthTablet || attrs.ajnSingleButtonWidthDesktop || 'auto'));
        className = mergeClassName(className, 'aj-button-width-mobile-' + (attrs.ajnSingleButtonWidthMobile || attrs.ajnSingleButtonWidthTablet || attrs.ajnSingleButtonWidthDesktop || 'auto'));

        return className;
    }

    function getSingleButtonStyles(attrs) {
        var style = {};

        setVar(style, '--aj-button-custom-width-desktop', attrs.ajnSingleButtonCustomWidthDesktop);
        setVar(style, '--aj-button-custom-width-tablet', attrs.ajnSingleButtonCustomWidthTablet);
        setVar(style, '--aj-button-custom-width-mobile', attrs.ajnSingleButtonCustomWidthMobile);

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
            !!attrs.ajnSingleButtonCustomWidthMobile;
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

                if ('core/buttons' === props.name) {
                    return createElement(
                        Fragment,
                        {},
                        createElement(BlockEdit, props),
                        createElement(
                            InspectorControls,
                            {},
                            createElement(
                                PanelBody,
                                {
                                    title: 'AJNanda Button Layout',
                                    initialOpen: true
                                },
                                buttonLayoutControl(props, 'ajnButtonLayoutDesktop', 'Desktop layout', attrs.ajnButtonLayoutDesktop || 'row'),
                                buttonLayoutControl(props, 'ajnButtonLayoutTablet', 'Tablet layout', attrs.ajnButtonLayoutTablet || attrs.ajnButtonLayoutDesktop || 'row'),
                                buttonLayoutControl(props, 'ajnButtonLayoutMobile', 'Mobile layout', attrs.ajnButtonLayoutMobile || attrs.ajnButtonLayoutTablet || attrs.ajnButtonLayoutDesktop || 'stack'),
                                buttonGapControl(props, 'ajnButtonGapDesktop', 'Desktop gap', attrs.ajnButtonGapDesktop || 12),
                                buttonGapControl(props, 'ajnButtonGapTablet', 'Tablet gap', attrs.ajnButtonGapTablet || attrs.ajnButtonGapDesktop || 12),
                                buttonGapControl(props, 'ajnButtonGapMobile', 'Mobile gap', attrs.ajnButtonGapMobile || attrs.ajnButtonGapTablet || attrs.ajnButtonGapDesktop || 12),
                                widthControl(props, 'ajnButtonsWidthDesktop', 'Buttons area width - Desktop', attrs.ajnButtonsWidthDesktop || 'auto'),
                                attrs.ajnButtonsWidthDesktop === 'custom' ? field('Custom desktop width', attrs.ajnButtonsCustomWidthDesktop, 'Example: 760px or 70%', '', function(value) { setAttributes({ ajnButtonsCustomWidthDesktop: value }); }) : null,
                                widthControl(props, 'ajnButtonsWidthTablet', 'Buttons area width - Tablet', attrs.ajnButtonsWidthTablet || attrs.ajnButtonsWidthDesktop || 'auto'),
                                attrs.ajnButtonsWidthTablet === 'custom' ? field('Custom tablet width', attrs.ajnButtonsCustomWidthTablet, 'Leave blank to use desktop', '', function(value) { setAttributes({ ajnButtonsCustomWidthTablet: value }); }) : null,
                                widthControl(props, 'ajnButtonsWidthMobile', 'Buttons area width - Mobile', attrs.ajnButtonsWidthMobile || attrs.ajnButtonsWidthTablet || attrs.ajnButtonsWidthDesktop || 'auto'),
                                attrs.ajnButtonsWidthMobile === 'custom' ? field('Custom mobile width', attrs.ajnButtonsCustomWidthMobile, 'Leave blank to use tablet/desktop', '', function(value) { setAttributes({ ajnButtonsCustomWidthMobile: value }); }) : null
                            )
                        )
                    );
                }

                if ('core/button' === props.name) {
                    return createElement(
                        Fragment,
                        {},
                        createElement(BlockEdit, props),
                        createElement(
                            InspectorControls,
                            {},
                            createElement(
                                PanelBody,
                                {
                                    title: 'AJNanda Button Width',
                                    initialOpen: true
                                },
                                singleButtonWidthControl(props, 'ajnSingleButtonWidthDesktop', 'Desktop width', attrs.ajnSingleButtonWidthDesktop || 'auto'),
                                attrs.ajnSingleButtonWidthDesktop === 'custom' ? field('Custom desktop width', attrs.ajnSingleButtonCustomWidthDesktop, 'Example: 220px or 50%', '', function(value) { setAttributes({ ajnSingleButtonCustomWidthDesktop: value }); }) : null,
                                singleButtonWidthControl(props, 'ajnSingleButtonWidthTablet', 'Tablet width', attrs.ajnSingleButtonWidthTablet || attrs.ajnSingleButtonWidthDesktop || 'auto'),
                                attrs.ajnSingleButtonWidthTablet === 'custom' ? field('Custom tablet width', attrs.ajnSingleButtonCustomWidthTablet, 'Leave blank to use desktop', '', function(value) { setAttributes({ ajnSingleButtonCustomWidthTablet: value }); }) : null,
                                singleButtonWidthControl(props, 'ajnSingleButtonWidthMobile', 'Mobile width', attrs.ajnSingleButtonWidthMobile || attrs.ajnSingleButtonWidthTablet || attrs.ajnSingleButtonWidthDesktop || 'auto'),
                                attrs.ajnSingleButtonWidthMobile === 'custom' ? field('Custom mobile width', attrs.ajnSingleButtonCustomWidthMobile, 'Leave blank to use tablet/desktop', '', function(value) { setAttributes({ ajnSingleButtonCustomWidthMobile: value }); }) : null
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
