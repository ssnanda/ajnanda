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
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
    var useEffect = wp.element.useEffect;
    var useState = wp.element.useState;

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
                className: 'builder-hero-section hero-height-standard hero-width-standard',
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

    addFilter('blocks.registerBlockType', 'ajn/block-layout-attributes', function(settings, name) {
        if (!hasLayoutControls(name || settings.name)) {
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
                    return createElement(BlockEdit, props);
                }

                var attrs = props.attributes || {};
                var setAttributes = props.setAttributes;
                var measuredHeight = useMeasuredBlockHeight(props.clientId);

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

                return createElement(BlockListBlock, Object.assign({}, props, { wrapperProps: wrapperProps }));
            };
        }, 'withAjnLiveBlockLayoutPreview')
    );

    addFilter('blocks.getSaveContent.extraProps', 'ajn/save-block-layout-props', function(extraProps, blockType, attrs) {
        attrs = attrs || {};

        if (!hasLayoutControls(blockType.name) || !hasLayout(attrs)) {
            return extraProps;
        }

        extraProps.className = getLayoutClass(attrs, extraProps.className);
        extraProps.style = Object.assign({}, extraProps.style || {}, getLayoutStyles(attrs));

        return extraProps;
    });

})(window.wp);
