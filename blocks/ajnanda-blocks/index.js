(function(wp) {
    if (!wp || !wp.blocks || !wp.element || !wp.blockEditor || !wp.components) {
        return;
    }

    var registerBlockType = wp.blocks.registerBlockType;
    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var __ = wp.i18n.__;
    var createBlock = wp.blocks.createBlock;
    var dispatch = wp.data && wp.data.dispatch;
    var useSelect = wp.data && wp.data.useSelect;
    var useEffect = wp.element.useEffect;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var InnerBlocks = wp.blockEditor.InnerBlocks;
    var MediaUpload = wp.blockEditor.MediaUpload;
    var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
    var RichText = wp.blockEditor.RichText;
    var URLInputButton = wp.blockEditor.URLInputButton;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var TextareaControl = wp.components.TextareaControl;
    var ToggleControl = wp.components.ToggleControl;
    var RangeControl = wp.components.RangeControl;
    var SelectControl = wp.components.SelectControl;
    var Button = wp.components.Button;
    var ButtonGroup = wp.components.ButtonGroup;
    var DropdownMenu = wp.components.DropdownMenu;
    var ServerSideRender = wp.serverSideRender;
    var category = 'ajnanda-blocks';

    function el(tag, props) {
        var children = Array.prototype.slice.call(arguments, 2);
        return createElement.apply(null, [tag, props || {}].concat(children));
    }

    function inspector(children) {
        return el(InspectorControls, {}, el(PanelBody, { title: __('AJNanda Settings', 'ajnanda'), initialOpen: true }, children));
    }

    function classNames() {
        return Array.prototype.slice.call(arguments).filter(Boolean).join(' ');
    }

    function field(label, value, onChange, placeholder) {
        return el(TextControl, { label: label, value: value || '', placeholder: placeholder || '', onChange: onChange });
    }

    function colorField(label, value, onChange, fallback) {
        var color = value || fallback || '#000000';

        return el('div', { className: 'aj-color-control' },
            el('span', { className: 'aj-control-label' }, label),
            el('div', { className: 'aj-color-control__inputs' },
                el('input', {
                    type: 'color',
                    value: /^#[0-9a-f]{6}$/i.test(color) ? color : fallback || '#000000',
                    onChange: function(event) { onChange(event.target.value); }
                }),
                el(TextControl, {
                    value: value || '',
                    placeholder: fallback || '#000000',
                    onChange: onChange
                })
            )
        );
    }

    function urlField(value, onChange) {
        return el('div', { className: 'aj-url-control' }, el('span', {}, __('Link', 'ajnanda')), el(URLInputButton, { url: value || '', onChange: onChange }));
    }

    function withStyleAttributes(attrs) {
        return Object.assign({
            alignText: { type: 'string', default: '' },
            backgroundColor: { type: 'string', default: '' },
            textColor: { type: 'string', default: '' },
            borderColor: { type: 'string', default: '' },
            borderStyle: { type: 'string', default: 'none' },
            borderWidth: { type: 'number', default: 0 },
            borderRadius: { type: 'number', default: 0 },
            padding: { type: 'number', default: 0 },
            marginTop: { type: 'number', default: 0 },
            marginBottom: { type: 'number', default: 0 },
            fontFamily: { type: 'string', default: '' },
            fontSize: { type: 'string', default: '' },
            fontWeight: { type: 'string', default: '' },
            fontStyle: { type: 'string', default: '' },
            textDecoration: { type: 'string', default: '' },
            headingScheme: { type: 'string', default: '' },
            animation: { type: 'string', default: 'none' }
        }, attrs || {});
    }

    function blockStyle(attrs) {
        attrs = attrs || {};
        var style = {};

        if (attrs.backgroundColor) {
            style.backgroundColor = attrs.backgroundColor;
        }
        if (attrs.textColor) {
            style.color = attrs.textColor;
        }
        if (attrs.borderColor) {
            style.borderColor = attrs.borderColor;
        }
        if (attrs.borderStyle && attrs.borderStyle !== 'none') {
            style.borderStyle = attrs.borderStyle;
            style.borderWidth = ('number' === typeof attrs.borderWidth ? attrs.borderWidth : 1) + 'px';
        } else if (attrs.borderColor) {
            style.borderStyle = 'solid';
            style.borderWidth = ('number' === typeof attrs.borderWidth && attrs.borderWidth > 0 ? attrs.borderWidth : 1) + 'px';
        }
        if (attrs.borderRadius) {
            style.borderRadius = attrs.borderRadius + 'px';
        }
        if (attrs.fontFamily) {
            style.fontFamily = attrs.fontFamily;
        }
        if (attrs.fontSize) {
            style.fontSize = attrs.fontSize;
        }
        if (attrs.fontWeight) {
            style.fontWeight = attrs.fontWeight;
        }
        if (attrs.fontStyle) {
            style.fontStyle = attrs.fontStyle;
        }
        if (attrs.textDecoration) {
            style.textDecoration = attrs.textDecoration;
        }
        if (attrs.padding) {
            style.padding = attrs.padding + 'px';
        }
        if (attrs.marginTop) {
            style.marginTop = attrs.marginTop + 'px';
        }
        if (attrs.marginBottom) {
            style.marginBottom = attrs.marginBottom + 'px';
        }
        if (attrs.alignText) {
            style.textAlign = attrs.alignText;
        }
        if (attrs.gap) {
            style.gap = attrs.gap + 'px';
        }
        if (attrs.columns) {
            style['--aj-columns'] = attrs.columns;
        }
        if (attrs.width) {
            style.width = attrs.width + '%';
        }
        if (attrs.thickness) {
            style.height = attrs.thickness + 'px';
        }
        if (attrs.maxWidth) {
            style.maxWidth = attrs.maxWidth + 'px';
        }
        if (attrs.minHeight) {
            style.minHeight = attrs.minHeight + 'px';
        }
        if (attrs.aspectRatio) {
            style.aspectRatio = attrs.aspectRatio;
        }
        if (attrs.layoutMode === 'flex') {
            style.display = 'flex';
            style.flexDirection = attrs.direction || 'row';
            style.flexWrap = attrs.wrapMode || 'wrap';
            style.alignItems = attrs.alignItems || 'stretch';
            style.justifyContent = attrs.justify || 'center';
        }
        if (attrs.layoutMode === 'grid') {
            style.display = 'grid';
            style.gridTemplateColumns = 'repeat(' + (attrs.columns || 2) + ', minmax(0, 1fr))';
            style.gridTemplateRows = attrs.gridRows && attrs.gridRows > 1 ? 'repeat(' + attrs.gridRows + ', auto)' : undefined;
            style.alignItems = attrs.alignItems || 'stretch';
            style.justifyItems = attrs.justify || 'stretch';
            style.alignContent = attrs.alignContent || 'stretch';
        }

        return style;
    }

    function styledProps(baseClass, attrs, extraClass) {
        return {
            className: classNames('aj-block', baseClass, extraClass, attrs && attrs.alignText ? 'has-text-align-' + attrs.alignText : '', attrs && attrs.animation && attrs.animation !== 'none' ? 'aj-animate-' + attrs.animation : ''),
            style: blockStyle(attrs)
        };
    }

    function commonControls(props) {
        var attrs = props.attributes || {};

        return [
            el(SelectControl, {
                label: __('Alignment', 'ajnanda'),
                value: attrs.alignText || '',
                options: [
                    { label: __('Default', 'ajnanda'), value: '' },
                    { label: __('Left', 'ajnanda'), value: 'left' },
                    { label: __('Center', 'ajnanda'), value: 'center' },
                    { label: __('Right', 'ajnanda'), value: 'right' }
                ],
                onChange: function(value) { props.setAttributes({ alignText: value }); }
            }),
            field(__('Background color', 'ajnanda'), attrs.backgroundColor, function(value) { props.setAttributes({ backgroundColor: value }); }, '#ffffff'),
            field(__('Text color', 'ajnanda'), attrs.textColor, function(value) { props.setAttributes({ textColor: value }); }, '#111827'),
            field(__('Border color', 'ajnanda'), attrs.borderColor, function(value) { props.setAttributes({ borderColor: value }); }, '#e5e7eb'),
            el(SelectControl, {
                label: __('Border style', 'ajnanda'),
                value: attrs.borderStyle || 'none',
                options: [
                    { label: __('None', 'ajnanda'), value: 'none' },
                    { label: __('Solid', 'ajnanda'), value: 'solid' },
                    { label: __('Dotted', 'ajnanda'), value: 'dotted' },
                    { label: __('Dashed', 'ajnanda'), value: 'dashed' },
                    { label: __('Double', 'ajnanda'), value: 'double' }
                ],
                onChange: function(value) { props.setAttributes({ borderStyle: value }); }
            }),
            el(RangeControl, { label: __('Border width', 'ajnanda'), min: 0, max: 12, value: attrs.borderWidth || 0, onChange: function(value) { props.setAttributes({ borderWidth: value }); } }),
            el(RangeControl, { label: __('Border radius', 'ajnanda'), min: 0, max: 80, value: attrs.borderRadius || 0, onChange: function(value) { props.setAttributes({ borderRadius: value }); } }),
            el(RangeControl, { label: __('Padding', 'ajnanda'), min: 0, max: 120, value: attrs.padding || 0, onChange: function(value) { props.setAttributes({ padding: value }); } }),
            el(RangeControl, { label: __('Margin top', 'ajnanda'), min: 0, max: 160, value: attrs.marginTop || 0, onChange: function(value) { props.setAttributes({ marginTop: value }); } }),
            el(RangeControl, { label: __('Margin bottom', 'ajnanda'), min: 0, max: 160, value: attrs.marginBottom || 0, onChange: function(value) { props.setAttributes({ marginBottom: value }); } }),
            el(SelectControl, {
                label: __('Animation', 'ajnanda'),
                value: attrs.animation || 'none',
                options: [
                    { label: __('None', 'ajnanda'), value: 'none' },
                    { label: __('Fade In', 'ajnanda'), value: 'fade-in' },
                    { label: __('Slide Up', 'ajnanda'), value: 'slide-up' },
                    { label: __('Slide Down', 'ajnanda'), value: 'slide-down' },
                    { label: __('Slide Left', 'ajnanda'), value: 'slide-left' },
                    { label: __('Slide Right', 'ajnanda'), value: 'slide-right' },
                    { label: __('Zoom In', 'ajnanda'), value: 'zoom-in' },
                    { label: __('Zoom Out', 'ajnanda'), value: 'zoom-out' },
                    { label: __('Pop', 'ajnanda'), value: 'pop' },
                    { label: __('Blur In', 'ajnanda'), value: 'blur-in' },
                    { label: __('Rotate In', 'ajnanda'), value: 'rotate-in' },
                    { label: __('Flip In', 'ajnanda'), value: 'flip-in' }
                ],
                onChange: function(value) { props.setAttributes({ animation: value }); }
            })
        ];
    }

    function controlsWithCommon(props, controls) {
        controls = controls ? (Array.isArray(controls) ? controls : [controls]) : [];
        return controls.concat(commonControls(props));
    }

    function headingControls(props) {
        var attrs = props.attributes || {};
        var level = attrs.level || 2;
        var headingSchemes = {
            clean: {
                textColor: '#111827',
                backgroundColor: '',
                borderColor: '',
                borderStyle: 'none',
                borderRadius: 0,
                fontFamily: 'Inter',
                fontWeight: '700',
                fontStyle: '',
                textDecoration: '',
                animation: 'none'
            },
            hero: {
                textColor: '#ffffff',
                backgroundColor: '#2563eb',
                borderColor: '#1d4ed8',
                borderStyle: 'solid',
                borderRadius: 16,
                fontFamily: 'Poppins',
                fontWeight: '700',
                fontStyle: '',
                textDecoration: '',
                animation: 'slide-up'
            },
            dark: {
                textColor: '#f8fafc',
                backgroundColor: '#0f172a',
                borderColor: '#334155',
                borderStyle: 'solid',
                borderRadius: 12,
                fontFamily: 'Inter',
                fontWeight: '700',
                fontStyle: '',
                textDecoration: '',
                animation: 'fade-in'
            },
            outlined: {
                textColor: '#1d4ed8',
                backgroundColor: '#eff6ff',
                borderColor: '#2563eb',
                borderStyle: 'solid',
                borderRadius: 8,
                fontFamily: 'Inter',
                fontWeight: '700',
                fontStyle: '',
                textDecoration: '',
                animation: 'zoom-in'
            },
            dotted: {
                textColor: '#581c87',
                backgroundColor: '#faf5ff',
                borderColor: '#a855f7',
                borderStyle: 'dotted',
                borderRadius: 16,
                fontFamily: 'Poppins',
                fontWeight: '700',
                fontStyle: '',
                textDecoration: '',
                animation: 'pop'
            },
            editorial: {
                textColor: '#111827',
                backgroundColor: '#fffbeb',
                borderColor: '#f59e0b',
                borderStyle: 'solid',
                borderRadius: 0,
                fontFamily: 'Georgia',
                fontWeight: '700',
                fontStyle: 'italic',
                textDecoration: '',
                animation: 'blur-in'
            },
            underline: {
                textColor: '#0f172a',
                backgroundColor: '',
                borderColor: '#2563eb',
                borderStyle: 'solid',
                borderRadius: 0,
                fontFamily: 'Inter',
                fontWeight: '700',
                fontStyle: '',
                textDecoration: 'underline',
                animation: 'slide-right'
            }
        };

        function setBorderShape(value) {
            props.setAttributes({ borderRadius: parseInt(value, 10) || 0 });
        }

        function applyHeadingScheme(value) {
            if (!value || !headingSchemes[value]) {
                return;
            }

            props.setAttributes(Object.assign({ headingScheme: value }, headingSchemes[value]));
        }

        return [
            el(SelectControl, {
                label: __('Heading scheme', 'ajnanda'),
                value: attrs.headingScheme || '',
                options: [
                    { label: __('Choose a prebuilt scheme...', 'ajnanda'), value: '' },
                    { label: __('Clean', 'ajnanda'), value: 'clean' },
                    { label: __('Hero Banner', 'ajnanda'), value: 'hero' },
                    { label: __('Dark Header', 'ajnanda'), value: 'dark' },
                    { label: __('Blue Outline', 'ajnanda'), value: 'outlined' },
                    { label: __('Purple Dotted', 'ajnanda'), value: 'dotted' },
                    { label: __('Editorial Serif', 'ajnanda'), value: 'editorial' },
                    { label: __('Underlined Accent', 'ajnanda'), value: 'underline' }
                ],
                onChange: applyHeadingScheme
            }),
            el(RangeControl, { label: __('Level', 'ajnanda'), min: 1, max: 6, value: level, onChange: function(value) { props.setAttributes({ level: value }); } }),
            el(SelectControl, {
                label: __('Alignment', 'ajnanda'),
                value: attrs.alignText || '',
                options: [
                    { label: __('Default', 'ajnanda'), value: '' },
                    { label: __('Left', 'ajnanda'), value: 'left' },
                    { label: __('Center', 'ajnanda'), value: 'center' },
                    { label: __('Right', 'ajnanda'), value: 'right' }
                ],
                onChange: function(value) { props.setAttributes({ alignText: value }); }
            }),
            el('div', { className: 'aj-heading-font-row' },
                el('span', { className: 'aj-control-label' }, __('Font', 'ajnanda')),
                el(DropdownMenu, {
                    className: 'aj-heading-font-dropdown',
                    icon: 'editor-textcolor',
                    label: __('Font', 'ajnanda'),
                    text: __('Font', 'ajnanda'),
                    popoverProps: { placement: 'bottom-start' },
                    controls: [
                        {
                            title: __('Inter', 'ajnanda'),
                            onClick: function() { props.setAttributes({ fontFamily: 'Inter' }); }
                        },
                        {
                            title: __('Poppins', 'ajnanda'),
                            onClick: function() { props.setAttributes({ fontFamily: 'Poppins' }); }
                        },
                        {
                            title: __('Arial', 'ajnanda'),
                            onClick: function() { props.setAttributes({ fontFamily: 'Arial' }); }
                        },
                        {
                            title: __('Georgia', 'ajnanda'),
                            onClick: function() { props.setAttributes({ fontFamily: 'Georgia' }); }
                        },
                        {
                            title: __('System UI', 'ajnanda'),
                            onClick: function() { props.setAttributes({ fontFamily: 'system-ui' }); }
                        },
                        {
                            title: __('Bold', 'ajnanda'),
                            onClick: function() { props.setAttributes({ fontWeight: attrs.fontWeight === '700' ? '' : '700' }); }
                        },
                        {
                            title: __('Italic', 'ajnanda'),
                            onClick: function() { props.setAttributes({ fontStyle: attrs.fontStyle === 'italic' ? '' : 'italic' }); }
                        },
                        {
                            title: __('Underline', 'ajnanda'),
                            onClick: function() { props.setAttributes({ textDecoration: attrs.textDecoration === 'underline' ? '' : 'underline' }); }
                        },
                        {
                            title: __('Reset font', 'ajnanda'),
                            onClick: function() { props.setAttributes({ fontFamily: '', fontSize: '', fontWeight: '', fontStyle: '', textDecoration: '' }); }
                        }
                    ]
                })
            ),
            field(__('Font size', 'ajnanda'), attrs.fontSize, function(value) { props.setAttributes({ fontSize: value }); }, '2rem'),
            colorField(__('Font color', 'ajnanda'), attrs.textColor, function(value) { props.setAttributes({ textColor: value }); }, '#111827'),
            colorField(__('Background color', 'ajnanda'), attrs.backgroundColor, function(value) { props.setAttributes({ backgroundColor: value }); }, '#ffffff'),
            colorField(__('Border color', 'ajnanda'), attrs.borderColor, function(value) { props.setAttributes({ borderColor: value }); }, '#e5e7eb'),
            el(SelectControl, {
                label: __('Border shape', 'ajnanda'),
                value: String(attrs.borderRadius || 0),
                options: [
                    { label: __('Rectangle', 'ajnanda'), value: '0' },
                    { label: __('Slightly rounded', 'ajnanda'), value: '6' },
                    { label: __('Curved edges', 'ajnanda'), value: '16' },
                    { label: __('Pill', 'ajnanda'), value: '999' }
                ],
                onChange: setBorderShape
            }),
            el(SelectControl, {
                label: __('Border line', 'ajnanda'),
                value: attrs.borderStyle || 'none',
                options: [
                    { label: __('None', 'ajnanda'), value: 'none' },
                    { label: __('Solid line', 'ajnanda'), value: 'solid' },
                    { label: __('Dotted line', 'ajnanda'), value: 'dotted' },
                    { label: __('Dashed line', 'ajnanda'), value: 'dashed' },
                    { label: __('Double line', 'ajnanda'), value: 'double' }
                ],
                onChange: function(value) { props.setAttributes({ borderStyle: value }); }
            }),
            el(RangeControl, { label: __('Border width', 'ajnanda'), min: 0, max: 12, value: attrs.borderWidth || 0, onChange: function(value) { props.setAttributes({ borderWidth: value }); } }),
            el(RangeControl, { label: __('Padding', 'ajnanda'), min: 0, max: 120, value: attrs.padding || 0, onChange: function(value) { props.setAttributes({ padding: value }); } }),
            el(RangeControl, { label: __('Margin top', 'ajnanda'), min: 0, max: 160, value: attrs.marginTop || 0, onChange: function(value) { props.setAttributes({ marginTop: value }); } }),
            el(RangeControl, { label: __('Margin bottom', 'ajnanda'), min: 0, max: 160, value: attrs.marginBottom || 0, onChange: function(value) { props.setAttributes({ marginBottom: value }); } }),
            el(SelectControl, {
                label: __('Animation', 'ajnanda'),
                value: attrs.animation || 'none',
                options: [
                    { label: __('None', 'ajnanda'), value: 'none' },
                    { label: __('Fade In', 'ajnanda'), value: 'fade-in' },
                    { label: __('Slide Up', 'ajnanda'), value: 'slide-up' },
                    { label: __('Slide Down', 'ajnanda'), value: 'slide-down' },
                    { label: __('Slide Left', 'ajnanda'), value: 'slide-left' },
                    { label: __('Slide Right', 'ajnanda'), value: 'slide-right' },
                    { label: __('Zoom In', 'ajnanda'), value: 'zoom-in' },
                    { label: __('Zoom Out', 'ajnanda'), value: 'zoom-out' },
                    { label: __('Pop', 'ajnanda'), value: 'pop' },
                    { label: __('Blur In', 'ajnanda'), value: 'blur-in' },
                    { label: __('Rotate In', 'ajnanda'), value: 'rotate-in' },
                    { label: __('Flip In', 'ajnanda'), value: 'flip-in' }
                ],
                onChange: function(value) { props.setAttributes({ animation: value }); }
            })
        ];
    }

    function extraControls(props, options) {
        return options && options.controls ? options.controls(props) : [];
    }

    function extraClass(attrs, options) {
        return options && options.className ? options.className(attrs || {}) : '';
    }

    function registerContainerBlock(name, title, description, className, template, options) {
        options = options || {};
        registerBlockType(name, {
            title: title,
            description: description,
            category: category,
            icon: 'screenoptions',
            supports: { align: ['wide', 'full'], anchor: true },
            attributes: withStyleAttributes(Object.assign({ className: { type: 'string' } }, options.attributes || {})),
            __experimentalLabel: options.label || undefined,
            edit: function(props) {
                var blockContext = useSelect ? useSelect(function(select) {
                    var block = select('core/block-editor').getBlock(props.clientId);
                    var parentId = select('core/block-editor').getBlockRootClientId(props.clientId);
                    var parentBlock = parentId ? select('core/block-editor').getBlock(parentId) : null;
                    var grandParentId = parentId ? select('core/block-editor').getBlockRootClientId(parentId) : null;
                    var grandParentBlock = grandParentId ? select('core/block-editor').getBlock(grandParentId) : null;
                    var index = select('core/block-editor').getBlockIndex(props.clientId, parentId || undefined);
                    var parentIndex = grandParentId ? select('core/block-editor').getBlockIndex(parentId, grandParentId) : 0;

                    return {
                        block: block,
                        hasChildBlocks: !!(block && block.innerBlocks && block.innerBlocks.length),
                        innerCount: block && block.innerBlocks ? block.innerBlocks.length : 0,
                        parentId: parentId,
                        parentBlock: parentBlock,
                        grandParentId: grandParentId,
                        grandParentBlock: grandParentBlock,
                        index: index,
                        parentIndex: parentIndex
                    };
                }, [props.clientId]) : false;
                var shouldChooseLayout = name === 'ajnanda/container' && !props.attributes.layoutSelected && !(blockContext && blockContext.hasChildBlocks);
                var insertionControls = name === 'ajnanda/container' && !shouldChooseLayout && props.isSelected ? containerInsertionControls(props, blockContext) : null;
                var innerBlocksProps = { template: template || [], templateLock: false };

                if (name === 'ajnanda/container' && blockContext && isContainerRow(blockContext.block)) {
                    innerBlocksProps.renderAppender = false;
                }

                return el(Fragment, {},
                    inspector(controlsWithCommon(props, extraControls(props, options))),
                    el('div', styledProps(className, props.attributes, classNames(props.attributes.className, extraClass(props.attributes, options))),
                        insertionControls,
                        shouldChooseLayout ? containerLayoutChooser(props) : el(InnerBlocks, innerBlocksProps)
                    )
                );
            },
            save: function(props) {
                return el('div', styledProps(className, props.attributes, classNames(props.attributes.className, extraClass(props.attributes, options))), el(InnerBlocks.Content));
            }
        });
    }

    function layoutControls(props) {
        var attrs = props.attributes;

        return [
            el(SelectControl, {
                label: __('Direction', 'ajnanda'),
                value: attrs.direction || 'row',
                options: [
                    { label: __('Row', 'ajnanda'), value: 'row' },
                    { label: __('Column', 'ajnanda'), value: 'column' },
                    { label: __('Row Reverse', 'ajnanda'), value: 'row-reverse' },
                    { label: __('Column Reverse', 'ajnanda'), value: 'column-reverse' }
                ],
                onChange: function(value) { props.setAttributes({ direction: value }); }
            }),
            el(SelectControl, {
                label: __('Justify content', 'ajnanda'),
                value: attrs.justify || 'flex-start',
                options: [
                    { label: __('Start', 'ajnanda'), value: 'flex-start' },
                    { label: __('Center', 'ajnanda'), value: 'center' },
                    { label: __('End', 'ajnanda'), value: 'flex-end' },
                    { label: __('Space Between', 'ajnanda'), value: 'space-between' }
                ],
                onChange: function(value) { props.setAttributes({ justify: value }); }
            }),
            el(SelectControl, {
                label: __('Align items', 'ajnanda'),
                value: attrs.alignItems || 'stretch',
                options: [
                    { label: __('Stretch', 'ajnanda'), value: 'stretch' },
                    { label: __('Start', 'ajnanda'), value: 'flex-start' },
                    { label: __('Center', 'ajnanda'), value: 'center' },
                    { label: __('End', 'ajnanda'), value: 'flex-end' }
                ],
                onChange: function(value) { props.setAttributes({ alignItems: value }); }
            }),
            el(ToggleControl, { label: __('Allow wrap', 'ajnanda'), checked: attrs.wrap !== false, onChange: function(value) { props.setAttributes({ wrap: value }); } }),
            el(RangeControl, { label: __('Gap', 'ajnanda'), min: 0, max: 80, value: attrs.gap || 16, onChange: function(value) { props.setAttributes({ gap: value }); } })
        ];
    }

    function gridControls(props) {
        var attrs = props.attributes;

        return [
            el(RangeControl, { label: __('Columns', 'ajnanda'), min: 1, max: 6, value: attrs.columns || 3, onChange: function(value) { props.setAttributes({ columns: value }); } }),
            el(RangeControl, { label: __('Gap', 'ajnanda'), min: 0, max: 80, value: attrs.gap || 20, onChange: function(value) { props.setAttributes({ gap: value }); } })
        ];
    }

    function galleryControls(props) {
        var attrs = props.attributes;

        return gridControls(props).concat([
            el(ToggleControl, {
                label: __('Enable lightbox on click', 'ajnanda'),
                help: __('Applies to every photo in this gallery.', 'ajnanda'),
                checked: attrs.lightbox !== false,
                onChange: function(value) { props.setAttributes({ lightbox: value }); }
            })
        ]);
    }

    function collectNestedImageBlocks(block, found) {
        found = found || [];
        if (!block || !block.innerBlocks) {
            return found;
        }
        for (var i = 0; i < block.innerBlocks.length; i++) {
            var inner = block.innerBlocks[i];
            if (inner.name === 'core/image') {
                found.push(inner);
            }
            collectNestedImageBlocks(inner, found);
        }
        return found;
    }

    function galleryWrapperStyle(attrs) {
        var style = blockStyle(attrs);
        style['--wp--style--unstable-gallery-gap'] = (attrs.gap || 20) + 'px';
        return style;
    }

    function galleryStyledProps(className, attrs, extraClass) {
        var props = styledProps(className, attrs, extraClass);
        props.style = galleryWrapperStyle(attrs);
        return props;
    }

    // The actual photo grid comes from the nested core/gallery block, so the
    // Columns control here only has visible effect once it is pushed onto
    // that inner block's own `columns` attribute (WordPress renders columns
    // as a literal `columns-N` class, not something a wrapper style can set).
    // Gap can be set purely via CSS variable inheritance, since core/gallery's
    // own stylesheet reads --wp--style--unstable-gallery-gap.
    function registerGalleryBlock(name, title, description) {
        registerBlockType(name, {
            title: title,
            description: description,
            category: category,
            icon: 'format-gallery',
            supports: { align: ['wide', 'full'], anchor: true },
            attributes: withStyleAttributes({
                className: { type: 'string' },
                columns: { type: 'number', default: 3 },
                gap: { type: 'number', default: 20 },
                lightbox: { type: 'boolean', default: true }
            }),
            edit: function(props) {
                var attrs = props.attributes;
                var clientId = props.clientId;
                var innerGallery = useSelect ? useSelect(function(select) {
                    var block = select('core/block-editor').getBlock(clientId);
                    var innerBlocks = block && block.innerBlocks ? block.innerBlocks : [];
                    for (var i = 0; i < innerBlocks.length; i++) {
                        if (innerBlocks[i].name === 'core/gallery') {
                            return innerBlocks[i];
                        }
                    }
                    return null;
                }, [clientId]) : null;

                if (useEffect) {
                    useEffect(function() {
                        if (!innerGallery || !dispatch) {
                            return;
                        }
                        var columns = attrs.columns || 3;
                        if (innerGallery.attributes.columns !== columns) {
                            dispatch('core/block-editor').updateBlockAttributes(innerGallery.clientId, { columns: columns });
                        }

                        var wantLightbox = attrs.lightbox !== false;
                        var images = collectNestedImageBlocks(innerGallery);
                        for (var i = 0; i < images.length; i++) {
                            var current = images[i].attributes.lightbox;
                            var currentEnabled = !!(current && current.enabled);
                            if (currentEnabled !== wantLightbox) {
                                dispatch('core/block-editor').updateBlockAttributes(images[i].clientId, { lightbox: { enabled: wantLightbox } });
                            }
                        }
                    }, [innerGallery, attrs.columns, attrs.lightbox]);
                }

                return el(Fragment, {},
                    inspector(controlsWithCommon(props, galleryControls(props))),
                    el('div', galleryStyledProps('aj-gallery', attrs, attrs.className),
                        el(InnerBlocks, {
                            template: [['core/gallery', { columns: attrs.columns || 3 }]],
                            templateLock: false,
                            allowedBlocks: ['core/gallery']
                        })
                    )
                );
            },
            save: function(props) {
                var attrs = props.attributes;
                return el('div', galleryStyledProps('aj-gallery', attrs, classNames(attrs.className)), el(InnerBlocks.Content));
            }
        });
    }

    function segmentedOptionContent(option) {
        return option.icon ? el('span', { className: 'aj-control-icon', title: option.label }, option.icon) : option.label;
    }

    function segmented(label, value, options, onChange, help) {
        return el('div', { className: 'aj-segmented-control' },
            el('span', { className: 'aj-control-label' }, label),
            el(ButtonGroup, { className: 'aj-segmented-control__buttons' }, options.map(function(option) {
                return el(Button, {
                    key: option.value,
                    label: option.label,
                    showTooltip: !!option.icon,
                    variant: value === option.value ? 'primary' : 'secondary',
                    onClick: function() { onChange(option.value); }
                }, segmentedOptionContent(option));
            })),
            help ? el('p', { className: 'aj-control-help' }, help) : null
        );
    }

    function containerPreviewColumns(pattern) {
        return el('span', { className: 'aj-container-layout-preview aj-container-layout-preview--' + pattern },
            el('i', {}), el('i', {}), el('i', {}), el('i', {})
        );
    }

    function containerChild(label, attrs, innerBlocks) {
        if (!createBlock) {
            return null;
        }

        return createBlock('ajnanda/container', Object.assign({
            label: label,
            layoutSelected: true,
            layoutMode: 'flex',
            direction: 'column',
            childrenWidth: 'auto',
            alignItems: 'stretch',
            justify: 'flex-start',
            wrapMode: 'wrap',
            maxWidth: 1100,
            gap: 12
        }, attrs || {}), innerBlocks || [
            createBlock('core/paragraph', { placeholder: label || __('Add content', 'ajnanda') })
        ]);
    }

    function containerFooterBlock() {
        return createBlock('core/buttons', { layout: { type: 'flex', justifyContent: 'center' } }, [
            createBlock('core/button', { text: __('Button', 'ajnanda') })
        ]);
    }

    function containerBlankBlock() {
        return containerChild(__('Container', 'ajnanda'), { label: __('Container', 'ajnanda') });
    }

    function containerHeadingBlock() {
        return containerChild(__('Heading Container', 'ajnanda'), { label: __('Heading Container', 'ajnanda'), alignItems: 'center', containerType: 'header' }, [
            createBlock('core/heading', { level: 2, content: __('Section heading', 'ajnanda'), textAlign: 'center' }),
            createBlock('core/paragraph', { placeholder: __('Add supporting text.', 'ajnanda'), align: 'center' })
        ]);
    }

    function containerBlockForInsert(type) {
        switch (type) {
            case 'heading':
                return containerHeadingBlock();
            case 'row-two':
                return containerColumns(__('Tile Container', 'ajnanda'), 2);
            case 'row-three':
                return containerColumns(__('Tile Container', 'ajnanda'), 3);
            case 'button':
                return containerFooterBlock();
            default:
                return containerBlankBlock();
        }
    }

    function isContainerBlock(block) {
        return block && block.name === 'ajnanda/container';
    }

    function isContainerRow(block) {
        var attrs = block && block.attributes ? block.attributes : {};
        return isContainerBlock(block) && (attrs.layoutMode === 'grid' || attrs.containerType === 'row');
    }

    function isContainerSection(block) {
        var attrs = block && block.attributes ? block.attributes : {};
        return isContainerBlock(block) && attrs.containerType === 'section';
    }

    function containerInsertTarget(props, context) {
        var currentBlock = context && context.block;
        var parentBlock = context && context.parentBlock;

        if (isContainerRow(parentBlock) && context.grandParentId) {
            return { parentId: context.grandParentId, index: context.parentIndex };
        }

        if (isContainerSection(currentBlock)) {
            return { parentId: props.clientId, index: 0, insideCurrent: true };
        }

        if (isContainerRow(currentBlock) && context && context.parentId) {
            return { parentId: context.parentId, index: context.index };
        }

        if (isContainerRow(currentBlock)) {
            return { parentId: undefined, index: context && typeof context.index === 'number' ? context.index : 0 };
        }

        if (context && context.parentId) {
            return { parentId: context.parentId, index: context.index };
        }

        return { parentId: props.clientId, index: 0 };
    }

    function insertContainerBlock(props, context, position, block) {
        var editor = dispatch && dispatch('core/block-editor');
        var target;

        if (!editor || !block) {
            return;
        }

        if (position === 'before' || position === 'after') {
            target = containerInsertTarget(props, context);
            editor.insertBlocks(block, position === 'before' ? target.index : (target.insideCurrent ? undefined : target.index + 1), target.parentId);
            return;
        }

        editor.insertBlocks(block, position === 'before' ? 0 : undefined, props.clientId);
    }

    function insertContainerColumn(props, context, position) {
        var editor = dispatch && dispatch('core/block-editor');
        var parentBlock = context && context.parentBlock;
        var currentBlock = context && context.block;
        var parentIsColumnRow = isContainerRow(parentBlock);
        var currentIsColumnRow = isContainerRow(currentBlock);
        var block;
        var nextColumns;

        if (!editor || !context || (!parentIsColumnRow && !currentIsColumnRow)) {
            return;
        }

        block = containerChild(__('Column', 'ajnanda'), { label: __('Column', 'ajnanda'), containerType: 'tile' });

        if (parentIsColumnRow && context.parentId) {
            nextColumns = Math.max(1, (parentBlock.innerBlocks ? parentBlock.innerBlocks.length : 0) + 1);
            editor.insertBlocks(block, position === 'left' ? context.index : context.index + 1, context.parentId);
            editor.updateBlockAttributes(context.parentId, { columns: nextColumns });
            return;
        }

        nextColumns = Math.max(1, (context.innerCount || 0) + 1);
        editor.insertBlocks(block, position === 'left' ? 0 : undefined, props.clientId);
        editor.updateBlockAttributes(props.clientId, { columns: nextColumns });
    }

    function containerInsertDropdown(className, label, onSelect) {
        var controls = [
            { title: __('Heading container', 'ajnanda'), onClick: function() { onSelect('heading'); } },
            { title: __('Blank container', 'ajnanda'), onClick: function() { onSelect('blank'); } },
            { title: __('2 column row', 'ajnanda'), onClick: function() { onSelect('row-two'); } },
            { title: __('3 column row', 'ajnanda'), onClick: function() { onSelect('row-three'); } },
            { title: __('Button row', 'ajnanda'), onClick: function() { onSelect('button'); } }
        ];

        return DropdownMenu ? el(DropdownMenu, {
            className: className,
            icon: 'plus',
            label: label,
            controls: controls,
            popoverProps: { placement: 'bottom-start' }
        }) : el(Button, { className: className, label: label, onClick: function() { onSelect('blank'); } }, '+');
    }

    function containerInsertionControls(props, context) {
        var parentBlock = context && context.parentBlock;
        var currentBlock = context && context.block;
        var parentIsColumnRow = isContainerRow(parentBlock);
        var currentIsColumnRow = isContainerRow(currentBlock);
        var showColumnControls = parentIsColumnRow || currentIsColumnRow;

        return el('div', { className: 'aj-container-insert-controls', 'aria-hidden': false },
            containerInsertDropdown('aj-container-insert aj-container-insert--top', __('Add above', 'ajnanda'), function(type) {
                insertContainerBlock(props, context, 'before', containerBlockForInsert(type));
            }),
            containerInsertDropdown('aj-container-insert aj-container-insert--bottom', __('Add below', 'ajnanda'), function(type) {
                insertContainerBlock(props, context, 'after', containerBlockForInsert(type));
            }),
            showColumnControls ? el(Button, {
                className: 'aj-container-insert aj-container-insert--left',
                label: __('Add column on the left', 'ajnanda'),
                onClick: function() { insertContainerColumn(props, context, 'left'); }
            }, '+') : null,
            showColumnControls ? el(Button, {
                className: 'aj-container-insert aj-container-insert--right',
                label: __('Add column on the right', 'ajnanda'),
                onClick: function() { insertContainerColumn(props, context, 'right'); }
            }, '+') : null
        );
    }

    function containerColumns(label, count, attrs) {
        var children = [];
        var index;

        if (!createBlock) {
            return null;
        }

        for (index = 1; index <= count; index++) {
            children.push(containerChild(__('Column ', 'ajnanda') + index, { label: __('Column ', 'ajnanda') + index, containerType: 'tile' }));
        }

        return containerChild(label, Object.assign({
            layoutMode: 'grid',
            columns: count,
            gridRows: attrs && attrs.gridRows ? attrs.gridRows : 1,
            direction: 'row',
            label: label,
            containerType: 'row',
            maxWidth: 1100,
            gap: 28
        }, attrs || {}, { columns: count }), children);
    }

    function containerTemplate(pattern) {
        if (!createBlock) {
            return [];
        }

        switch (pattern) {
            case 'section-three':
                return [
                    containerHeadingBlock(),
                    containerColumns(__('Tile Container', 'ajnanda'), 3),
                    containerFooterBlock()
                ];
            case 'section-two':
                return [
                    containerHeadingBlock(),
                    containerColumns(__('Tile Container', 'ajnanda'), 2)
                ];
            case 'two':
                return [containerColumns(__('Tile Container', 'ajnanda'), 2)];
            case 'three':
                return [containerColumns(__('Tile Container', 'ajnanda'), 3)];
            case 'four':
                return [containerColumns(__('Tile Container', 'ajnanda'), 4)];
            case 'grid-2x2':
                return [containerColumns(__('Tile Container', 'ajnanda'), 4, { gridRows: 2 })];
            case 'left-wide':
                return [containerColumns(__('Tile Container', 'ajnanda'), 2)];
            case 'right-wide':
                return [containerColumns(__('Tile Container', 'ajnanda'), 2)];
            default:
                return [containerBlankBlock()];
        }
    }

    function applyContainerLayout(props, pattern) {
        var isSection = pattern !== 'one';
        var attrs = {
            layoutSelected: true,
            layoutPreset: pattern,
            label: isSection ? __('Section Container', 'ajnanda') : __('AJ Container', 'ajnanda'),
            containerType: isSection ? 'section' : 'container',
            layoutMode: 'flex',
            direction: isSection ? 'column' : 'row',
            childrenWidth: (pattern === 'one' || isSection) ? 'auto' : 'equal',
            columns: pattern === 'grid-2x2' ? 2 : (pattern === 'four' ? 4 : (pattern === 'three' || pattern === 'section-three' ? 3 : (pattern === 'one' ? 1 : 2))),
            gridRows: pattern === 'grid-2x2' ? 2 : 1
        };

        props.setAttributes(attrs);

        if (dispatch && createBlock) {
            dispatch('core/block-editor').replaceInnerBlocks(props.clientId, containerTemplate(pattern), false);
        }
    }

    function containerLayoutChooser(props) {
        var patterns = [
            { value: 'one', label: __('One column', 'ajnanda') },
            { value: 'two', label: __('Two columns', 'ajnanda') },
            { value: 'three', label: __('Three columns', 'ajnanda') },
            { value: 'four', label: __('Four columns', 'ajnanda') },
            { value: 'grid-2x2', label: __('Grid 2x2', 'ajnanda') },
            { value: 'left-wide', label: __('Left wide', 'ajnanda') },
            { value: 'right-wide', label: __('Right wide', 'ajnanda') },
            { value: 'section-two', label: __('Heading + 2 columns', 'ajnanda') },
            { value: 'section-three', label: __('Heading + 3 columns + button', 'ajnanda') }
        ];

        return el('div', { className: 'aj-container-layout-chooser' },
            el('div', { className: 'aj-container-layout-chooser__intro' },
                el('span', { className: 'dashicons dashicons-screenoptions' }),
                el('strong', {}, __('Container', 'ajnanda')),
                el('p', {}, __('Select a container layout to start with.', 'ajnanda'))
            ),
            el('div', { className: 'aj-container-layout-chooser__grid' }, patterns.map(function(pattern) {
                return el(Button, {
                    key: pattern.value,
                    className: 'aj-container-layout-choice',
                    label: pattern.label,
                    onClick: function() { applyContainerLayout(props, pattern.value); }
                }, containerPreviewColumns(pattern.value), el('span', {}, pattern.label));
            }))
        );
    }

    function containerControls(props) {
        var attrs = props.attributes;
        var isGrid = attrs.layoutMode === 'grid';

        return [
            field(__('Label', 'ajnanda'), attrs.label, function(value) { props.setAttributes({ label: value }); }, __('Container label', 'ajnanda')),
            segmented(__('Container Type', 'ajnanda'), attrs.containerType || 'container', [
                { label: __('Container', 'ajnanda'), value: 'container' },
                { label: __('Section', 'ajnanda'), value: 'section' },
                { label: __('Row', 'ajnanda'), value: 'row' },
                { label: __('Tile', 'ajnanda'), value: 'tile' }
            ], function(value) { props.setAttributes({ containerType: value }); }),
            segmented(__('Width', 'ajnanda'), attrs.contentWidth || 'boxed', [
                { label: __('Boxed', 'ajnanda'), value: 'boxed' },
                { label: __('Full Width', 'ajnanda'), value: 'full' }
            ], function(value) { props.setAttributes({ contentWidth: value }); }),
            segmented(__('Quick Add', 'ajnanda'), 'none', [
                { label: __('Header', 'ajnanda'), value: 'header' },
                { label: __('Row', 'ajnanda'), value: 'row' },
                { label: __('Footer', 'ajnanda'), value: 'footer' }
            ], function(value) {
                if (!dispatch || !createBlock) {
                    return;
                }

                var block = value === 'row' ? containerColumns(__('Tile Container', 'ajnanda'), Math.max(2, attrs.columns || 3)) : containerChild(value === 'header' ? __('Heading Container', 'ajnanda') : __('Footer Container', 'ajnanda'), {
                    label: value === 'header' ? __('Heading Container', 'ajnanda') : __('Footer Container', 'ajnanda'),
                    containerType: value === 'header' ? 'header' : 'footer',
                    alignItems: 'center'
                }, value === 'header' ? [createBlock('core/heading', { level: 2, content: __('Section heading', 'ajnanda'), textAlign: 'center' })] : [createBlock('core/buttons', { layout: { type: 'flex', justifyContent: 'center' } }, [createBlock('core/button', { text: __('Button', 'ajnanda') })])]);

                dispatch('core/block-editor').insertBlocks(block, undefined, props.clientId);
            }, __('Add a heading, row, or footer/button area without converting everything into columns.', 'ajnanda')),
            segmented(__('Layout', 'ajnanda'), attrs.layoutMode || 'flex', [
                { label: __('Flex', 'ajnanda'), value: 'flex' },
                { label: __('Grid', 'ajnanda'), value: 'grid' }
            ], function(value) { props.setAttributes({ layoutMode: value }); }),
            !isGrid ? segmented(__('Direction', 'ajnanda'), attrs.direction || 'row', [
                { label: __('Row', 'ajnanda'), value: 'row', icon: '→' },
                { label: __('Column', 'ajnanda'), value: 'column', icon: '↓' },
                { label: __('Row Reverse', 'ajnanda'), value: 'row-reverse', icon: '←' },
                { label: __('Column Reverse', 'ajnanda'), value: 'column-reverse', icon: '↑' }
            ], function(value) { props.setAttributes({ direction: value }); }, __('Define the direction in which blocks inside this container are placed.', 'ajnanda')) : null,
            !isGrid ? segmented(__('Children Width', 'ajnanda'), attrs.childrenWidth || 'equal', [
                { label: __('Auto', 'ajnanda'), value: 'auto' },
                { label: __('Equal', 'ajnanda'), value: 'equal' }
            ], function(value) { props.setAttributes({ childrenWidth: value }); }) : null,
            isGrid ? el(RangeControl, { label: __('Columns', 'ajnanda'), min: 1, max: 6, value: attrs.columns || 2, onChange: function(value) { props.setAttributes({ columns: value }); } }) : null,
            isGrid ? el(RangeControl, { label: __('Rows', 'ajnanda'), min: 1, max: 6, value: attrs.gridRows || 1, onChange: function(value) { props.setAttributes({ gridRows: value }); } }) : null,
            segmented(__('Align Items', 'ajnanda'), attrs.alignItems || 'stretch', [
                { label: __('Start', 'ajnanda'), value: 'flex-start', icon: '▔' },
                { label: __('Center', 'ajnanda'), value: 'center', icon: '≡' },
                { label: __('End', 'ajnanda'), value: 'flex-end', icon: '▁' },
                { label: __('Stretch', 'ajnanda'), value: 'stretch', icon: '▮' }
            ], function(value) { props.setAttributes({ alignItems: value }); }, isGrid ? __('Define the vertical alignment for grid items inside this container.', 'ajnanda') : __('Define the vertical alignment inside this container.', 'ajnanda')),
            segmented(__('Justify Content', 'ajnanda'), attrs.justify || 'center', [
                { label: __('Start', 'ajnanda'), value: 'flex-start', icon: '▌▌' },
                { label: __('Center', 'ajnanda'), value: 'center', icon: '|▌|' },
                { label: __('End', 'ajnanda'), value: 'flex-end', icon: '▌▌' },
                { label: __('Space Between', 'ajnanda'), value: 'space-between', icon: '▌  ▌' },
                { label: __('Space Around', 'ajnanda'), value: 'space-around', icon: ' ▌ ▌ ' },
                { label: __('Space Evenly', 'ajnanda'), value: 'space-evenly', icon: '▌ ▌ ▌' }
            ], function(value) { props.setAttributes({ justify: value }); }, isGrid ? __('Define the horizontal alignment for grid items within this container.', 'ajnanda') : __('Define the horizontal alignment inside this container.', 'ajnanda')),
            isGrid ? segmented(__('Align Content', 'ajnanda'), attrs.alignContent || 'stretch', [
                { label: __('Start', 'ajnanda'), value: 'start', icon: '▔' },
                { label: __('Center', 'ajnanda'), value: 'center', icon: '≡' },
                { label: __('End', 'ajnanda'), value: 'end', icon: '▁' },
                { label: __('Stretch', 'ajnanda'), value: 'stretch', icon: '▮' },
                { label: __('Between', 'ajnanda'), value: 'space-between', icon: '▔▁' },
                { label: __('Evenly', 'ajnanda'), value: 'space-evenly', icon: '≡≡' }
            ], function(value) { props.setAttributes({ alignContent: value }); }) : null,
            !isGrid ? segmented(__('Wrap', 'ajnanda'), attrs.wrapMode || 'wrap', [
                { label: __('No Wrap', 'ajnanda'), value: 'nowrap', icon: '↔' },
                { label: __('Wrap', 'ajnanda'), value: 'wrap', icon: '↵' },
                { label: __('Reverse', 'ajnanda'), value: 'wrap-reverse', icon: '↩' }
            ], function(value) { props.setAttributes({ wrapMode: value }); }) : null,
            el(RangeControl, { label: __('Gap', 'ajnanda'), min: 0, max: 96, value: attrs.gap || 16, onChange: function(value) { props.setAttributes({ gap: value }); } }),
            el(RangeControl, { label: __('Max width', 'ajnanda'), min: 320, max: 1800, value: attrs.maxWidth || 1100, onChange: function(value) { props.setAttributes({ maxWidth: value }); } }),
            el(RangeControl, { label: __('Minimum height', 'ajnanda'), min: 0, max: 900, value: attrs.minHeight || 0, onChange: function(value) { props.setAttributes({ minHeight: value }); } })
        ];
    }

    function mediaControls(props) {
        var attrs = props.attributes;

        return [
            el(RangeControl, { label: __('Minimum height', 'ajnanda'), min: 100, max: 800, value: attrs.minHeight || 320, onChange: function(value) { props.setAttributes({ minHeight: value }); } }),
            el(SelectControl, {
                label: __('Aspect ratio', 'ajnanda'),
                value: attrs.aspectRatio || '16 / 9',
                options: [
                    { label: '16:9', value: '16 / 9' },
                    { label: '4:3', value: '4 / 3' },
                    { label: '1:1', value: '1 / 1' },
                    { label: __('Auto', 'ajnanda'), value: '' }
                ],
                onChange: function(value) { props.setAttributes({ aspectRatio: value }); }
            })
        ];
    }

    registerContainerBlock('ajnanda/div-block', __('AJ Div Block', 'ajnanda'), __('Simple wrapper block.', 'ajnanda'), 'aj-div', [], {
        attributes: { minHeight: { type: 'number', default: 0 } },
        controls: function(props) {
            return el(RangeControl, { label: __('Minimum height', 'ajnanda'), min: 0, max: 800, value: props.attributes.minHeight || 0, onChange: function(value) { props.setAttributes({ minHeight: value }); } });
        }
    });
    registerContainerBlock('ajnanda/flexbox', __('AJ Flexbox', 'ajnanda'), __('Flexible row or column layout.', 'ajnanda'), 'aj-flexbox', [['core/paragraph', { placeholder: __('Flex item', 'ajnanda') }]], {
        attributes: { direction: { type: 'string', default: 'row' }, justify: { type: 'string', default: 'flex-start' }, alignItems: { type: 'string', default: 'stretch' }, wrap: { type: 'boolean', default: true }, gap: { type: 'number', default: 16 } },
        controls: layoutControls,
        className: function(attrs) { return classNames('aj-flexbox--' + attrs.direction, attrs.wrap === false ? 'aj-flexbox--nowrap' : ''); }
    });
    registerContainerBlock('ajnanda/container', __('AJ Container', 'ajnanda'), __('Constrained content container.', 'ajnanda'), 'aj-container', [], {
        label: function(attrs) {
            return attrs.label || __('AJ Container', 'ajnanda');
        },
        attributes: {
            label: { type: 'string', default: '' },
            containerType: { type: 'string', default: 'container' },
            contentWidth: { type: 'string', default: 'boxed' },
            layoutSelected: { type: 'boolean', default: false },
            layoutPreset: { type: 'string', default: '' },
            layoutMode: { type: 'string', default: 'flex' },
            direction: { type: 'string', default: 'row' },
            childrenWidth: { type: 'string', default: 'equal' },
            alignItems: { type: 'string', default: 'stretch' },
            justify: { type: 'string', default: 'center' },
            wrapMode: { type: 'string', default: 'wrap' },
            columns: { type: 'number', default: 2 },
            gridRows: { type: 'number', default: 1 },
            alignContent: { type: 'string', default: 'stretch' },
            maxWidth: { type: 'number', default: 1100 },
            minHeight: { type: 'number', default: 0 },
            gap: { type: 'number', default: 16 }
        },
        controls: containerControls,
        className: function(attrs) {
            return classNames(
                'aj-container--' + (attrs.layoutMode || 'flex'),
                'aj-container--preset-' + (attrs.layoutPreset || 'custom'),
                'aj-container--type-' + (attrs.containerType || 'container'),
                'aj-container--width-' + (attrs.contentWidth || 'boxed'),
                attrs.layoutMode === 'flex' ? 'aj-container--' + (attrs.direction || 'row') : '',
                attrs.layoutMode === 'flex' ? 'aj-container--children-' + (attrs.childrenWidth || 'equal') : '',
                attrs.layoutMode === 'flex' ? 'aj-container--wrap-' + (attrs.wrapMode || 'wrap') : ''
            );
        }
    });
    registerContainerBlock('ajnanda/grid', __('AJ Grid', 'ajnanda'), __('Responsive grid layout.', 'ajnanda'), 'aj-grid', [['core/group', { className: 'aj-card' }], ['core/group', { className: 'aj-card' }], ['core/group', { className: 'aj-card' }]], {
        attributes: { columns: { type: 'number', default: 3 }, gap: { type: 'number', default: 20 } },
        controls: gridControls
    });

    registerBlockType('ajnanda/heading', {
        title: __('AJ Heading', 'ajnanda'),
        category: category,
        icon: 'heading',
        attributes: withStyleAttributes({ content: { type: 'string', source: 'html', selector: '.aj-heading' }, level: { type: 'number', default: 2 } }),
        edit: function(props) {
            var level = props.attributes.level || 2;
            return el(Fragment, {},
                inspector(headingControls(props)),
                el(RichText, Object.assign({ tagName: 'h' + level, value: props.attributes.content, placeholder: __('Heading', 'ajnanda'), onChange: function(value) { props.setAttributes({ content: value }); } }, styledProps('aj-heading', props.attributes)))
            );
        },
        save: function(props) {
            return el(RichText.Content, Object.assign({ tagName: 'h' + (props.attributes.level || 2), value: props.attributes.content }, styledProps('aj-heading', props.attributes)));
        }
    });

    registerBlockType('ajnanda/text-editor', {
        title: __('AJ Paragraph/Text Editor', 'ajnanda'),
        category: category,
        icon: 'editor-paragraph',
        attributes: withStyleAttributes({ content: { type: 'string', source: 'html', selector: 'p' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props)),
                el(RichText, Object.assign({ tagName: 'p', value: props.attributes.content, placeholder: __('Text', 'ajnanda'), onChange: function(value) { props.setAttributes({ content: value }); } }, styledProps('aj-text', props.attributes)))
            );
        },
        save: function(props) {
            return el(RichText.Content, Object.assign({ tagName: 'p', value: props.attributes.content }, styledProps('aj-text', props.attributes)));
        }
    });

    registerBlockType('ajnanda/image', {
        title: __('AJ Image', 'ajnanda'),
        category: category,
        icon: 'format-image',
        attributes: withStyleAttributes({ url: { type: 'string' }, alt: { type: 'string' } }),
        edit: function(props) {
            var attrs = props.attributes;
            return el(Fragment, {},
                inspector(controlsWithCommon(props, field(__('Alt text', 'ajnanda'), attrs.alt, function(value) { props.setAttributes({ alt: value }); }))),
                el('figure', styledProps('aj-image', attrs),
                    attrs.url ? el('img', { src: attrs.url, alt: attrs.alt || '' }) : null,
                    el(MediaUploadCheck, {}, el(MediaUpload, {
                        onSelect: function(media) { props.setAttributes({ url: media.url, alt: media.alt || '' }); },
                        allowedTypes: ['image'],
                        render: function(obj) { return el(Button, { variant: attrs.url ? 'secondary' : 'primary', onClick: obj.open }, attrs.url ? __('Replace Image', 'ajnanda') : __('Select Image', 'ajnanda')); }
                    }))
                )
            );
        },
        save: function(props) {
            return el('figure', styledProps('aj-image', props.attributes), props.attributes.url ? el('img', { src: props.attributes.url, alt: props.attributes.alt || '' }) : null);
        }
    });

    registerBlockType('ajnanda/button', {
        title: __('AJ Button', 'ajnanda'),
        category: category,
        icon: 'button',
        attributes: withStyleAttributes({ text: { type: 'string', default: 'Button' }, url: { type: 'string' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, urlField(props.attributes.url, function(value) { props.setAttributes({ url: value }); }))),
                el(RichText, Object.assign({ tagName: 'a', value: props.attributes.text, placeholder: __('Button text', 'ajnanda'), onChange: function(value) { props.setAttributes({ text: value }); } }, styledProps('aj-button', props.attributes)))
            );
        },
        save: function(props) {
            return el('a', Object.assign({ href: props.attributes.url || '#' }, styledProps('aj-button', props.attributes)), props.attributes.text);
        }
    });

    registerBlockType('ajnanda/divider', {
        title: __('AJ Divider', 'ajnanda'),
        category: category,
        icon: 'minus',
        attributes: withStyleAttributes({ label: { type: 'string', default: '' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, field(__('Optional label', 'ajnanda'), props.attributes.label, function(value) { props.setAttributes({ label: value }); }))),
                el('div', styledProps('aj-divider', props.attributes), props.attributes.label ? el('span', {}, props.attributes.label) : null)
            );
        },
        save: function(props) {
            return el('div', styledProps('aj-divider', props.attributes), props.attributes.label ? el('span', {}, props.attributes.label) : null);
        }
    });

    registerBlockType('ajnanda/spacer', {
        title: __('AJ Spacer', 'ajnanda'),
        category: category,
        icon: 'image-flip-vertical',
        attributes: withStyleAttributes({ height: { type: 'number', default: 48 } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, el(RangeControl, { label: __('Height', 'ajnanda'), min: 8, max: 320, value: props.attributes.height, onChange: function(value) { props.setAttributes({ height: value }); } }))),
                el('div', Object.assign(styledProps('aj-spacer', props.attributes), { style: Object.assign(blockStyle(props.attributes), { height: props.attributes.height + 'px' }) }))
            );
        },
        save: function(props) {
            return el('div', Object.assign(styledProps('aj-spacer', props.attributes), { style: Object.assign(blockStyle(props.attributes), { height: props.attributes.height + 'px' }) }));
        }
    });

    registerBlockType('ajnanda/icon', {
        title: __('AJ Icon', 'ajnanda'),
        category: category,
        icon: 'star-filled',
        attributes: withStyleAttributes({ icon: { type: 'string', default: '★' }, label: { type: 'string', default: '' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, [field(__('Icon character', 'ajnanda'), props.attributes.icon, function(value) { props.setAttributes({ icon: value }); }), field(__('Label', 'ajnanda'), props.attributes.label, function(value) { props.setAttributes({ label: value }); })])),
                el('span', Object.assign({ 'aria-label': props.attributes.label || undefined }, styledProps('aj-icon', props.attributes)), props.attributes.icon)
            );
        },
        save: function(props) {
            return el('span', Object.assign({ 'aria-label': props.attributes.label || undefined }, styledProps('aj-icon', props.attributes)), props.attributes.icon);
        }
    });

    registerBlockType('ajnanda/svg', {
        title: __('AJ SVG', 'ajnanda'),
        category: category,
        icon: 'admin-customizer',
        attributes: withStyleAttributes({ svg: { type: 'string', default: '<svg viewBox="0 0 80 80" role="img" aria-label="Circle"><circle cx="40" cy="40" r="32"/></svg>' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, el(TextareaControl, { label: __('SVG markup', 'ajnanda'), value: props.attributes.svg, onChange: function(value) { props.setAttributes({ svg: value }); } }))),
                ServerSideRender ? el(ServerSideRender, { block: 'ajnanda/svg', attributes: props.attributes }) : el('div', { className: 'aj-block aj-svg' }, __('SVG preview', 'ajnanda'))
            );
        },
        save: function(props) {
            return null;
        }
    });

    function mediaEmbedBlock(name, title, icon, className, placeholder, extraAttrs, extraControlBuilder) {
        registerBlockType(name, {
            title: title,
            category: category,
            icon: icon,
            attributes: withStyleAttributes(Object.assign({ url: { type: 'string' }, minHeight: { type: 'number', default: 320 }, aspectRatio: { type: 'string', default: '16 / 9' } }, extraAttrs || {})),
            edit: function(props) {
                return el(Fragment, {}, inspector(controlsWithCommon(props, [field(__('URL', 'ajnanda'), props.attributes.url, function(value) { props.setAttributes({ url: value }); }, placeholder)].concat(mediaControls(props), extraControlBuilder ? extraControlBuilder(props) : []))), el('div', styledProps(className, props.attributes), props.attributes.url || placeholder));
            },
            save: function(props) {
                return el('div', styledProps(className, props.attributes), props.attributes.url ? el('iframe', { src: props.attributes.url, loading: 'lazy', allowFullScreen: true, title: title }) : null);
            }
        });
    }

    mediaEmbedBlock('ajnanda/youtube', __('AJ YouTube', 'ajnanda'), 'video-alt3', 'aj-embed', 'https://www.youtube.com/embed/...');
    mediaEmbedBlock('ajnanda/video', __('AJ Video', 'ajnanda'), 'format-video', 'aj-embed', 'Video embed URL', { controls: { type: 'string', default: 'playback' } }, function(props) {
        return el(SelectControl, { label: __('Controls', 'ajnanda'), value: props.attributes.controls || 'playback', options: [{ label: __('Playback controls', 'ajnanda'), value: 'playback' }, { label: __('Minimal', 'ajnanda'), value: 'minimal' }], onChange: function(value) { props.setAttributes({ controls: value }); } });
    });
    mediaEmbedBlock('ajnanda/google-maps', __('AJ Google Maps Embed', 'ajnanda'), 'location-alt', 'aj-embed', 'Google Maps embed URL', { zoom: { type: 'number', default: 12 } }, function(props) {
        return el(RangeControl, { label: __('Zoom', 'ajnanda'), min: 1, max: 20, value: props.attributes.zoom || 12, onChange: function(value) { props.setAttributes({ zoom: value }); } });
    });

    function editableTextBlock(name, title, icon, tagName, className, defaultText, placeholder) {
        registerBlockType(name, {
            title: title,
            category: category,
            icon: icon,
            attributes: withStyleAttributes({ content: { type: 'string', source: 'html', selector: tagName, default: defaultText || '' } }),
            edit: function(props) {
                return el(Fragment, {},
                    inspector(controlsWithCommon(props)),
                    el(RichText, Object.assign({ tagName: tagName, value: props.attributes.content, placeholder: placeholder || title, onChange: function(value) { props.setAttributes({ content: value }); } }, styledProps(className, props.attributes)))
                );
            },
            save: function(props) {
                return el(RichText.Content, Object.assign({ tagName: tagName, value: props.attributes.content }, styledProps(className, props.attributes)));
            }
        });
    }

    function simpleCardBlock(name, title, icon, className, template, options) {
        options = options || {};
        registerBlockType(name, {
            title: title,
            category: category,
            icon: icon,
            supports: { align: ['wide', 'full'], anchor: true },
            attributes: withStyleAttributes(options.attributes || {}),
            variations: options.variations || [],
            edit: function(props) {
                return el(Fragment, {},
                    inspector(controlsWithCommon(props, extraControls(props, options))),
                    options.searchAIControls ? el(InspectorControls, {}, el(PanelBody, { title: __('Search & AI', 'ajnanda'), initialOpen: false }, options.searchAIControls(props))) : null,
                    el('section', styledProps(className, props.attributes, extraClass(props.attributes, options)), el(InnerBlocks, { template: template || [], templateLock: false }))
                );
            },
            save: function(props) {
                return el('section', styledProps(className, props.attributes, extraClass(props.attributes, options)), el(InnerBlocks.Content));
            }
        });
    }

    function ajButtonsStyle(attrs) {
        return Object.assign({}, blockStyle(attrs), {
            '--aj-buttons-gap-desktop': (attrs.gapDesktop || attrs.gap || 12) + 'px',
            '--aj-buttons-gap-tablet': (attrs.gapTablet || attrs.gapDesktop || attrs.gap || 12) + 'px',
            '--aj-buttons-gap-mobile': (attrs.gapMobile || attrs.gapTablet || attrs.gapDesktop || attrs.gap || 12) + 'px'
        });
    }

    function ajButtonsProps(attrs) {
        return {
            className: classNames(
                'aj-block',
                'aj-buttons',
                'aj-buttons-desktop-' + (attrs.layoutDesktop || attrs.orientation || 'row'),
                'aj-buttons-tablet-' + (attrs.layoutTablet || attrs.layoutDesktop || attrs.orientation || 'row'),
                'aj-buttons-mobile-' + (attrs.layoutMobile || attrs.layoutTablet || attrs.layoutDesktop || attrs.orientation || 'stack'),
                attrs && attrs.alignText ? 'has-text-align-' + attrs.alignText : '',
                attrs && attrs.animation && attrs.animation !== 'none' ? 'aj-animate-' + attrs.animation : ''
            ),
            style: ajButtonsStyle(attrs)
        };
    }

    function ajButtonsLayoutControl(props, device, label, fallback) {
        var attr = 'layout' + device;
        var value = props.attributes[attr] || fallback;

        return el(SelectControl, {
            label: label,
            value: value,
            options: [
                { label: __('Horizontal row', 'ajnanda'), value: 'row' },
                { label: __('Stacked', 'ajnanda'), value: 'stack' },
                { label: __('Equal grid', 'ajnanda'), value: 'grid' },
                { label: __('First wide, rest below', 'ajnanda'), value: 'featured' }
            ],
            onChange: function(nextValue) {
                var update = {};
                update[attr] = nextValue;
                props.setAttributes(update);
            }
        });
    }

    function ajButtonsGapControl(props, device, label, fallback) {
        var attr = 'gap' + device;
        var value = props.attributes[attr] || fallback;

        return el(RangeControl, {
            label: label,
            min: 0,
            max: 60,
            value: value,
            onChange: function(nextValue) {
                var update = {};
                update[attr] = nextValue;
                props.setAttributes(update);
            }
        });
    }

    function registerAJButtonsBlock() {
        registerBlockType('ajnanda/buttons', {
            title: __('AJ Buttons', 'ajnanda'),
            description: __('Legacy AJ Buttons wrapper. Use the native AJ Buttons variation instead.', 'ajnanda'),
            category: category,
            icon: 'button',
            supports: { align: ['wide', 'full'], anchor: true, inserter: false },
            attributes: withStyleAttributes({
                layoutDesktop: { type: 'string', default: 'row' },
                layoutTablet: { type: 'string', default: 'row' },
                layoutMobile: { type: 'string', default: 'stack' },
                gapDesktop: { type: 'number', default: 12 },
                gapTablet: { type: 'number', default: 12 },
                gapMobile: { type: 'number', default: 12 },
                orientation: { type: 'string' },
                gap: { type: 'number' }
            }),
            edit: function(props) {
                var attrs = props.attributes;
                var desktopLayout = attrs.layoutDesktop || attrs.orientation || 'row';
                var tabletLayout = attrs.layoutTablet || desktopLayout;
                var mobileLayout = attrs.layoutMobile || tabletLayout || 'stack';
                var desktopGap = attrs.gapDesktop || attrs.gap || 12;
                var tabletGap = attrs.gapTablet || desktopGap;
                var mobileGap = attrs.gapMobile || tabletGap;

                return el(Fragment, {},
                    inspector([
                        ajButtonsLayoutControl(props, 'Desktop', __('Desktop layout', 'ajnanda'), desktopLayout),
                        ajButtonsLayoutControl(props, 'Tablet', __('Tablet layout', 'ajnanda'), tabletLayout),
                        ajButtonsLayoutControl(props, 'Mobile', __('Mobile layout', 'ajnanda'), mobileLayout),
                        ajButtonsGapControl(props, 'Desktop', __('Desktop gap', 'ajnanda'), desktopGap),
                        ajButtonsGapControl(props, 'Tablet', __('Tablet gap', 'ajnanda'), tabletGap),
                        ajButtonsGapControl(props, 'Mobile', __('Mobile gap', 'ajnanda'), mobileGap)
                    ]),
                    el('section', ajButtonsProps(attrs), el(InnerBlocks, {
                        allowedBlocks: ['core/button'],
                        template: [
                            ['core/button', { text: __('Button', 'ajnanda') }],
                            ['core/button', { text: __('Button', 'ajnanda') }],
                            ['core/button', { text: __('Button', 'ajnanda') }]
                        ],
                        templateLock: false
                    }))
                );
            },
            save: function(props) {
                return el('section', ajButtonsProps(props.attributes), el(InnerBlocks.Content));
            }
        });
    }

    simpleCardBlock('ajnanda/info-box', __('AJ Info Box', 'ajnanda'), 'welcome-widgets-menus', 'aj-info-box', [['ajnanda/icon'], ['core/heading', { level: 3, content: 'Info Box' }], ['core/paragraph', { placeholder: 'Add supporting text.' }]], {
        attributes: { mediaPosition: { type: 'string', default: 'top' } },
        controls: function(props) {
            return el(SelectControl, { label: __('Icon/Image position', 'ajnanda'), value: props.attributes.mediaPosition || 'top', options: [{ label: __('Top', 'ajnanda'), value: 'top' }, { label: __('Left', 'ajnanda'), value: 'left' }, { label: __('Right', 'ajnanda'), value: 'right' }], onChange: function(value) { props.setAttributes({ mediaPosition: value }); } });
        },
        className: function(attrs) { return 'aj-media-' + attrs.mediaPosition; }
    });
    simpleCardBlock('ajnanda/call-to-action', __('AJ Call To Action', 'ajnanda'), 'megaphone', 'aj-call-to-action', [['core/heading', { level: 2, content: 'Ready to get started?' }], ['core/paragraph', { placeholder: 'Add a short call to action.' }], ['ajnanda/button', { text: 'Get Started' }]], {
        attributes: { layout: { type: 'string', default: 'stacked' } },
        controls: function(props) {
            return el(SelectControl, { label: __('Layout', 'ajnanda'), value: props.attributes.layout || 'stacked', options: [{ label: __('Stacked', 'ajnanda'), value: 'stacked' }, { label: __('Inline', 'ajnanda'), value: 'inline' }], onChange: function(value) { props.setAttributes({ layout: value }); } });
        },
        className: function(attrs) { return 'aj-cta--' + attrs.layout; }
    });
    registerAJButtonsBlock();
    simpleCardBlock('ajnanda/marketing-button', __('AJ Marketing Button', 'ajnanda'), 'external', 'aj-marketing-button', [['core/buttons', { layout: { type: 'flex', justifyContent: 'center' } }, [['core/button', { text: 'Marketing Button' }]]]], {
        attributes: { showIcon: { type: 'boolean', default: true }, iconPosition: { type: 'string', default: 'after' } },
        controls: function(props) {
            return [el(ToggleControl, { label: __('Show icon', 'ajnanda'), checked: !!props.attributes.showIcon, onChange: function(value) { props.setAttributes({ showIcon: value }); } }), el(SelectControl, { label: __('Icon position', 'ajnanda'), value: props.attributes.iconPosition || 'after', options: [{ label: __('Before', 'ajnanda'), value: 'before' }, { label: __('After', 'ajnanda'), value: 'after' }], onChange: function(value) { props.setAttributes({ iconPosition: value }); } })];
        },
        className: function(attrs) { return classNames(attrs.showIcon ? 'aj-marketing-button--icon' : '', 'aj-icon-' + attrs.iconPosition); }
    });
    editableTextBlock('ajnanda/blockquote', __('AJ Blockquote', 'ajnanda'), 'format-quote', 'blockquote', 'aj-blockquote', 'Add a quote or testimonial.', 'Quote');
    simpleCardBlock('ajnanda/content-timeline', __('AJ Content Timeline', 'ajnanda'), 'networking', 'aj-timeline', [['core/heading', { level: 3, content: 'Timeline Item' }], ['core/paragraph', { placeholder: 'Add milestone details.' }]], {
        attributes: { linePosition: { type: 'string', default: 'left' } },
        controls: function(props) {
            return el(SelectControl, { label: __('Line position', 'ajnanda'), value: props.attributes.linePosition || 'left', options: [{ label: __('Left', 'ajnanda'), value: 'left' }, { label: __('Center', 'ajnanda'), value: 'center' }], onChange: function(value) { props.setAttributes({ linePosition: value }); } });
        },
        className: function(attrs) { return 'aj-timeline--line-' + attrs.linePosition; }
    });

    registerBlockType('ajnanda/faq', {
        title: __('AJ FAQ', 'ajnanda'),
        description: __('Add accordions and FAQ schema to your page.', 'ajnanda'),
        category: category,
        icon: 'editor-help',
        supports: { align: ['wide', 'full'], anchor: true },
        attributes: withStyleAttributes({
            layout: { type: 'string', default: 'accordion' },
            columns: { type: 'number', default: 2 },
            collapseOtherItems: { type: 'boolean', default: true },
            expandFirstItem: { type: 'boolean', default: true },
            enableToggle: { type: 'boolean', default: true },
            enableSchema: { type: 'boolean', default: false },
            enableSeparator: { type: 'boolean', default: false },
            questionTag: { type: 'string', default: 'span' },
            icon: { type: 'string', default: '+' },
            activeIcon: { type: 'string', default: '-' },
            iconPosition: { type: 'string', default: 'left' },
            questionColor: { type: 'string', default: '' },
            answerColor: { type: 'string', default: '' },
            activeColor: { type: 'string', default: '' },
            separatorColor: { type: 'string', default: '' },
            animation: { type: 'string', default: 'none' }
        }),
        variations: [{
            name: 'search-ai-enabled',
            title: __('AJ FAQ', 'ajnanda'),
            isDefault: true,
            attributes: { enableSchema: true }
        }],
        edit: function(props) {
            var attrs = props.attributes;
            var faqClass = classNames(
                'aj-block',
                'aj-faq',
                'aj-faq--' + (attrs.layout || 'accordion'),
                attrs.enableSeparator ? 'aj-faq--separator' : '',
                attrs.iconPosition ? 'aj-faq--icon-' + attrs.iconPosition : '',
                attrs.animation && attrs.animation !== 'none' ? 'aj-animate-' + attrs.animation : ''
            );
            var faqStyle = Object.assign(blockStyle(attrs), {
                '--aj-faq-columns': attrs.columns || 2,
                '--aj-faq-question-color': attrs.questionColor || '',
                '--aj-faq-answer-color': attrs.answerColor || '',
                '--aj-faq-active-color': attrs.activeColor || '',
                '--aj-faq-separator-color': attrs.separatorColor || ''
            });

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: __('General', 'ajnanda'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Layout', 'ajnanda'),
                            value: attrs.layout,
                            options: [
                                { label: __('Accordion', 'ajnanda'), value: 'accordion' },
                                { label: __('Grid', 'ajnanda'), value: 'grid' }
                            ],
                            onChange: function(value) { props.setAttributes({ layout: value }); }
                        }),
                        attrs.layout === 'grid' ? el(RangeControl, { label: __('Grid columns', 'ajnanda'), min: 1, max: 4, value: attrs.columns || 2, onChange: function(value) { props.setAttributes({ columns: value }); } }) : null,
                        el(ToggleControl, { label: __('Collapse other items', 'ajnanda'), checked: !!attrs.collapseOtherItems, onChange: function(value) { props.setAttributes({ collapseOtherItems: value }); } }),
                        el(ToggleControl, { label: __('Expand First Item', 'ajnanda'), checked: !!attrs.expandFirstItem, onChange: function(value) { props.setAttributes({ expandFirstItem: value }); } }),
                        el(ToggleControl, { label: __('Enable Toggle', 'ajnanda'), checked: !!attrs.enableToggle, onChange: function(value) { props.setAttributes({ enableToggle: value }); } }),
                        el(ToggleControl, { label: __('Enable Separator', 'ajnanda'), checked: !!attrs.enableSeparator, onChange: function(value) { props.setAttributes({ enableSeparator: value }); } }),
                        el(SelectControl, {
                            label: __('Question Tag', 'ajnanda'),
                            value: attrs.questionTag,
                            options: [
                                { label: 'H1', value: 'h1' },
                                { label: 'H2', value: 'h2' },
                                { label: 'H3', value: 'h3' },
                                { label: 'H4', value: 'h4' },
                                { label: 'H5', value: 'h5' },
                                { label: 'H6', value: 'h6' },
                                { label: 'Span', value: 'span' },
                                { label: 'P', value: 'p' }
                            ],
                            onChange: function(value) { props.setAttributes({ questionTag: value }); }
                        })
                    ),
                    el(PanelBody, { title: __('Icon', 'ajnanda'), initialOpen: false },
                        field(__('Icon', 'ajnanda'), attrs.icon, function(value) { props.setAttributes({ icon: value }); }, '+'),
                        field(__('Active Icon', 'ajnanda'), attrs.activeIcon, function(value) { props.setAttributes({ activeIcon: value }); }, '-'),
                        el(SelectControl, {
                            label: __('Icon Position', 'ajnanda'),
                            value: attrs.iconPosition,
                            options: [
                                { label: __('Left', 'ajnanda'), value: 'left' },
                                { label: __('Right', 'ajnanda'), value: 'right' }
                            ],
                            onChange: function(value) { props.setAttributes({ iconPosition: value }); }
                        })
                    ),
                    el(PanelBody, { title: __('Style', 'ajnanda'), initialOpen: false },
                        field(__('Question color', 'ajnanda'), attrs.questionColor, function(value) { props.setAttributes({ questionColor: value }); }, '#111827'),
                        field(__('Answer color', 'ajnanda'), attrs.answerColor, function(value) { props.setAttributes({ answerColor: value }); }, '#374151'),
                        field(__('Active question color', 'ajnanda'), attrs.activeColor, function(value) { props.setAttributes({ activeColor: value }); }, '#2563eb'),
                        field(__('Separator color', 'ajnanda'), attrs.separatorColor, function(value) { props.setAttributes({ separatorColor: value }); }, '#e5e7eb'),
                        field(__('Background color', 'ajnanda'), attrs.backgroundColor, function(value) { props.setAttributes({ backgroundColor: value }); }, '#ffffff'),
                        field(__('Border color', 'ajnanda'), attrs.borderColor, function(value) { props.setAttributes({ borderColor: value }); }, '#e5e7eb'),
                        el(RangeControl, { label: __('Border radius', 'ajnanda'), min: 0, max: 40, value: attrs.borderRadius || 0, onChange: function(value) { props.setAttributes({ borderRadius: value }); } }),
                        el(RangeControl, { label: __('Padding', 'ajnanda'), min: 0, max: 80, value: attrs.padding || 0, onChange: function(value) { props.setAttributes({ padding: value }); } })
                    ),
                    el(PanelBody, { title: __('Advanced', 'ajnanda'), initialOpen: false },
                        el(SelectControl, {
                            label: __('Animation', 'ajnanda'),
                            value: attrs.animation,
                            options: [
                                { label: __('None', 'ajnanda'), value: 'none' },
                                { label: __('Fade In', 'ajnanda'), value: 'fade-in' },
                                { label: __('Slide Up', 'ajnanda'), value: 'slide-up' },
                                { label: __('Zoom In', 'ajnanda'), value: 'zoom-in' }
                            ],
                            onChange: function(value) { props.setAttributes({ animation: value }); }
                        }),
                        el(RangeControl, { label: __('Margin top', 'ajnanda'), min: 0, max: 160, value: attrs.marginTop || 0, onChange: function(value) { props.setAttributes({ marginTop: value }); } }),
                        el(RangeControl, { label: __('Margin bottom', 'ajnanda'), min: 0, max: 160, value: attrs.marginBottom || 0, onChange: function(value) { props.setAttributes({ marginBottom: value }); } })
                    ),
                    el(PanelBody, { title: __('Search & AI', 'ajnanda'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Describe these questions as FAQ content', 'ajnanda'),
                            checked: !!attrs.enableSchema,
                            onChange: function(value) { props.setAttributes({ enableSchema: value }); },
                            help: __('Helps search engines and AI systems understand the visible questions and answers.', 'ajnanda')
                        })
                    )
                ),
                el('section', {
                    className: faqClass,
                    style: faqStyle,
                    'data-layout': attrs.layout,
                    'data-collapse-other-items': attrs.collapseOtherItems ? 'true' : 'false',
                    'data-expand-first-item': attrs.expandFirstItem ? 'true' : 'false',
                    'data-enable-toggle': attrs.enableToggle ? 'true' : 'false',
                    'data-enable-schema': attrs.enableSchema ? 'true' : 'false',
                    'data-question-tag': attrs.questionTag,
                    'data-icon': attrs.icon || '+',
                    'data-active-icon': attrs.activeIcon || '-'
                }, el(InnerBlocks, { template: [['core/details', { summary: 'Question' }], ['core/details', { summary: 'Question' }]], templateLock: false }))
            );
        },
        save: function(props) {
            var attrs = props.attributes;
            return el('section', {
                className: classNames(
                    'aj-block',
                    'aj-faq',
                    'aj-faq--' + (attrs.layout || 'accordion'),
                    attrs.enableSeparator ? 'aj-faq--separator' : '',
                    attrs.iconPosition ? 'aj-faq--icon-' + attrs.iconPosition : '',
                    attrs.animation && attrs.animation !== 'none' ? 'aj-animate-' + attrs.animation : ''
                ),
                style: Object.assign(blockStyle(attrs), {
                    '--aj-faq-columns': attrs.columns || 2,
                    '--aj-faq-question-color': attrs.questionColor || '',
                    '--aj-faq-answer-color': attrs.answerColor || '',
                    '--aj-faq-active-color': attrs.activeColor || '',
                    '--aj-faq-separator-color': attrs.separatorColor || ''
                }),
                'data-layout': attrs.layout,
                'data-collapse-other-items': attrs.collapseOtherItems ? 'true' : 'false',
                'data-expand-first-item': attrs.expandFirstItem ? 'true' : 'false',
                'data-enable-toggle': attrs.enableToggle ? 'true' : 'false',
                'data-enable-schema': attrs.enableSchema ? 'true' : 'false',
                'data-question-tag': attrs.questionTag,
                'data-icon': attrs.icon || '+',
                'data-active-icon': attrs.activeIcon || '-'
            }, el(InnerBlocks.Content));
        }
    });

    simpleCardBlock('ajnanda/how-to', __('AJ How To', 'ajnanda'), 'media-document', 'aj-how-to', [['core/heading', { level: 2, content: 'How To' }], ['core/list', { values: '<li>Step one</li><li>Step two</li><li>Step three</li>' }]], {
        attributes: { showSchema: { type: 'boolean', default: false }, stepStyle: { type: 'string', default: 'numbered' } },
        variations: [{ name: 'search-ai-enabled', title: __('AJ How To', 'ajnanda'), isDefault: true, attributes: { showSchema: true } }],
        controls: function(props) {
            return el(SelectControl, { label: __('Step style', 'ajnanda'), value: props.attributes.stepStyle || 'numbered', options: [{ label: __('Numbered', 'ajnanda'), value: 'numbered' }, { label: __('Bullets', 'ajnanda'), value: 'bullets' }, { label: __('Cards', 'ajnanda'), value: 'cards' }], onChange: function(value) { props.setAttributes({ stepStyle: value }); } });
        },
        searchAIControls: function(props) {
            return el(ToggleControl, { label: __('Describe these steps as How-To content', 'ajnanda'), checked: !!props.attributes.showSchema, onChange: function(value) { props.setAttributes({ showSchema: value }); }, help: __('Helps search engines and AI systems understand the visible instructions and their order.', 'ajnanda') });
        },
        className: function(attrs) { return 'aj-how-to--' + attrs.stepStyle; }
    });
    editableTextBlock('ajnanda/inline-notice', __('AJ Inline Notice', 'ajnanda'), 'info', 'div', 'aj-inline-notice', 'Add an important notice.', 'Notice');
    simpleCardBlock('ajnanda/modal', __('AJ Modal Placeholder', 'ajnanda'), 'welcome-comments', 'aj-modal-placeholder', [['core/heading', { level: 3, content: 'Modal Placeholder' }], ['core/paragraph', { placeholder: 'Static modal content placeholder.' }]], {
        attributes: { triggerText: { type: 'string', default: 'Open Modal' }, modalWidth: { type: 'number', default: 640 } },
        controls: function(props) {
            return [field(__('Trigger text', 'ajnanda'), props.attributes.triggerText, function(value) { props.setAttributes({ triggerText: value }); }), el(RangeControl, { label: __('Modal width', 'ajnanda'), min: 320, max: 1200, value: props.attributes.modalWidth || 640, onChange: function(value) { props.setAttributes({ modalWidth: value }); } })];
        }
    });
    registerBlockType('ajnanda/slide', {
        title: __('AJ Slide', 'ajnanda'),
        category: category,
        icon: 'slides',
        parent: ['ajnanda/slider'],
        supports: { anchor: true },
        attributes: {},
        edit: function() {
            return el('div', { className: 'aj-block aj-slide aj-slide--editor' },
                el(InnerBlocks, { template: [['core/heading', { level: 2, content: 'Slide Title', textAlign: 'center' }], ['core/paragraph', { placeholder: 'Slide description.', align: 'center' }]], templateLock: false })
            );
        },
        save: function() {
            return el(InnerBlocks.Content);
        }
    });

    registerBlockType('ajnanda/slider', {
        title: __('AJ Slider', 'ajnanda'),
        category: category,
        icon: 'images-alt2',
        supports: { align: ['wide', 'full'], anchor: true },
        attributes: {
            loop:       { type: 'boolean', default: true },
            autoplay:   { type: 'boolean', default: false },
            delay:      { type: 'number',  default: 4000 },
            speed:      { type: 'number',  default: 400 },
            effect:     { type: 'string',  default: 'slide' },
            showArrows: { type: 'boolean', default: true },
            showDots:   { type: 'boolean', default: true }
        },
        edit: function(props) {
            var attrs = props.attributes;
            return el(Fragment, {},
                inspector([
                    el(ToggleControl, { label: __('Loop', 'ajnanda'), checked: attrs.loop !== false, onChange: function(v) { props.setAttributes({ loop: v }); } }),
                    el(ToggleControl, { label: __('Autoplay', 'ajnanda'), checked: !!attrs.autoplay, onChange: function(v) { props.setAttributes({ autoplay: v }); } }),
                    el(RangeControl, { label: __('Autoplay delay (ms)', 'ajnanda'), min: 1000, max: 10000, step: 500, value: attrs.delay || 4000, onChange: function(v) { props.setAttributes({ delay: v }); } }),
                    el(RangeControl, { label: __('Transition speed (ms)', 'ajnanda'), min: 100, max: 2000, value: attrs.speed || 400, onChange: function(v) { props.setAttributes({ speed: v }); } }),
                    el(SelectControl, { label: __('Effect', 'ajnanda'), value: attrs.effect || 'slide', options: [{ label: __('Slide', 'ajnanda'), value: 'slide' }, { label: __('Fade', 'ajnanda'), value: 'fade' }], onChange: function(v) { props.setAttributes({ effect: v }); } }),
                    el(ToggleControl, { label: __('Show arrows', 'ajnanda'), checked: attrs.showArrows !== false, onChange: function(v) { props.setAttributes({ showArrows: v }); } }),
                    el(ToggleControl, { label: __('Show dots', 'ajnanda'), checked: attrs.showDots !== false, onChange: function(v) { props.setAttributes({ showDots: v }); } })
                ]),
                el('div', { className: 'aj-block aj-slider aj-slider--editor' },
                    el(InnerBlocks, {
                        allowedBlocks: ['ajnanda/slide'],
                        template: [['ajnanda/slide'], ['ajnanda/slide']],
                        templateLock: false
                    })
                )
            );
        },
        save: function() {
            return el(InnerBlocks.Content);
        }
    });
    simpleCardBlock('ajnanda/lottie-animation', __('AJ Lottie Animation Placeholder', 'ajnanda'), 'controls-repeat', 'aj-lottie-placeholder', [['core/paragraph', { content: 'Lottie animation placeholder.' }]], {
        attributes: { jsonUrl: { type: 'string', default: '' }, loop: { type: 'boolean', default: true }, autoplay: { type: 'boolean', default: true } },
        controls: function(props) {
            return [field(__('Lottie JSON URL', 'ajnanda'), props.attributes.jsonUrl, function(value) { props.setAttributes({ jsonUrl: value }); }), el(ToggleControl, { label: __('Loop', 'ajnanda'), checked: !!props.attributes.loop, onChange: function(value) { props.setAttributes({ loop: value }); } }), el(ToggleControl, { label: __('Autoplay', 'ajnanda'), checked: !!props.attributes.autoplay, onChange: function(value) { props.setAttributes({ autoplay: value }); } })];
        }
    });
    simpleCardBlock('ajnanda/team', __('AJ Team', 'ajnanda'), 'groups', 'aj-team', [['core/image'], ['core/heading', { level: 3, content: 'Team Member' }], ['core/paragraph', { content: 'Role or short bio.' }]], {
        attributes: { imageShape: { type: 'string', default: 'rounded' }, socialLinks: { type: 'boolean', default: false }, enableSchema: { type: 'boolean', default: false } },
        controls: function(props) {
            return [el(SelectControl, { label: __('Image shape', 'ajnanda'), value: props.attributes.imageShape || 'rounded', options: [{ label: __('Rounded', 'ajnanda'), value: 'rounded' }, { label: __('Circle', 'ajnanda'), value: 'circle' }, { label: __('Square', 'ajnanda'), value: 'square' }], onChange: function(value) { props.setAttributes({ imageShape: value }); } }), el(ToggleControl, { label: __('Show social links area', 'ajnanda'), checked: !!props.attributes.socialLinks, onChange: function(value) { props.setAttributes({ socialLinks: value }); } })];
        },
        searchAIControls: function(props) {
            return el(ToggleControl, { label: __('Describe this person in structured data', 'ajnanda'), checked: !!props.attributes.enableSchema, onChange: function(value) { props.setAttributes({ enableSchema: value }); }, help: __('Uses the visible name, image, and biography; no duplicate profile fields are required.', 'ajnanda') });
        },
        className: function(attrs) { return 'aj-team--image-' + attrs.imageShape; }
    });
    simpleCardBlock('ajnanda/testimonials', __('AJ Testimonials', 'ajnanda'), 'format-chat', 'aj-testimonials', [['core/quote', { value: 'Add testimonial text.', citation: 'Customer Name' }]], {
        attributes: { layout: { type: 'string', default: 'single' }, showQuoteIcon: { type: 'boolean', default: true } },
        controls: function(props) {
            return [el(SelectControl, { label: __('Layout', 'ajnanda'), value: props.attributes.layout || 'single', options: [{ label: __('Single', 'ajnanda'), value: 'single' }, { label: __('Grid', 'ajnanda'), value: 'grid' }, { label: __('Carousel placeholder', 'ajnanda'), value: 'carousel' }], onChange: function(value) { props.setAttributes({ layout: value }); } }), el(ToggleControl, { label: __('Show quote icon', 'ajnanda'), checked: !!props.attributes.showQuoteIcon, onChange: function(value) { props.setAttributes({ showQuoteIcon: value }); } })];
        },
        className: function(attrs) { return 'aj-testimonials--' + attrs.layout; }
    });
    simpleCardBlock('ajnanda/review', __('AJ Review', 'ajnanda'), 'star-filled', 'aj-review', [['ajnanda/star-ratings'], ['core/quote', { value: 'Add review text.', citation: 'Reviewer Name' }]], {
        attributes: { enableSchema: { type: 'boolean', default: false }, reviewerImage: { type: 'boolean', default: false } },
        controls: function(props) {
            return [el(ToggleControl, { label: __('Enable review schema', 'ajnanda'), checked: !!props.attributes.enableSchema, onChange: function(value) { props.setAttributes({ enableSchema: value }); } }), el(ToggleControl, { label: __('Reviewer image area', 'ajnanda'), checked: !!props.attributes.reviewerImage, onChange: function(value) { props.setAttributes({ reviewerImage: value }); } })];
        }
    });
    simpleCardBlock('ajnanda/price-list', __('AJ Price List', 'ajnanda'), 'money-alt', 'aj-price-list', [['core/list', { values: '<li>Service - $99</li><li>Service - $149</li>' }]], {
        attributes: { currency: { type: 'string', default: '$' }, layout: { type: 'string', default: 'list' } },
        controls: function(props) {
            return [field(__('Currency symbol', 'ajnanda'), props.attributes.currency, function(value) { props.setAttributes({ currency: value }); }), el(SelectControl, { label: __('Layout', 'ajnanda'), value: props.attributes.layout || 'list', options: [{ label: __('List', 'ajnanda'), value: 'list' }, { label: __('Cards', 'ajnanda'), value: 'cards' }], onChange: function(value) { props.setAttributes({ layout: value }); } })];
        },
        className: function(attrs) { return 'aj-price-list--' + attrs.layout; }
    });
    simpleCardBlock('ajnanda/social-share', __('AJ Social Share', 'ajnanda'), 'share', 'aj-social-share', [['core/buttons', {}, [['core/button', { text: 'Share' }], ['core/button', { text: 'LinkedIn' }], ['core/button', { text: 'Email' }]]]], {
        attributes: { networks: { type: 'string', default: 'Facebook, LinkedIn, Email' }, iconOnly: { type: 'boolean', default: false } },
        controls: function(props) {
            return [field(__('Networks', 'ajnanda'), props.attributes.networks, function(value) { props.setAttributes({ networks: value }); }, 'Facebook, LinkedIn, Email'), el(ToggleControl, { label: __('Icon only', 'ajnanda'), checked: !!props.attributes.iconOnly, onChange: function(value) { props.setAttributes({ iconOnly: value }); } })];
        },
        className: function(attrs) { return attrs.iconOnly ? 'aj-social-share--icon-only' : ''; }
    });
    simpleCardBlock('ajnanda/separator', __('AJ Separator', 'ajnanda'), 'minus', 'aj-separator-block', [], {
        attributes: { thickness: { type: 'number', default: 1 }, width: { type: 'number', default: 100 } },
        controls: function(props) {
            return [el(RangeControl, { label: __('Thickness', 'ajnanda'), min: 1, max: 16, value: props.attributes.thickness || 1, onChange: function(value) { props.setAttributes({ thickness: value }); } }), el(RangeControl, { label: __('Width percent', 'ajnanda'), min: 10, max: 100, value: props.attributes.width || 100, onChange: function(value) { props.setAttributes({ width: value }); } })];
        }
    });

    registerContainerBlock('ajnanda/form', __('AJ Form', 'ajnanda'), __('Static form layout.', 'ajnanda'), 'aj-form', [['ajnanda/label'], ['ajnanda/input'], ['ajnanda/submit-button']], {
        attributes: { submitAction: { type: 'string', default: 'none' }, fieldGap: { type: 'number', default: 14 } },
        controls: function(props) {
            return [el(SelectControl, { label: __('Submit action', 'ajnanda'), value: props.attributes.submitAction || 'none', options: [{ label: __('Placeholder only', 'ajnanda'), value: 'none' }, { label: __('Email placeholder', 'ajnanda'), value: 'email' }, { label: __('Webhook placeholder', 'ajnanda'), value: 'webhook' }], onChange: function(value) { props.setAttributes({ submitAction: value }); } }), el(RangeControl, { label: __('Field gap', 'ajnanda'), min: 0, max: 48, value: props.attributes.fieldGap || 14, onChange: function(value) { props.setAttributes({ fieldGap: value, gap: value }); } })];
        }
    });

    function formFieldBlock(name, title, tag, defaults) {
        registerBlockType(name, {
            title: title,
            category: category,
            icon: 'forms',
            attributes: withStyleAttributes(Object.assign({ text: { type: 'string', default: defaults.text || '' }, placeholder: { type: 'string', default: defaults.placeholder || '' }, name: { type: 'string', default: defaults.name || '' }, required: { type: 'boolean', default: false }, fieldType: { type: 'string', default: defaults.type || 'text' } }, defaults.attributes || {})),
            edit: function(props) {
                var attrs = props.attributes;
                return el(Fragment, {},
                    inspector(controlsWithCommon(props, [
                        field(__('Name', 'ajnanda'), attrs.name, function(value) { props.setAttributes({ name: value }); }),
                        field(__('Placeholder', 'ajnanda'), attrs.placeholder, function(value) { props.setAttributes({ placeholder: value }); }),
                        tag === 'input' && defaults.type !== 'checkbox' ? el(SelectControl, { label: __('Input type', 'ajnanda'), value: attrs.fieldType || 'text', options: [{ label: __('Text', 'ajnanda'), value: 'text' }, { label: __('Email', 'ajnanda'), value: 'email' }, { label: __('Phone', 'ajnanda'), value: 'tel' }, { label: __('Number', 'ajnanda'), value: 'number' }, { label: __('URL', 'ajnanda'), value: 'url' }], onChange: function(value) { props.setAttributes({ fieldType: value }); } }) : null,
                        tag !== 'label' ? el(ToggleControl, { label: __('Required', 'ajnanda'), checked: !!attrs.required, onChange: function(value) { props.setAttributes({ required: value }); } }) : null
                    ])),
                    tag === 'label' ? el(RichText, Object.assign({ tagName: 'label', value: attrs.text, placeholder: __('Label', 'ajnanda'), onChange: function(value) { props.setAttributes({ text: value }); } }, styledProps('aj-label', attrs))) : el(tag, Object.assign({ placeholder: attrs.placeholder, type: attrs.fieldType || defaults.type || undefined, value: '', readOnly: true, required: !!attrs.required }, styledProps('aj-field', attrs)))
                );
            },
            save: function(props) {
                var attrs = props.attributes;
                if (tag === 'label') {
                    return el(RichText.Content, Object.assign({ tagName: 'label', value: attrs.text }, styledProps('aj-label', attrs)));
                }
                return el(tag, Object.assign({ name: attrs.name, placeholder: attrs.placeholder, type: attrs.fieldType || defaults.type || undefined, required: !!attrs.required }, styledProps('aj-field', attrs)));
            }
        });
    }

    formFieldBlock('ajnanda/input', __('AJ Input', 'ajnanda'), 'input', { placeholder: 'Your answer', name: 'field', type: 'text' });
    formFieldBlock('ajnanda/label', __('AJ Label', 'ajnanda'), 'label', { text: 'Label' });
    formFieldBlock('ajnanda/text-area', __('AJ Text Area', 'ajnanda'), 'textarea', { placeholder: 'Message', name: 'message' });
    formFieldBlock('ajnanda/checkbox', __('AJ Checkbox', 'ajnanda'), 'input', { name: 'agree', type: 'checkbox' });

    registerBlockType('ajnanda/submit-button', {
        title: __('AJ Submit Button', 'ajnanda'),
        category: category,
        icon: 'yes',
        attributes: withStyleAttributes({ text: { type: 'string', default: 'Submit' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props)),
                el(RichText, Object.assign({ tagName: 'button', value: props.attributes.text, onChange: function(value) { props.setAttributes({ text: value }); } }, styledProps('aj-button aj-submit', props.attributes)))
            );
        },
        save: function(props) {
            return el('button', Object.assign({ type: 'submit' }, styledProps('aj-button aj-submit', props.attributes)), props.attributes.text);
        }
    });

    registerContainerBlock('ajnanda/tabs', __('AJ Tabs', 'ajnanda'), __('Tabbed content placeholder.', 'ajnanda'), 'aj-tabs', [['core/heading', { level: 3, content: 'Tab Title' }], ['core/paragraph', { placeholder: 'Tab content' }]], {
        attributes: { tabPosition: { type: 'string', default: 'top' }, activeTab: { type: 'number', default: 1 } },
        controls: function(props) {
            return [el(SelectControl, { label: __('Tab position', 'ajnanda'), value: props.attributes.tabPosition || 'top', options: [{ label: __('Top', 'ajnanda'), value: 'top' }, { label: __('Left', 'ajnanda'), value: 'left' }, { label: __('Right', 'ajnanda'), value: 'right' }], onChange: function(value) { props.setAttributes({ tabPosition: value }); } }), el(RangeControl, { label: __('Default active tab', 'ajnanda'), min: 1, max: 10, value: props.attributes.activeTab || 1, onChange: function(value) { props.setAttributes({ activeTab: value }); } })];
        },
        className: function(attrs) { return 'aj-tabs--' + attrs.tabPosition; }
    });
    registerContainerBlock('ajnanda/accordion', __('AJ Accordion', 'ajnanda'), __('Expandable content layout.', 'ajnanda'), 'aj-accordion', [['core/details', { summary: 'Accordion item' }]], {
        attributes: { collapseOtherItems: { type: 'boolean', default: true }, expandFirstItem: { type: 'boolean', default: true }, iconPosition: { type: 'string', default: 'left' } },
        controls: function(props) {
            return [el(ToggleControl, { label: __('Collapse other items', 'ajnanda'), checked: !!props.attributes.collapseOtherItems, onChange: function(value) { props.setAttributes({ collapseOtherItems: value }); } }), el(ToggleControl, { label: __('Expand first item', 'ajnanda'), checked: !!props.attributes.expandFirstItem, onChange: function(value) { props.setAttributes({ expandFirstItem: value }); } }), el(SelectControl, { label: __('Icon position', 'ajnanda'), value: props.attributes.iconPosition || 'left', options: [{ label: __('Left', 'ajnanda'), value: 'left' }, { label: __('Right', 'ajnanda'), value: 'right' }], onChange: function(value) { props.setAttributes({ iconPosition: value }); } })];
        },
        className: function(attrs) { return 'aj-accordion--icon-' + attrs.iconPosition; }
    });
    registerContainerBlock('ajnanda/image-box', __('AJ Image Box', 'ajnanda'), __('Image with text.', 'ajnanda'), 'aj-image-box', [['ajnanda/image'], ['ajnanda/heading', { content: 'Image Box' }], ['ajnanda/text-editor']], {
        attributes: { imagePosition: { type: 'string', default: 'top' } },
        controls: function(props) {
            return el(SelectControl, { label: __('Image position', 'ajnanda'), value: props.attributes.imagePosition || 'top', options: [{ label: __('Top', 'ajnanda'), value: 'top' }, { label: __('Left', 'ajnanda'), value: 'left' }, { label: __('Right', 'ajnanda'), value: 'right' }], onChange: function(value) { props.setAttributes({ imagePosition: value }); } });
        },
        className: function(attrs) { return 'aj-media-' + attrs.imagePosition; }
    });
    registerContainerBlock('ajnanda/icon-box', __('AJ Icon Box', 'ajnanda'), __('Icon with text.', 'ajnanda'), 'aj-icon-box', [['ajnanda/icon'], ['ajnanda/heading', { content: 'Icon Box', level: 3 }], ['ajnanda/text-editor']], {
        attributes: { iconPosition: { type: 'string', default: 'top' } },
        controls: function(props) {
            return el(SelectControl, { label: __('Icon position', 'ajnanda'), value: props.attributes.iconPosition || 'top', options: [{ label: __('Top', 'ajnanda'), value: 'top' }, { label: __('Left', 'ajnanda'), value: 'left' }, { label: __('Right', 'ajnanda'), value: 'right' }], onChange: function(value) { props.setAttributes({ iconPosition: value }); } });
        },
        className: function(attrs) { return 'aj-media-' + attrs.iconPosition; }
    });
    registerGalleryBlock('ajnanda/basic-gallery', __('AJ Basic Gallery', 'ajnanda'), __('Image gallery with adjustable columns and gap.', 'ajnanda'));
    registerGalleryBlock('ajnanda/image-gallery', __('AJ Image Gallery', 'ajnanda'), __('Image gallery with adjustable columns and gap.', 'ajnanda'));
    registerBlockType('ajnanda/icon-list', {
        title: __('AJ Icon List', 'ajnanda'),
        description: __('Create a list highlighted with icons or images.', 'ajnanda'),
        category: category,
        icon: 'editor-ul',
        supports: { align: ['wide', 'full'], anchor: true },
        attributes: withStyleAttributes({
            layout: { type: 'string', default: 'stack' },
            columns: { type: 'number', default: 1 },
            iconType: { type: 'string', default: 'icon' },
            icon: { type: 'string', default: '→' },
            iconImageUrl: { type: 'string', default: '' },
            iconSize: { type: 'number', default: 24 },
            iconColor: { type: 'string', default: '' },
            iconBackground: { type: 'string', default: '' },
            iconGap: { type: 'number', default: 12 },
            itemGap: { type: 'number', default: 10 }
        }),
        edit: function(props) {
            var attrs = props.attributes;
            var listStyle = Object.assign(blockStyle(attrs), {
                '--aj-list-columns': attrs.columns || 1,
                '--aj-list-icon-size': (attrs.iconSize || 24) + 'px',
                '--aj-list-icon-color': attrs.iconColor || '',
                '--aj-list-icon-background': attrs.iconBackground || '',
                '--aj-list-icon-gap': (attrs.iconGap || 12) + 'px',
                '--aj-list-item-gap': (attrs.itemGap || 10) + 'px'
            });

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Icon', 'ajnanda'), initialOpen: true },
                        segmented(__('Type', 'ajnanda'), attrs.iconType || 'icon', [
                            { label: __('Icon', 'ajnanda'), value: 'icon' },
                            { label: __('Image', 'ajnanda'), value: 'image' },
                            { label: __('None', 'ajnanda'), value: 'none' }
                        ], function(value) { props.setAttributes({ iconType: value }); }),
                        attrs.iconType === 'icon' ? field(__('Icon', 'ajnanda'), attrs.icon, function(value) { props.setAttributes({ icon: value }); }, '→') : null,
                        attrs.iconType === 'image' ? el('div', { className: 'aj-image-control' },
                            attrs.iconImageUrl ? el('img', { src: attrs.iconImageUrl, alt: '' }) : el('div', { className: 'aj-image-control__empty' }, '+'),
                            el(MediaUploadCheck, {}, el(MediaUpload, {
                                onSelect: function(media) { props.setAttributes({ iconImageUrl: media.url }); },
                                allowedTypes: ['image'],
                                render: function(obj) { return el(Button, { variant: 'secondary', onClick: obj.open }, attrs.iconImageUrl ? __('Replace Image', 'ajnanda') : __('Choose Image', 'ajnanda')); }
                            }))
                        ) : null,
                        el(RangeControl, { label: __('Icon size', 'ajnanda'), min: 10, max: 80, value: attrs.iconSize || 24, onChange: function(value) { props.setAttributes({ iconSize: value }); } }),
                        field(__('Icon color', 'ajnanda'), attrs.iconColor, function(value) { props.setAttributes({ iconColor: value }); }, '#111827'),
                        field(__('Icon background', 'ajnanda'), attrs.iconBackground, function(value) { props.setAttributes({ iconBackground: value }); }, '#ffffff')
                    ),
                    el(PanelBody, { title: __('Content', 'ajnanda'), initialOpen: false },
                        el(SelectControl, { label: __('Layout', 'ajnanda'), value: attrs.layout || 'stack', options: [{ label: __('Stack', 'ajnanda'), value: 'stack' }, { label: __('Inline', 'ajnanda'), value: 'inline' }, { label: __('Grid', 'ajnanda'), value: 'grid' }], onChange: function(value) { props.setAttributes({ layout: value }); } }),
                        attrs.layout === 'grid' ? el(RangeControl, { label: __('Columns', 'ajnanda'), min: 1, max: 6, value: attrs.columns || 1, onChange: function(value) { props.setAttributes({ columns: value }); } }) : null,
                        el(RangeControl, { label: __('Space between icon and text', 'ajnanda'), min: 0, max: 48, value: attrs.iconGap || 12, onChange: function(value) { props.setAttributes({ iconGap: value }); } }),
                        el(RangeControl, { label: __('Space between items', 'ajnanda'), min: 0, max: 60, value: attrs.itemGap || 10, onChange: function(value) { props.setAttributes({ itemGap: value }); } })
                    ),
                    el(PanelBody, { title: __('Advanced', 'ajnanda'), initialOpen: false }, commonControls(props))
                ),
                el('ul', Object.assign({
                    'data-icon-type': attrs.iconType || 'icon',
                    'data-icon': attrs.icon || '→',
                    'data-icon-image': attrs.iconImageUrl || ''
                }, styledProps('aj-icon-list aj-icon-list--' + (attrs.layout || 'stack'), attrs, '',), { style: listStyle }), el(InnerBlocks, {
                    allowedBlocks: ['ajnanda/icon-list-item'],
                    template: [['ajnanda/icon-list-item', { content: 'List item' }], ['ajnanda/icon-list-item', { content: 'List item' }], ['ajnanda/icon-list-item', { content: 'List item' }]],
                    templateLock: false,
                    orientation: attrs.layout === 'inline' ? 'horizontal' : 'vertical'
                }))
            );
        },
        save: function(props) {
            var attrs = props.attributes;
            var listStyle = Object.assign(blockStyle(attrs), {
                '--aj-list-columns': attrs.columns || 1,
                '--aj-list-icon-size': (attrs.iconSize || 24) + 'px',
                '--aj-list-icon-color': attrs.iconColor || '',
                '--aj-list-icon-background': attrs.iconBackground || '',
                '--aj-list-icon-gap': (attrs.iconGap || 12) + 'px',
                '--aj-list-item-gap': (attrs.itemGap || 10) + 'px'
            });

            return el('ul', Object.assign({
                'data-icon-type': attrs.iconType || 'icon',
                'data-icon': attrs.icon || '→',
                'data-icon-image': attrs.iconImageUrl || ''
            }, styledProps('aj-icon-list aj-icon-list--' + (attrs.layout || 'stack'), attrs), { style: listStyle }), el(InnerBlocks.Content));
        }
    });

    registerBlockType('ajnanda/icon-list-item', {
        title: __('AJ List Item', 'ajnanda'),
        parent: ['ajnanda/icon-list'],
        category: category,
        icon: 'editor-ul',
        attributes: withStyleAttributes({
            content: { type: 'string', source: 'html', selector: '.aj-icon-list-item__content', default: 'List item' },
            iconType: { type: 'string', default: 'inherit' },
            icon: { type: 'string', default: '' },
            iconImageUrl: { type: 'string', default: '' },
            linkEnabled: { type: 'boolean', default: false },
            url: { type: 'string', default: '' }
        }),
        edit: function(props) {
            var attrs = props.attributes;

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Icon', 'ajnanda'), initialOpen: true },
                        segmented(__('Type', 'ajnanda'), attrs.iconType || 'inherit', [
                            { label: __('Inherit', 'ajnanda'), value: 'inherit' },
                            { label: __('Icon', 'ajnanda'), value: 'icon' },
                            { label: __('Image', 'ajnanda'), value: 'image' },
                            { label: __('None', 'ajnanda'), value: 'none' }
                        ], function(value) { props.setAttributes({ iconType: value }); }),
                        attrs.iconType === 'icon' ? field(__('Icon', 'ajnanda'), attrs.icon, function(value) { props.setAttributes({ icon: value }); }, '→') : null,
                        attrs.iconType === 'image' ? el('div', { className: 'aj-image-control' },
                            attrs.iconImageUrl ? el('img', { src: attrs.iconImageUrl, alt: '' }) : el('div', { className: 'aj-image-control__empty' }, '+'),
                            el(MediaUploadCheck, {}, el(MediaUpload, {
                                onSelect: function(media) { props.setAttributes({ iconImageUrl: media.url }); },
                                allowedTypes: ['image'],
                                render: function(obj) { return el(Button, { variant: 'secondary', onClick: obj.open }, attrs.iconImageUrl ? __('Replace Image', 'ajnanda') : __('Choose Image', 'ajnanda')); }
                            }))
                        ) : null,
                        el(ToggleControl, { label: __('Link', 'ajnanda'), checked: !!attrs.linkEnabled, onChange: function(value) { props.setAttributes({ linkEnabled: value }); } }),
                        attrs.linkEnabled ? urlField(attrs.url, function(value) { props.setAttributes({ url: value }); }) : null
                    ),
                    el(PanelBody, { title: __('Advanced', 'ajnanda'), initialOpen: false }, commonControls(props))
                ),
                el('li', styledProps('aj-icon-list-item', attrs),
                    el('span', { className: 'aj-icon-list-item__marker', 'data-icon-type': attrs.iconType || 'inherit', 'data-icon': attrs.icon || '', 'data-icon-image': attrs.iconImageUrl || '', style: attrs.iconType === 'image' && attrs.iconImageUrl ? { backgroundImage: 'url(' + attrs.iconImageUrl + ')' } : {} }),
                    el(RichText, { tagName: attrs.linkEnabled ? 'a' : 'span', className: 'aj-icon-list-item__content', href: attrs.linkEnabled ? attrs.url || '#' : undefined, value: attrs.content, placeholder: __('List item', 'ajnanda'), onChange: function(value) { props.setAttributes({ content: value }); } })
                )
            );
        },
        save: function(props) {
            var attrs = props.attributes;

            return el('li', styledProps('aj-icon-list-item', attrs),
                el('span', { className: 'aj-icon-list-item__marker', 'data-icon-type': attrs.iconType || 'inherit', 'data-icon': attrs.icon || '', 'data-icon-image': attrs.iconImageUrl || '' }),
                el(RichText.Content, { tagName: attrs.linkEnabled ? 'a' : 'span', className: 'aj-icon-list-item__content', href: attrs.linkEnabled ? attrs.url || '#' : undefined, value: attrs.content })
            );
        }
    });

    registerBlockType('ajnanda/counter', {
        title: __('AJ Counter', 'ajnanda'),
        category: category,
        icon: 'dashboard',
        attributes: withStyleAttributes({ value: { type: 'number', default: 100 }, label: { type: 'string', default: 'Counter' } }),
        edit: function(props) {
            return el(Fragment, {}, inspector(controlsWithCommon(props, [el(RangeControl, { label: __('Value', 'ajnanda'), min: 0, max: 10000, value: props.attributes.value, onChange: function(value) { props.setAttributes({ value: value }); } }), field(__('Label', 'ajnanda'), props.attributes.label, function(value) { props.setAttributes({ label: value }); })])), el('div', styledProps('aj-counter', props.attributes), el('strong', {}, props.attributes.value), el('span', {}, props.attributes.label)));
        },
        save: function(props) {
            return el('div', styledProps('aj-counter', props.attributes), el('strong', {}, props.attributes.value), el('span', {}, props.attributes.label));
        }
    });

    registerBlockType('ajnanda/progress-bar', {
        title: __('AJ Progress Bar', 'ajnanda'),
        category: category,
        icon: 'chart-bar',
        attributes: withStyleAttributes({ value: { type: 'number', default: 65 }, label: { type: 'string', default: 'Progress' } }),
        edit: function(props) {
            return el(Fragment, {}, inspector(controlsWithCommon(props, el(RangeControl, { label: __('Percent', 'ajnanda'), min: 0, max: 100, value: props.attributes.value, onChange: function(value) { props.setAttributes({ value: value }); } }))), el('div', styledProps('aj-progress', props.attributes), el('span', {}, props.attributes.label), el('div', { className: 'aj-progress__track' }, el('i', { style: { width: props.attributes.value + '%' } }))));
        },
        save: function(props) {
            return el('div', styledProps('aj-progress', props.attributes), el('span', {}, props.attributes.label), el('div', { className: 'aj-progress__track' }, el('i', { style: { width: props.attributes.value + '%' } })));
        }
    });

    registerBlockType('ajnanda/countdown', {
        title: __('AJ Countdown', 'ajnanda'),
        category: category,
        icon: 'clock',
        attributes: withStyleAttributes({ label: { type: 'string', default: 'Countdown' }, date: { type: 'string', default: '' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, [field(__('Label', 'ajnanda'), props.attributes.label, function(value) { props.setAttributes({ label: value }); }), field(__('Target date', 'ajnanda'), props.attributes.date, function(value) { props.setAttributes({ date: value }); }, '2026-12-31')])),
                el('div', styledProps('aj-countdown', props.attributes), el('strong', {}, props.attributes.date || 'YYYY-MM-DD'), el('span', {}, props.attributes.label))
            );
        },
        save: function(props) {
            return el('div', Object.assign({ 'data-target-date': props.attributes.date || '' }, styledProps('aj-countdown', props.attributes)), el('strong', {}, props.attributes.date || 'YYYY-MM-DD'), el('span', {}, props.attributes.label));
        }
    });

    registerBlockType('ajnanda/star-ratings', {
        title: __('AJ Star Ratings', 'ajnanda'),
        category: category,
        icon: 'star-half',
        attributes: withStyleAttributes({ rating: { type: 'number', default: 5 }, label: { type: 'string', default: '5.0' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, [el(RangeControl, { label: __('Rating', 'ajnanda'), min: 1, max: 5, value: props.attributes.rating, onChange: function(value) { props.setAttributes({ rating: value }); } }), field(__('Label', 'ajnanda'), props.attributes.label, function(value) { props.setAttributes({ label: value }); })])),
                el('div', Object.assign({ 'aria-label': props.attributes.label }, styledProps('aj-stars', props.attributes)), '★★★★★'.slice(0, props.attributes.rating), el('span', {}, props.attributes.label))
            );
        },
        save: function(props) {
            return el('div', Object.assign({ 'aria-label': props.attributes.label }, styledProps('aj-stars', props.attributes)), '★★★★★'.slice(0, props.attributes.rating), el('span', {}, props.attributes.label));
        }
    });

    function dynamicBlock(name, title, icon, attrs, controls) {
        registerBlockType(name, {
            title: title,
            category: category,
            icon: icon,
            attributes: attrs || {},
            edit: function(props) {
                return el(Fragment, {}, controls ? inspector(controls(props)) : null, ServerSideRender ? el(ServerSideRender, { block: name, attributes: props.attributes }) : el('div', { className: 'aj-block aj-placeholder' }, title));
            },
            save: function() {
                return null;
            }
        });
    }

    function postAttrs(defaultCount) {
        return { count: { type: 'number', default: defaultCount }, showExcerpt: { type: 'boolean', default: true }, showImage: { type: 'boolean', default: true }, buttonText: { type: 'string', default: 'Read More' }, order: { type: 'string', default: 'desc' }, orderBy: { type: 'string', default: 'date' }, columns: { type: 'number', default: 3 } };
    }

    function postControls(props) {
        return [
            el(RangeControl, { label: __('Post count', 'ajnanda'), min: 1, max: 12, value: props.attributes.count, onChange: function(value) { props.setAttributes({ count: value }); } }),
            el(RangeControl, { label: __('Columns', 'ajnanda'), min: 1, max: 4, value: props.attributes.columns || 3, onChange: function(value) { props.setAttributes({ columns: value }); } }),
            el(SelectControl, { label: __('Order by', 'ajnanda'), value: props.attributes.orderBy || 'date', options: [{ label: __('Date', 'ajnanda'), value: 'date' }, { label: __('Title', 'ajnanda'), value: 'title' }, { label: __('Menu order', 'ajnanda'), value: 'menu_order' }], onChange: function(value) { props.setAttributes({ orderBy: value }); } }),
            el(SelectControl, { label: __('Order', 'ajnanda'), value: props.attributes.order || 'desc', options: [{ label: __('Descending', 'ajnanda'), value: 'desc' }, { label: __('Ascending', 'ajnanda'), value: 'asc' }], onChange: function(value) { props.setAttributes({ order: value }); } }),
            el(ToggleControl, { label: __('Show featured image', 'ajnanda'), checked: props.attributes.showImage !== false, onChange: function(value) { props.setAttributes({ showImage: value }); } }),
            el(ToggleControl, { label: __('Show excerpt', 'ajnanda'), checked: !!props.attributes.showExcerpt, onChange: function(value) { props.setAttributes({ showExcerpt: value }); } }),
            field(__('Button text', 'ajnanda'), props.attributes.buttonText, function(value) { props.setAttributes({ buttonText: value }); })
        ];
    }

    dynamicBlock('ajnanda/posts', __('AJ Posts', 'ajnanda'), 'admin-post', postAttrs(3), postControls);
    dynamicBlock('ajnanda/post-grid', __('AJ Post Grid', 'ajnanda'), 'grid-view', postAttrs(6), postControls);
    dynamicBlock('ajnanda/post-carousel', __('AJ Post Carousel Placeholder', 'ajnanda'), 'images-alt2', Object.assign(postAttrs(6), { autoplay: { type: 'boolean', default: false }, delay: { type: 'number', default: 4 } }), function(props) {
        return postControls(props).concat([el(ToggleControl, { label: __('Autoplay', 'ajnanda'), checked: !!props.attributes.autoplay, onChange: function(value) { props.setAttributes({ autoplay: value }); } }), el(RangeControl, { label: __('Delay seconds', 'ajnanda'), min: 1, max: 12, value: props.attributes.delay || 4, onChange: function(value) { props.setAttributes({ delay: value }); } })]);
    });
    dynamicBlock('ajnanda/post-timeline', __('AJ Post Timeline', 'ajnanda'), 'backup', Object.assign(postAttrs(5), { dateFormat: { type: 'string', default: 'M j, Y' } }), function(props) {
        return postControls(props).concat(field(__('Date format', 'ajnanda'), props.attributes.dateFormat, function(value) { props.setAttributes({ dateFormat: value }); }, 'M j, Y'));
    });
    dynamicBlock('ajnanda/search', __('AJ Search', 'ajnanda'), 'search', {
        placeholder: { type: 'string', default: 'Search...' },
        buttonText: { type: 'string', default: 'Search' },
        layout: { type: 'string', default: 'inline' },
        buttonPosition: { type: 'string', default: 'right' }
    }, function(props) {
        return [
            field(__('Placeholder', 'ajnanda'), props.attributes.placeholder, function(value) { props.setAttributes({ placeholder: value }); }),
            field(__('Button text', 'ajnanda'), props.attributes.buttonText, function(value) { props.setAttributes({ buttonText: value }); }),
            el(SelectControl, {
                label: __('Layout', 'ajnanda'),
                value: props.attributes.layout || 'inline',
                options: [
                    { label: __('Inline', 'ajnanda'), value: 'inline' },
                    { label: __('Stacked', 'ajnanda'), value: 'stacked' }
                ],
                onChange: function(value) { props.setAttributes({ layout: value }); }
            }),
            el(SelectControl, {
                label: __('Button position', 'ajnanda'),
                value: props.attributes.buttonPosition || 'right',
                options: [
                    { label: __('Right', 'ajnanda'), value: 'right' },
                    { label: __('Left', 'ajnanda'), value: 'left' }
                ],
                onChange: function(value) { props.setAttributes({ buttonPosition: value }); }
            })
        ];
    });
    dynamicBlock('ajnanda/nav-menu', __('AJ Menu/Nav Menu', 'ajnanda'), 'menu', {
        menuLocation: { type: 'string', default: 'primary' },
        layout: { type: 'string', default: 'horizontal' },
        depth: { type: 'number', default: 2 },
        dropdownOnHover: { type: 'boolean', default: true }
    }, function(props) {
        return [
            el(SelectControl, { label: __('Menu location', 'ajnanda'), value: props.attributes.menuLocation, options: [{ label: 'Primary', value: 'primary' }, { label: 'Footer', value: 'footer' }], onChange: function(value) { props.setAttributes({ menuLocation: value }); } }),
            el(SelectControl, { label: __('Layout', 'ajnanda'), value: props.attributes.layout || 'horizontal', options: [{ label: __('Horizontal', 'ajnanda'), value: 'horizontal' }, { label: __('Vertical', 'ajnanda'), value: 'vertical' }], onChange: function(value) { props.setAttributes({ layout: value }); } }),
            el(RangeControl, { label: __('Menu depth', 'ajnanda'), min: 1, max: 4, value: props.attributes.depth || 2, onChange: function(value) { props.setAttributes({ depth: value }); } }),
            el(ToggleControl, { label: __('Open submenu on hover', 'ajnanda'), checked: props.attributes.dropdownOnHover !== false, onChange: function(value) { props.setAttributes({ dropdownOnHover: value }); } })
        ];
    });
    dynamicBlock('ajnanda/table-of-contents', __('AJ Table of Contents', 'ajnanda'), 'list-view', {
        title: { type: 'string', default: 'On this page' },
        minLevel: { type: 'number', default: 2 },
        maxLevel: { type: 'number', default: 3 },
        ordered: { type: 'boolean', default: true },
        collapsible: { type: 'boolean', default: false }
    }, function(props) {
        return [
            field(__('Title', 'ajnanda'), props.attributes.title, function(value) { props.setAttributes({ title: value }); }),
            el(RangeControl, { label: __('Minimum heading level', 'ajnanda'), min: 1, max: 6, value: props.attributes.minLevel || 2, onChange: function(value) { props.setAttributes({ minLevel: value }); } }),
            el(RangeControl, { label: __('Maximum heading level', 'ajnanda'), min: 1, max: 6, value: props.attributes.maxLevel || 3, onChange: function(value) { props.setAttributes({ maxLevel: value }); } }),
            el(ToggleControl, { label: __('Ordered list', 'ajnanda'), checked: props.attributes.ordered !== false, onChange: function(value) { props.setAttributes({ ordered: value }); } }),
            el(ToggleControl, { label: __('Collapsible placeholder', 'ajnanda'), checked: !!props.attributes.collapsible, onChange: function(value) { props.setAttributes({ collapsible: value }); } })
        ];
    });
    dynamicBlock('ajnanda/taxonomy-list', __('AJ Taxonomy List', 'ajnanda'), 'category', {
        taxonomy: { type: 'string', default: 'category' },
        layout: { type: 'string', default: 'pills' },
        hideEmpty: { type: 'boolean', default: false },
        showCount: { type: 'boolean', default: false }
    }, function(props) {
        return [
            el(SelectControl, { label: __('Taxonomy', 'ajnanda'), value: props.attributes.taxonomy, options: [{ label: 'Categories', value: 'category' }, { label: 'Tags', value: 'post_tag' }], onChange: function(value) { props.setAttributes({ taxonomy: value }); } }),
            el(SelectControl, { label: __('Layout', 'ajnanda'), value: props.attributes.layout || 'pills', options: [{ label: __('Pills', 'ajnanda'), value: 'pills' }, { label: __('List', 'ajnanda'), value: 'list' }, { label: __('Inline', 'ajnanda'), value: 'inline' }], onChange: function(value) { props.setAttributes({ layout: value }); } }),
            el(ToggleControl, { label: __('Hide empty terms', 'ajnanda'), checked: !!props.attributes.hideEmpty, onChange: function(value) { props.setAttributes({ hideEmpty: value }); } }),
            el(ToggleControl, { label: __('Show post count', 'ajnanda'), checked: !!props.attributes.showCount, onChange: function(value) { props.setAttributes({ showCount: value }); } })
        ];
    });
    dynamicBlock('ajnanda/login-placeholder', __('AJ Login Placeholder', 'ajnanda'), 'admin-users', {
        loggedOutText: { type: 'string', default: 'Login area placeholder.' },
        loginText: { type: 'string', default: 'Log In' },
        logoutText: { type: 'string', default: 'Log Out' }
    }, function(props) {
        return [
            field(__('Logged out text', 'ajnanda'), props.attributes.loggedOutText, function(value) { props.setAttributes({ loggedOutText: value }); }),
            field(__('Login button text', 'ajnanda'), props.attributes.loginText, function(value) { props.setAttributes({ loginText: value }); }),
            field(__('Logout button text', 'ajnanda'), props.attributes.logoutText, function(value) { props.setAttributes({ logoutText: value }); })
        ];
    });
})(window.wp);
