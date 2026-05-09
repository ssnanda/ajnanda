(function(wp) {
    if (!wp || !wp.blocks || !wp.element || !wp.blockEditor || !wp.components) {
        return;
    }

    var registerBlockType = wp.blocks.registerBlockType;
    var createElement = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var __ = wp.i18n.__;
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
    var ServerSideRender = wp.serverSideRender;
    var category = 'ajnanda-blocks';

    function el(tag, props) {
        var children = Array.prototype.slice.call(arguments, 2);
        return createElement.apply(null, [tag, props || {}].concat(children));
    }

    function inspector(children) {
        return el(InspectorControls, {}, el(PanelBody, { title: __('AJNanda Settings', 'ncllc-pro'), initialOpen: true }, children));
    }

    function classNames() {
        return Array.prototype.slice.call(arguments).filter(Boolean).join(' ');
    }

    function field(label, value, onChange, placeholder) {
        return el(TextControl, { label: label, value: value || '', placeholder: placeholder || '', onChange: onChange });
    }

    function urlField(value, onChange) {
        return el('div', { className: 'aj-url-control' }, el('span', {}, __('Link', 'ncllc-pro')), el(URLInputButton, { url: value || '', onChange: onChange }));
    }

    function withStyleAttributes(attrs) {
        return Object.assign({
            alignText: { type: 'string', default: '' },
            backgroundColor: { type: 'string', default: '' },
            textColor: { type: 'string', default: '' },
            borderColor: { type: 'string', default: '' },
            borderRadius: { type: 'number', default: 0 },
            padding: { type: 'number', default: 0 },
            marginTop: { type: 'number', default: 0 },
            marginBottom: { type: 'number', default: 0 }
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
            style.borderStyle = 'solid';
            style.borderWidth = '1px';
        }
        if (attrs.borderRadius) {
            style.borderRadius = attrs.borderRadius + 'px';
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

        return style;
    }

    function styledProps(baseClass, attrs, extraClass) {
        return {
            className: classNames('aj-block', baseClass, extraClass, attrs && attrs.alignText ? 'has-text-align-' + attrs.alignText : ''),
            style: blockStyle(attrs)
        };
    }

    function commonControls(props) {
        var attrs = props.attributes || {};

        return [
            el(SelectControl, {
                label: __('Alignment', 'ncllc-pro'),
                value: attrs.alignText || '',
                options: [
                    { label: __('Default', 'ncllc-pro'), value: '' },
                    { label: __('Left', 'ncllc-pro'), value: 'left' },
                    { label: __('Center', 'ncllc-pro'), value: 'center' },
                    { label: __('Right', 'ncllc-pro'), value: 'right' }
                ],
                onChange: function(value) { props.setAttributes({ alignText: value }); }
            }),
            field(__('Background color', 'ncllc-pro'), attrs.backgroundColor, function(value) { props.setAttributes({ backgroundColor: value }); }, '#ffffff'),
            field(__('Text color', 'ncllc-pro'), attrs.textColor, function(value) { props.setAttributes({ textColor: value }); }, '#111827'),
            field(__('Border color', 'ncllc-pro'), attrs.borderColor, function(value) { props.setAttributes({ borderColor: value }); }, '#e5e7eb'),
            el(RangeControl, { label: __('Border radius', 'ncllc-pro'), min: 0, max: 80, value: attrs.borderRadius || 0, onChange: function(value) { props.setAttributes({ borderRadius: value }); } }),
            el(RangeControl, { label: __('Padding', 'ncllc-pro'), min: 0, max: 120, value: attrs.padding || 0, onChange: function(value) { props.setAttributes({ padding: value }); } }),
            el(RangeControl, { label: __('Margin top', 'ncllc-pro'), min: 0, max: 160, value: attrs.marginTop || 0, onChange: function(value) { props.setAttributes({ marginTop: value }); } }),
            el(RangeControl, { label: __('Margin bottom', 'ncllc-pro'), min: 0, max: 160, value: attrs.marginBottom || 0, onChange: function(value) { props.setAttributes({ marginBottom: value }); } })
        ];
    }

    function controlsWithCommon(props, controls) {
        controls = controls ? (Array.isArray(controls) ? controls : [controls]) : [];
        return controls.concat(commonControls(props));
    }

    function registerContainerBlock(name, title, description, className, template) {
        registerBlockType(name, {
            title: title,
            description: description,
            category: category,
            icon: 'screenoptions',
            supports: { align: ['wide', 'full'], anchor: true },
            attributes: withStyleAttributes({ className: { type: 'string' } }),
            edit: function(props) {
                return el(Fragment, {},
                    inspector(controlsWithCommon(props)),
                    el('div', styledProps(className, props.attributes, props.attributes.className), el(InnerBlocks, { template: template || [], templateLock: false }))
                );
            },
            save: function(props) {
                return el('div', styledProps(className, props.attributes, props.attributes.className), el(InnerBlocks.Content));
            }
        });
    }

    registerContainerBlock('ajnanda/div-block', __('AJ Div Block', 'ncllc-pro'), __('Simple wrapper block.', 'ncllc-pro'), 'aj-div');
    registerContainerBlock('ajnanda/flexbox', __('AJ Flexbox', 'ncllc-pro'), __('Flexible row or column layout.', 'ncllc-pro'), 'aj-flexbox', [['core/paragraph', { placeholder: __('Flex item', 'ncllc-pro') }]]);
    registerContainerBlock('ajnanda/container', __('AJ Container', 'ncllc-pro'), __('Constrained content container.', 'ncllc-pro'), 'aj-container', [['core/paragraph', { placeholder: __('Container content', 'ncllc-pro') }]]);
    registerContainerBlock('ajnanda/grid', __('AJ Grid', 'ncllc-pro'), __('Responsive grid layout.', 'ncllc-pro'), 'aj-grid', [['core/group', { className: 'aj-card' }], ['core/group', { className: 'aj-card' }], ['core/group', { className: 'aj-card' }]]);

    registerBlockType('ajnanda/heading', {
        title: __('AJ Heading', 'ncllc-pro'),
        category: category,
        icon: 'heading',
        attributes: withStyleAttributes({ content: { type: 'string', source: 'html', selector: 'h2' }, level: { type: 'number', default: 2 } }),
        edit: function(props) {
            var level = props.attributes.level || 2;
            return el(Fragment, {},
                inspector(controlsWithCommon(props, el(RangeControl, { label: __('Level', 'ncllc-pro'), min: 1, max: 6, value: level, onChange: function(value) { props.setAttributes({ level: value }); } }))),
                el(RichText, Object.assign({ tagName: 'h' + level, value: props.attributes.content, placeholder: __('Heading', 'ncllc-pro'), onChange: function(value) { props.setAttributes({ content: value }); } }, styledProps('aj-heading', props.attributes)))
            );
        },
        save: function(props) {
            return el(RichText.Content, Object.assign({ tagName: 'h' + (props.attributes.level || 2), value: props.attributes.content }, styledProps('aj-heading', props.attributes)));
        }
    });

    registerBlockType('ajnanda/text-editor', {
        title: __('AJ Paragraph/Text Editor', 'ncllc-pro'),
        category: category,
        icon: 'editor-paragraph',
        attributes: withStyleAttributes({ content: { type: 'string', source: 'html', selector: 'p' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props)),
                el(RichText, Object.assign({ tagName: 'p', value: props.attributes.content, placeholder: __('Text', 'ncllc-pro'), onChange: function(value) { props.setAttributes({ content: value }); } }, styledProps('aj-text', props.attributes)))
            );
        },
        save: function(props) {
            return el(RichText.Content, Object.assign({ tagName: 'p', value: props.attributes.content }, styledProps('aj-text', props.attributes)));
        }
    });

    registerBlockType('ajnanda/image', {
        title: __('AJ Image', 'ncllc-pro'),
        category: category,
        icon: 'format-image',
        attributes: withStyleAttributes({ url: { type: 'string' }, alt: { type: 'string' } }),
        edit: function(props) {
            var attrs = props.attributes;
            return el(Fragment, {},
                inspector(controlsWithCommon(props, field(__('Alt text', 'ncllc-pro'), attrs.alt, function(value) { props.setAttributes({ alt: value }); }))),
                el('figure', styledProps('aj-image', attrs),
                    attrs.url ? el('img', { src: attrs.url, alt: attrs.alt || '' }) : null,
                    el(MediaUploadCheck, {}, el(MediaUpload, {
                        onSelect: function(media) { props.setAttributes({ url: media.url, alt: media.alt || '' }); },
                        allowedTypes: ['image'],
                        render: function(obj) { return el(Button, { variant: attrs.url ? 'secondary' : 'primary', onClick: obj.open }, attrs.url ? __('Replace Image', 'ncllc-pro') : __('Select Image', 'ncllc-pro')); }
                    }))
                )
            );
        },
        save: function(props) {
            return el('figure', styledProps('aj-image', props.attributes), props.attributes.url ? el('img', { src: props.attributes.url, alt: props.attributes.alt || '' }) : null);
        }
    });

    registerBlockType('ajnanda/button', {
        title: __('AJ Button', 'ncllc-pro'),
        category: category,
        icon: 'button',
        attributes: withStyleAttributes({ text: { type: 'string', default: 'Button' }, url: { type: 'string' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, urlField(props.attributes.url, function(value) { props.setAttributes({ url: value }); }))),
                el(RichText, Object.assign({ tagName: 'a', value: props.attributes.text, placeholder: __('Button text', 'ncllc-pro'), onChange: function(value) { props.setAttributes({ text: value }); } }, styledProps('aj-button', props.attributes)))
            );
        },
        save: function(props) {
            return el('a', Object.assign({ href: props.attributes.url || '#' }, styledProps('aj-button', props.attributes)), props.attributes.text);
        }
    });

    registerBlockType('ajnanda/divider', {
        title: __('AJ Divider', 'ncllc-pro'),
        category: category,
        icon: 'minus',
        attributes: withStyleAttributes({ label: { type: 'string', default: '' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, field(__('Optional label', 'ncllc-pro'), props.attributes.label, function(value) { props.setAttributes({ label: value }); }))),
                el('div', styledProps('aj-divider', props.attributes), props.attributes.label ? el('span', {}, props.attributes.label) : null)
            );
        },
        save: function(props) {
            return el('div', styledProps('aj-divider', props.attributes), props.attributes.label ? el('span', {}, props.attributes.label) : null);
        }
    });

    registerBlockType('ajnanda/spacer', {
        title: __('AJ Spacer', 'ncllc-pro'),
        category: category,
        icon: 'image-flip-vertical',
        attributes: withStyleAttributes({ height: { type: 'number', default: 48 } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, el(RangeControl, { label: __('Height', 'ncllc-pro'), min: 8, max: 320, value: props.attributes.height, onChange: function(value) { props.setAttributes({ height: value }); } }))),
                el('div', Object.assign(styledProps('aj-spacer', props.attributes), { style: Object.assign(blockStyle(props.attributes), { height: props.attributes.height + 'px' }) }))
            );
        },
        save: function(props) {
            return el('div', Object.assign(styledProps('aj-spacer', props.attributes), { style: Object.assign(blockStyle(props.attributes), { height: props.attributes.height + 'px' }) }));
        }
    });

    registerBlockType('ajnanda/icon', {
        title: __('AJ Icon', 'ncllc-pro'),
        category: category,
        icon: 'star-filled',
        attributes: withStyleAttributes({ icon: { type: 'string', default: '★' }, label: { type: 'string', default: '' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, [field(__('Icon character', 'ncllc-pro'), props.attributes.icon, function(value) { props.setAttributes({ icon: value }); }), field(__('Label', 'ncllc-pro'), props.attributes.label, function(value) { props.setAttributes({ label: value }); })])),
                el('span', Object.assign({ 'aria-label': props.attributes.label || undefined }, styledProps('aj-icon', props.attributes)), props.attributes.icon)
            );
        },
        save: function(props) {
            return el('span', Object.assign({ 'aria-label': props.attributes.label || undefined }, styledProps('aj-icon', props.attributes)), props.attributes.icon);
        }
    });

    registerBlockType('ajnanda/svg', {
        title: __('AJ SVG', 'ncllc-pro'),
        category: category,
        icon: 'admin-customizer',
        attributes: withStyleAttributes({ svg: { type: 'string', default: '<svg viewBox="0 0 80 80" role="img" aria-label="Circle"><circle cx="40" cy="40" r="32"/></svg>' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, el(TextareaControl, { label: __('SVG markup', 'ncllc-pro'), value: props.attributes.svg, onChange: function(value) { props.setAttributes({ svg: value }); } }))),
                ServerSideRender ? el(ServerSideRender, { block: 'ajnanda/svg', attributes: props.attributes }) : el('div', { className: 'aj-block aj-svg' }, __('SVG preview', 'ncllc-pro'))
            );
        },
        save: function(props) {
            return null;
        }
    });

    function mediaEmbedBlock(name, title, icon, className, placeholder) {
        registerBlockType(name, {
            title: title,
            category: category,
            icon: icon,
            attributes: withStyleAttributes({ url: { type: 'string' } }),
            edit: function(props) {
                return el(Fragment, {}, inspector(controlsWithCommon(props, field(__('URL', 'ncllc-pro'), props.attributes.url, function(value) { props.setAttributes({ url: value }); }, placeholder))), el('div', styledProps(className, props.attributes), props.attributes.url || placeholder));
            },
            save: function(props) {
                return el('div', styledProps(className, props.attributes), props.attributes.url ? el('iframe', { src: props.attributes.url, loading: 'lazy', allowFullScreen: true, title: title }) : null);
            }
        });
    }

    mediaEmbedBlock('ajnanda/youtube', __('AJ YouTube', 'ncllc-pro'), 'video-alt3', 'aj-embed', 'https://www.youtube.com/embed/...');
    mediaEmbedBlock('ajnanda/video', __('AJ Video', 'ncllc-pro'), 'format-video', 'aj-embed', 'Video embed URL');
    mediaEmbedBlock('ajnanda/google-maps', __('AJ Google Maps Embed', 'ncllc-pro'), 'location-alt', 'aj-embed', 'Google Maps embed URL');

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

    function simpleCardBlock(name, title, icon, className, template) {
        registerBlockType(name, {
            title: title,
            category: category,
            icon: icon,
            supports: { align: ['wide', 'full'], anchor: true },
            attributes: withStyleAttributes({}),
            edit: function(props) {
                return el(Fragment, {},
                    inspector(controlsWithCommon(props)),
                    el('section', styledProps(className, props.attributes), el(InnerBlocks, { template: template || [], templateLock: false }))
                );
            },
            save: function(props) {
                return el('section', styledProps(className, props.attributes), el(InnerBlocks.Content));
            }
        });
    }

    simpleCardBlock('ajnanda/info-box', __('AJ Info Box', 'ncllc-pro'), 'welcome-widgets-menus', 'aj-info-box', [['ajnanda/icon'], ['core/heading', { level: 3, content: 'Info Box' }], ['core/paragraph', { placeholder: 'Add supporting text.' }]]);
    simpleCardBlock('ajnanda/call-to-action', __('AJ Call To Action', 'ncllc-pro'), 'megaphone', 'aj-call-to-action', [['core/heading', { level: 2, content: 'Ready to get started?' }], ['core/paragraph', { placeholder: 'Add a short call to action.' }], ['ajnanda/button', { text: 'Get Started' }]]);
    simpleCardBlock('ajnanda/buttons', __('AJ Buttons', 'ncllc-pro'), 'button', 'aj-buttons', [['core/buttons', {}, [['core/button', { text: 'Button' }], ['core/button', { text: 'Button' }]]]]);
    simpleCardBlock('ajnanda/marketing-button', __('AJ Marketing Button', 'ncllc-pro'), 'external', 'aj-marketing-button', [['core/buttons', { layout: { type: 'flex', justifyContent: 'center' } }, [['core/button', { text: 'Marketing Button' }]]]]);
    editableTextBlock('ajnanda/blockquote', __('AJ Blockquote', 'ncllc-pro'), 'format-quote', 'blockquote', 'aj-blockquote', 'Add a quote or testimonial.', 'Quote');
    simpleCardBlock('ajnanda/content-timeline', __('AJ Content Timeline', 'ncllc-pro'), 'networking', 'aj-timeline', [['core/heading', { level: 3, content: 'Timeline Item' }], ['core/paragraph', { placeholder: 'Add milestone details.' }]]);

    registerBlockType('ajnanda/faq', {
        title: __('AJ FAQ', 'ncllc-pro'),
        description: __('Add accordions and FAQ schema to your page.', 'ncllc-pro'),
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
                    el(PanelBody, { title: __('General', 'ncllc-pro'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Layout', 'ncllc-pro'),
                            value: attrs.layout,
                            options: [
                                { label: __('Accordion', 'ncllc-pro'), value: 'accordion' },
                                { label: __('Grid', 'ncllc-pro'), value: 'grid' }
                            ],
                            onChange: function(value) { props.setAttributes({ layout: value }); }
                        }),
                        attrs.layout === 'grid' ? el(RangeControl, { label: __('Grid columns', 'ncllc-pro'), min: 1, max: 4, value: attrs.columns || 2, onChange: function(value) { props.setAttributes({ columns: value }); } }) : null,
                        el(ToggleControl, { label: __('Collapse other items', 'ncllc-pro'), checked: !!attrs.collapseOtherItems, onChange: function(value) { props.setAttributes({ collapseOtherItems: value }); } }),
                        el(ToggleControl, { label: __('Expand First Item', 'ncllc-pro'), checked: !!attrs.expandFirstItem, onChange: function(value) { props.setAttributes({ expandFirstItem: value }); } }),
                        el(ToggleControl, { label: __('Enable Toggle', 'ncllc-pro'), checked: !!attrs.enableToggle, onChange: function(value) { props.setAttributes({ enableToggle: value }); } }),
                        el(ToggleControl, { label: __('Enable Schema Support', 'ncllc-pro'), checked: !!attrs.enableSchema, onChange: function(value) { props.setAttributes({ enableSchema: value }); } }),
                        el(ToggleControl, { label: __('Enable Separator', 'ncllc-pro'), checked: !!attrs.enableSeparator, onChange: function(value) { props.setAttributes({ enableSeparator: value }); } }),
                        el(SelectControl, {
                            label: __('Question Tag', 'ncllc-pro'),
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
                    el(PanelBody, { title: __('Icon', 'ncllc-pro'), initialOpen: false },
                        field(__('Icon', 'ncllc-pro'), attrs.icon, function(value) { props.setAttributes({ icon: value }); }, '+'),
                        field(__('Active Icon', 'ncllc-pro'), attrs.activeIcon, function(value) { props.setAttributes({ activeIcon: value }); }, '-'),
                        el(SelectControl, {
                            label: __('Icon Position', 'ncllc-pro'),
                            value: attrs.iconPosition,
                            options: [
                                { label: __('Left', 'ncllc-pro'), value: 'left' },
                                { label: __('Right', 'ncllc-pro'), value: 'right' }
                            ],
                            onChange: function(value) { props.setAttributes({ iconPosition: value }); }
                        })
                    ),
                    el(PanelBody, { title: __('Style', 'ncllc-pro'), initialOpen: false },
                        field(__('Question color', 'ncllc-pro'), attrs.questionColor, function(value) { props.setAttributes({ questionColor: value }); }, '#111827'),
                        field(__('Answer color', 'ncllc-pro'), attrs.answerColor, function(value) { props.setAttributes({ answerColor: value }); }, '#374151'),
                        field(__('Active question color', 'ncllc-pro'), attrs.activeColor, function(value) { props.setAttributes({ activeColor: value }); }, '#2563eb'),
                        field(__('Separator color', 'ncllc-pro'), attrs.separatorColor, function(value) { props.setAttributes({ separatorColor: value }); }, '#e5e7eb'),
                        field(__('Background color', 'ncllc-pro'), attrs.backgroundColor, function(value) { props.setAttributes({ backgroundColor: value }); }, '#ffffff'),
                        field(__('Border color', 'ncllc-pro'), attrs.borderColor, function(value) { props.setAttributes({ borderColor: value }); }, '#e5e7eb'),
                        el(RangeControl, { label: __('Border radius', 'ncllc-pro'), min: 0, max: 40, value: attrs.borderRadius || 0, onChange: function(value) { props.setAttributes({ borderRadius: value }); } }),
                        el(RangeControl, { label: __('Padding', 'ncllc-pro'), min: 0, max: 80, value: attrs.padding || 0, onChange: function(value) { props.setAttributes({ padding: value }); } })
                    ),
                    el(PanelBody, { title: __('Advanced', 'ncllc-pro'), initialOpen: false },
                        el(SelectControl, {
                            label: __('Animation', 'ncllc-pro'),
                            value: attrs.animation,
                            options: [
                                { label: __('None', 'ncllc-pro'), value: 'none' },
                                { label: __('Fade In', 'ncllc-pro'), value: 'fade-in' },
                                { label: __('Slide Up', 'ncllc-pro'), value: 'slide-up' },
                                { label: __('Zoom In', 'ncllc-pro'), value: 'zoom-in' }
                            ],
                            onChange: function(value) { props.setAttributes({ animation: value }); }
                        }),
                        el(RangeControl, { label: __('Margin top', 'ncllc-pro'), min: 0, max: 160, value: attrs.marginTop || 0, onChange: function(value) { props.setAttributes({ marginTop: value }); } }),
                        el(RangeControl, { label: __('Margin bottom', 'ncllc-pro'), min: 0, max: 160, value: attrs.marginBottom || 0, onChange: function(value) { props.setAttributes({ marginBottom: value }); } })
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

    simpleCardBlock('ajnanda/how-to', __('AJ How To', 'ncllc-pro'), 'media-document', 'aj-how-to', [['core/heading', { level: 2, content: 'How To' }], ['core/list', { values: '<li>Step one</li><li>Step two</li><li>Step three</li>' }]]);
    editableTextBlock('ajnanda/inline-notice', __('AJ Inline Notice', 'ncllc-pro'), 'info', 'div', 'aj-inline-notice', 'Add an important notice.', 'Notice');
    simpleCardBlock('ajnanda/modal', __('AJ Modal Placeholder', 'ncllc-pro'), 'welcome-comments', 'aj-modal-placeholder', [['core/heading', { level: 3, content: 'Modal Placeholder' }], ['core/paragraph', { placeholder: 'Static modal content placeholder.' }]]);
    simpleCardBlock('ajnanda/slider', __('AJ Slider Placeholder', 'ncllc-pro'), 'slides', 'aj-slider-placeholder', [['core/image'], ['core/heading', { level: 3, content: 'Slide Title' }], ['core/paragraph', { placeholder: 'Slide text.' }]]);
    simpleCardBlock('ajnanda/lottie-animation', __('AJ Lottie Animation Placeholder', 'ncllc-pro'), 'controls-repeat', 'aj-lottie-placeholder', [['core/paragraph', { content: 'Lottie animation placeholder.' }]]);
    simpleCardBlock('ajnanda/team', __('AJ Team', 'ncllc-pro'), 'groups', 'aj-team', [['core/image'], ['core/heading', { level: 3, content: 'Team Member' }], ['core/paragraph', { content: 'Role or short bio.' }]]);
    simpleCardBlock('ajnanda/testimonials', __('AJ Testimonials', 'ncllc-pro'), 'format-chat', 'aj-testimonials', [['core/quote', { value: 'Add testimonial text.', citation: 'Customer Name' }]]);
    simpleCardBlock('ajnanda/review', __('AJ Review', 'ncllc-pro'), 'star-filled', 'aj-review', [['ajnanda/star-ratings'], ['core/quote', { value: 'Add review text.', citation: 'Reviewer Name' }]]);
    simpleCardBlock('ajnanda/price-list', __('AJ Price List', 'ncllc-pro'), 'money-alt', 'aj-price-list', [['core/list', { values: '<li>Service - $99</li><li>Service - $149</li>' }]]);
    simpleCardBlock('ajnanda/social-share', __('AJ Social Share', 'ncllc-pro'), 'share', 'aj-social-share', [['core/buttons', {}, [['core/button', { text: 'Share' }], ['core/button', { text: 'LinkedIn' }], ['core/button', { text: 'Email' }]]]]);
    simpleCardBlock('ajnanda/separator', __('AJ Separator', 'ncllc-pro'), 'minus', 'aj-separator-block', []);

    registerContainerBlock('ajnanda/form', __('AJ Form', 'ncllc-pro'), __('Static form layout.', 'ncllc-pro'), 'aj-form', [['ajnanda/label'], ['ajnanda/input'], ['ajnanda/submit-button']]);

    function formFieldBlock(name, title, tag, defaults) {
        registerBlockType(name, {
            title: title,
            category: category,
            icon: 'forms',
            attributes: withStyleAttributes(Object.assign({ text: { type: 'string', default: defaults.text || '' }, placeholder: { type: 'string', default: defaults.placeholder || '' }, name: { type: 'string', default: defaults.name || '' } }, defaults.attributes || {})),
            edit: function(props) {
                var attrs = props.attributes;
                return el(Fragment, {},
                    inspector(controlsWithCommon(props, [field(__('Name', 'ncllc-pro'), attrs.name, function(value) { props.setAttributes({ name: value }); }), field(__('Placeholder', 'ncllc-pro'), attrs.placeholder, function(value) { props.setAttributes({ placeholder: value }); })])),
                    tag === 'label' ? el(RichText, Object.assign({ tagName: 'label', value: attrs.text, placeholder: __('Label', 'ncllc-pro'), onChange: function(value) { props.setAttributes({ text: value }); } }, styledProps('aj-label', attrs))) : el(tag, Object.assign({ placeholder: attrs.placeholder, type: defaults.type || undefined, value: '', readOnly: true }, styledProps('aj-field', attrs)))
                );
            },
            save: function(props) {
                var attrs = props.attributes;
                if (tag === 'label') {
                    return el(RichText.Content, Object.assign({ tagName: 'label', value: attrs.text }, styledProps('aj-label', attrs)));
                }
                return el(tag, Object.assign({ name: attrs.name, placeholder: attrs.placeholder, type: defaults.type || undefined }, styledProps('aj-field', attrs)));
            }
        });
    }

    formFieldBlock('ajnanda/input', __('AJ Input', 'ncllc-pro'), 'input', { placeholder: 'Your answer', name: 'field', type: 'text' });
    formFieldBlock('ajnanda/label', __('AJ Label', 'ncllc-pro'), 'label', { text: 'Label' });
    formFieldBlock('ajnanda/text-area', __('AJ Text Area', 'ncllc-pro'), 'textarea', { placeholder: 'Message', name: 'message' });
    formFieldBlock('ajnanda/checkbox', __('AJ Checkbox', 'ncllc-pro'), 'input', { name: 'agree', type: 'checkbox' });

    registerBlockType('ajnanda/submit-button', {
        title: __('AJ Submit Button', 'ncllc-pro'),
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

    registerContainerBlock('ajnanda/tabs', __('AJ Tabs', 'ncllc-pro'), __('Tabbed content placeholder.', 'ncllc-pro'), 'aj-tabs', [['core/heading', { level: 3, content: 'Tab Title' }], ['core/paragraph', { placeholder: 'Tab content' }]]);
    registerContainerBlock('ajnanda/accordion', __('AJ Accordion', 'ncllc-pro'), __('Expandable content layout.', 'ncllc-pro'), 'aj-accordion', [['core/details', { summary: 'Accordion item' }]]);
    registerContainerBlock('ajnanda/image-box', __('AJ Image Box', 'ncllc-pro'), __('Image with text.', 'ncllc-pro'), 'aj-image-box', [['ajnanda/image'], ['ajnanda/heading', { content: 'Image Box' }], ['ajnanda/text-editor']]);
    registerContainerBlock('ajnanda/icon-box', __('AJ Icon Box', 'ncllc-pro'), __('Icon with text.', 'ncllc-pro'), 'aj-icon-box', [['ajnanda/icon'], ['ajnanda/heading', { content: 'Icon Box', level: 3 }], ['ajnanda/text-editor']]);
    registerContainerBlock('ajnanda/basic-gallery', __('AJ Basic Gallery', 'ncllc-pro'), __('Simple image gallery wrapper.', 'ncllc-pro'), 'aj-gallery', [['core/gallery']]);
    registerContainerBlock('ajnanda/image-gallery', __('AJ Image Gallery', 'ncllc-pro'), __('Simple image gallery wrapper.', 'ncllc-pro'), 'aj-gallery', [['core/gallery']]);
    registerContainerBlock('ajnanda/icon-list', __('AJ Icon List', 'ncllc-pro'), __('List with icons.', 'ncllc-pro'), 'aj-icon-list', [['core/list', { values: '<li>List item</li><li>List item</li>' }]]);

    registerBlockType('ajnanda/counter', {
        title: __('AJ Counter', 'ncllc-pro'),
        category: category,
        icon: 'dashboard',
        attributes: withStyleAttributes({ value: { type: 'number', default: 100 }, label: { type: 'string', default: 'Counter' } }),
        edit: function(props) {
            return el(Fragment, {}, inspector(controlsWithCommon(props, [el(RangeControl, { label: __('Value', 'ncllc-pro'), min: 0, max: 10000, value: props.attributes.value, onChange: function(value) { props.setAttributes({ value: value }); } }), field(__('Label', 'ncllc-pro'), props.attributes.label, function(value) { props.setAttributes({ label: value }); })])), el('div', styledProps('aj-counter', props.attributes), el('strong', {}, props.attributes.value), el('span', {}, props.attributes.label)));
        },
        save: function(props) {
            return el('div', styledProps('aj-counter', props.attributes), el('strong', {}, props.attributes.value), el('span', {}, props.attributes.label));
        }
    });

    registerBlockType('ajnanda/progress-bar', {
        title: __('AJ Progress Bar', 'ncllc-pro'),
        category: category,
        icon: 'chart-bar',
        attributes: withStyleAttributes({ value: { type: 'number', default: 65 }, label: { type: 'string', default: 'Progress' } }),
        edit: function(props) {
            return el(Fragment, {}, inspector(controlsWithCommon(props, el(RangeControl, { label: __('Percent', 'ncllc-pro'), min: 0, max: 100, value: props.attributes.value, onChange: function(value) { props.setAttributes({ value: value }); } }))), el('div', styledProps('aj-progress', props.attributes), el('span', {}, props.attributes.label), el('div', { className: 'aj-progress__track' }, el('i', { style: { width: props.attributes.value + '%' } }))));
        },
        save: function(props) {
            return el('div', styledProps('aj-progress', props.attributes), el('span', {}, props.attributes.label), el('div', { className: 'aj-progress__track' }, el('i', { style: { width: props.attributes.value + '%' } })));
        }
    });

    registerBlockType('ajnanda/countdown', {
        title: __('AJ Countdown', 'ncllc-pro'),
        category: category,
        icon: 'clock',
        attributes: withStyleAttributes({ label: { type: 'string', default: 'Countdown' }, date: { type: 'string', default: '' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, [field(__('Label', 'ncllc-pro'), props.attributes.label, function(value) { props.setAttributes({ label: value }); }), field(__('Target date', 'ncllc-pro'), props.attributes.date, function(value) { props.setAttributes({ date: value }); }, '2026-12-31')])),
                el('div', styledProps('aj-countdown', props.attributes), el('strong', {}, props.attributes.date || 'YYYY-MM-DD'), el('span', {}, props.attributes.label))
            );
        },
        save: function(props) {
            return el('div', Object.assign({ 'data-target-date': props.attributes.date || '' }, styledProps('aj-countdown', props.attributes)), el('strong', {}, props.attributes.date || 'YYYY-MM-DD'), el('span', {}, props.attributes.label));
        }
    });

    registerBlockType('ajnanda/star-ratings', {
        title: __('AJ Star Ratings', 'ncllc-pro'),
        category: category,
        icon: 'star-half',
        attributes: withStyleAttributes({ rating: { type: 'number', default: 5 }, label: { type: 'string', default: '5.0' } }),
        edit: function(props) {
            return el(Fragment, {},
                inspector(controlsWithCommon(props, [el(RangeControl, { label: __('Rating', 'ncllc-pro'), min: 1, max: 5, value: props.attributes.rating, onChange: function(value) { props.setAttributes({ rating: value }); } }), field(__('Label', 'ncllc-pro'), props.attributes.label, function(value) { props.setAttributes({ label: value }); })])),
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

    dynamicBlock('ajnanda/posts', __('AJ Posts', 'ncllc-pro'), 'admin-post', { count: { type: 'number', default: 3 }, showExcerpt: { type: 'boolean', default: true }, buttonText: { type: 'string', default: 'Read More' } }, function(props) {
        return [el(RangeControl, { label: __('Post count', 'ncllc-pro'), min: 1, max: 12, value: props.attributes.count, onChange: function(value) { props.setAttributes({ count: value }); } }), el(ToggleControl, { label: __('Show excerpt', 'ncllc-pro'), checked: !!props.attributes.showExcerpt, onChange: function(value) { props.setAttributes({ showExcerpt: value }); } }), field(__('Button text', 'ncllc-pro'), props.attributes.buttonText, function(value) { props.setAttributes({ buttonText: value }); })];
    });
    dynamicBlock('ajnanda/post-grid', __('AJ Post Grid', 'ncllc-pro'), 'grid-view', { count: { type: 'number', default: 6 }, showExcerpt: { type: 'boolean', default: true }, buttonText: { type: 'string', default: 'Read More' } }, function(props) {
        return [el(RangeControl, { label: __('Post count', 'ncllc-pro'), min: 1, max: 12, value: props.attributes.count, onChange: function(value) { props.setAttributes({ count: value }); } }), el(ToggleControl, { label: __('Show excerpt', 'ncllc-pro'), checked: !!props.attributes.showExcerpt, onChange: function(value) { props.setAttributes({ showExcerpt: value }); } }), field(__('Button text', 'ncllc-pro'), props.attributes.buttonText, function(value) { props.setAttributes({ buttonText: value }); })];
    });
    dynamicBlock('ajnanda/post-carousel', __('AJ Post Carousel Placeholder', 'ncllc-pro'), 'images-alt2', { count: { type: 'number', default: 6 }, showExcerpt: { type: 'boolean', default: true }, buttonText: { type: 'string', default: 'Read More' } }, function(props) {
        return [el(RangeControl, { label: __('Post count', 'ncllc-pro'), min: 1, max: 12, value: props.attributes.count, onChange: function(value) { props.setAttributes({ count: value }); } }), el(ToggleControl, { label: __('Show excerpt', 'ncllc-pro'), checked: !!props.attributes.showExcerpt, onChange: function(value) { props.setAttributes({ showExcerpt: value }); } }), field(__('Button text', 'ncllc-pro'), props.attributes.buttonText, function(value) { props.setAttributes({ buttonText: value }); })];
    });
    dynamicBlock('ajnanda/post-timeline', __('AJ Post Timeline', 'ncllc-pro'), 'backup', { count: { type: 'number', default: 5 }, showExcerpt: { type: 'boolean', default: true }, buttonText: { type: 'string', default: 'Read More' } }, function(props) {
        return [el(RangeControl, { label: __('Post count', 'ncllc-pro'), min: 1, max: 12, value: props.attributes.count, onChange: function(value) { props.setAttributes({ count: value }); } }), el(ToggleControl, { label: __('Show excerpt', 'ncllc-pro'), checked: !!props.attributes.showExcerpt, onChange: function(value) { props.setAttributes({ showExcerpt: value }); } }), field(__('Button text', 'ncllc-pro'), props.attributes.buttonText, function(value) { props.setAttributes({ buttonText: value }); })];
    });
    dynamicBlock('ajnanda/search', __('AJ Search', 'ncllc-pro'), 'search', { placeholder: { type: 'string', default: 'Search...' }, buttonText: { type: 'string', default: 'Search' } }, function(props) {
        return [field(__('Placeholder', 'ncllc-pro'), props.attributes.placeholder, function(value) { props.setAttributes({ placeholder: value }); }), field(__('Button text', 'ncllc-pro'), props.attributes.buttonText, function(value) { props.setAttributes({ buttonText: value }); })];
    });
    dynamicBlock('ajnanda/nav-menu', __('AJ Menu/Nav Menu', 'ncllc-pro'), 'menu', { menuLocation: { type: 'string', default: 'primary' } }, function(props) {
        return el(SelectControl, { label: __('Menu location', 'ncllc-pro'), value: props.attributes.menuLocation, options: [{ label: 'Primary', value: 'primary' }, { label: 'Footer', value: 'footer' }], onChange: function(value) { props.setAttributes({ menuLocation: value }); } });
    });
    dynamicBlock('ajnanda/table-of-contents', __('AJ Table of Contents', 'ncllc-pro'), 'list-view', {});
    dynamicBlock('ajnanda/taxonomy-list', __('AJ Taxonomy List', 'ncllc-pro'), 'category', { taxonomy: { type: 'string', default: 'category' } }, function(props) {
        return el(SelectControl, { label: __('Taxonomy', 'ncllc-pro'), value: props.attributes.taxonomy, options: [{ label: 'Categories', value: 'category' }, { label: 'Tags', value: 'post_tag' }], onChange: function(value) { props.setAttributes({ taxonomy: value }); } });
    });
    dynamicBlock('ajnanda/login-placeholder', __('AJ Login Placeholder', 'ncllc-pro'), 'admin-users', {});
})(window.wp);
