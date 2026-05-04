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
    var SelectControl = wp.components.SelectControl;
    var ToggleControl = wp.components.ToggleControl;
    var createHigherOrderComponent = wp.compose.createHigherOrderComponent;

    var SIZE_ATTRS = {
        ncllcWidth: {
            type: 'string',
            default: ''
        },
        ncllcMaxWidth: {
            type: 'string',
            default: ''
        },
        ncllcMinHeight: {
            type: 'string',
            default: ''
        },
        ncllcHeight: {
            type: 'string',
            default: ''
        },
        ncllcOverflow: {
            type: 'string',
            default: ''
        },
        ncllcObjectFit: {
            type: 'string',
            default: ''
        },
        ncllcCenterBlock: {
            type: 'boolean',
            default: false
        }
    };

    function hasSizeAttrs(attrs) {
        return !!(
            attrs.ncllcWidth ||
            attrs.ncllcMaxWidth ||
            attrs.ncllcMinHeight ||
            attrs.ncllcHeight ||
            attrs.ncllcOverflow ||
            attrs.ncllcObjectFit ||
            attrs.ncllcCenterBlock
        );
    }

    function getSizeStyle(attrs) {
        var style = {};

        if (attrs.ncllcWidth) {
            style.width = attrs.ncllcWidth;
        }
        if (attrs.ncllcMaxWidth) {
            style.maxWidth = attrs.ncllcMaxWidth;
        }
        if (attrs.ncllcMinHeight) {
            style.minHeight = attrs.ncllcMinHeight;
        }
        if (attrs.ncllcHeight) {
            style.height = attrs.ncllcHeight;
        }
        if (attrs.ncllcOverflow) {
            style.overflow = attrs.ncllcOverflow;
        }
        if (attrs.ncllcCenterBlock) {
            style.marginLeft = 'auto';
            style.marginRight = 'auto';
        }

        return style;
    }

    function mergeStyle(existingStyle, addedStyle) {
        return Object.assign({}, existingStyle || {}, addedStyle);
    }

    addFilter('blocks.registerBlockType', 'ncllc-pro/size-attributes', function(settings) {
        settings.attributes = Object.assign({}, settings.attributes || {}, SIZE_ATTRS);
        return settings;
    });

    addFilter(
        'editor.BlockEdit',
        'ncllc-pro/size-controls',
        createHigherOrderComponent(function(BlockEdit) {
            return function(props) {
                var attrs = props.attributes || {};
                var setAttributes = props.setAttributes;

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
                                title: 'NCLLC Size',
                                initialOpen: hasSizeAttrs(attrs)
                            },
                            createElement(TextControl, {
                                label: 'Width',
                                help: 'Examples: 50%, 420px, 30rem, 80vw.',
                                value: attrs.ncllcWidth || '',
                                onChange: function(value) {
                                    setAttributes({ ncllcWidth: value });
                                }
                            }),
                            createElement(TextControl, {
                                label: 'Max width',
                                help: 'Examples: 720px, 950px, 1120px.',
                                value: attrs.ncllcMaxWidth || '',
                                onChange: function(value) {
                                    setAttributes({ ncllcMaxWidth: value });
                                }
                            }),
                            createElement(TextControl, {
                                label: 'Minimum height',
                                help: 'Examples: 320px, 70vh, 24rem.',
                                value: attrs.ncllcMinHeight || '',
                                onChange: function(value) {
                                    setAttributes({ ncllcMinHeight: value });
                                }
                            }),
                            createElement(TextControl, {
                                label: 'Fixed height',
                                help: 'Use only when the block must be an exact height.',
                                value: attrs.ncllcHeight || '',
                                onChange: function(value) {
                                    setAttributes({ ncllcHeight: value });
                                }
                            }),
                            createElement(ToggleControl, {
                                label: 'Center block',
                                checked: !!attrs.ncllcCenterBlock,
                                onChange: function(value) {
                                    setAttributes({ ncllcCenterBlock: value });
                                }
                            }),
                            createElement(SelectControl, {
                                label: 'Overflow',
                                value: attrs.ncllcOverflow || '',
                                options: [
                                    { label: 'Default', value: '' },
                                    { label: 'Visible', value: 'visible' },
                                    { label: 'Hidden', value: 'hidden' },
                                    { label: 'Auto', value: 'auto' },
                                    { label: 'Scroll', value: 'scroll' }
                                ],
                                onChange: function(value) {
                                    setAttributes({ ncllcOverflow: value });
                                }
                            }),
                            createElement(SelectControl, {
                                label: 'Image fit',
                                help: 'Applies to Image blocks and blocks containing images.',
                                value: attrs.ncllcObjectFit || '',
                                options: [
                                    { label: 'Default', value: '' },
                                    { label: 'Cover', value: 'cover' },
                                    { label: 'Contain', value: 'contain' },
                                    { label: 'Fill', value: 'fill' }
                                ],
                                onChange: function(value) {
                                    setAttributes({ ncllcObjectFit: value });
                                }
                            })
                        )
                    )
                );
            };
        }, 'withNcllcSizeControls')
    );

    addFilter(
        'editor.BlockListBlock',
        'ncllc-pro/editor-size-preview',
        createHigherOrderComponent(function(BlockListBlock) {
            return function(props) {
                var attrs = props.attributes || {};
                var wrapperProps = props.wrapperProps || {};
                var sizeStyle = getSizeStyle(attrs);

                return createElement(
                    BlockListBlock,
                    Object.assign({}, props, {
                        wrapperProps: Object.assign({}, wrapperProps, {
                            style: mergeStyle(wrapperProps.style, sizeStyle)
                        })
                    })
                );
            };
        }, 'withNcllcEditorSizePreview')
    );

    addFilter('blocks.getSaveContent.extraProps', 'ncllc-pro/save-size-props', function(extraProps, blockType, attrs) {
        if (!hasSizeAttrs(attrs || {})) {
            return extraProps;
        }

        var classNames = extraProps.className ? extraProps.className.split(' ') : [];
        var style = getSizeStyle(attrs);

        if (attrs.ncllcObjectFit) {
            classNames.push('ncllc-fit-' + attrs.ncllcObjectFit);
        }

        extraProps.style = mergeStyle(extraProps.style, style);
        extraProps.className = classNames.filter(Boolean).join(' ');

        return extraProps;
    });
})(window.wp);
