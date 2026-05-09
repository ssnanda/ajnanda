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
            marginBottom: { type: 'number', default: 0 },
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
            el(RangeControl, { label: __('Margin bottom', 'ncllc-pro'), min: 0, max: 160, value: attrs.marginBottom || 0, onChange: function(value) { props.setAttributes({ marginBottom: value }); } }),
            el(SelectControl, {
                label: __('Animation', 'ncllc-pro'),
                value: attrs.animation || 'none',
                options: [
                    { label: __('None', 'ncllc-pro'), value: 'none' },
                    { label: __('Fade In', 'ncllc-pro'), value: 'fade-in' },
                    { label: __('Slide Up', 'ncllc-pro'), value: 'slide-up' },
                    { label: __('Zoom In', 'ncllc-pro'), value: 'zoom-in' }
                ],
                onChange: function(value) { props.setAttributes({ animation: value }); }
            })
        ];
    }

    function controlsWithCommon(props, controls) {
        controls = controls ? (Array.isArray(controls) ? controls : [controls]) : [];
        return controls.concat(commonControls(props));
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
                label: __('Direction', 'ncllc-pro'),
                value: attrs.direction || 'row',
                options: [
                    { label: __('Row', 'ncllc-pro'), value: 'row' },
                    { label: __('Column', 'ncllc-pro'), value: 'column' },
                    { label: __('Row Reverse', 'ncllc-pro'), value: 'row-reverse' },
                    { label: __('Column Reverse', 'ncllc-pro'), value: 'column-reverse' }
                ],
                onChange: function(value) { props.setAttributes({ direction: value }); }
            }),
            el(SelectControl, {
                label: __('Justify content', 'ncllc-pro'),
                value: attrs.justify || 'flex-start',
                options: [
                    { label: __('Start', 'ncllc-pro'), value: 'flex-start' },
                    { label: __('Center', 'ncllc-pro'), value: 'center' },
                    { label: __('End', 'ncllc-pro'), value: 'flex-end' },
                    { label: __('Space Between', 'ncllc-pro'), value: 'space-between' }
                ],
                onChange: function(value) { props.setAttributes({ justify: value }); }
            }),
            el(SelectControl, {
                label: __('Align items', 'ncllc-pro'),
                value: attrs.alignItems || 'stretch',
                options: [
                    { label: __('Stretch', 'ncllc-pro'), value: 'stretch' },
                    { label: __('Start', 'ncllc-pro'), value: 'flex-start' },
                    { label: __('Center', 'ncllc-pro'), value: 'center' },
                    { label: __('End', 'ncllc-pro'), value: 'flex-end' }
                ],
                onChange: function(value) { props.setAttributes({ alignItems: value }); }
            }),
            el(ToggleControl, { label: __('Allow wrap', 'ncllc-pro'), checked: attrs.wrap !== false, onChange: function(value) { props.setAttributes({ wrap: value }); } }),
            el(RangeControl, { label: __('Gap', 'ncllc-pro'), min: 0, max: 80, value: attrs.gap || 16, onChange: function(value) { props.setAttributes({ gap: value }); } })
        ];
    }

    function gridControls(props) {
        var attrs = props.attributes;

        return [
            el(RangeControl, { label: __('Columns', 'ncllc-pro'), min: 1, max: 6, value: attrs.columns || 3, onChange: function(value) { props.setAttributes({ columns: value }); } }),
            el(RangeControl, { label: __('Gap', 'ncllc-pro'), min: 0, max: 80, value: attrs.gap || 20, onChange: function(value) { props.setAttributes({ gap: value }); } })
        ];
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
            createBlock('core/paragraph', { placeholder: label || __('Add content', 'ncllc-pro') })
        ]);
    }

    function containerFooterBlock() {
        return createBlock('core/buttons', { layout: { type: 'flex', justifyContent: 'center' } }, [
            createBlock('core/button', { text: __('Button', 'ncllc-pro') })
        ]);
    }

    function containerBlankBlock() {
        return containerChild(__('Container', 'ncllc-pro'), { label: __('Container', 'ncllc-pro') });
    }

    function containerHeadingBlock() {
        return containerChild(__('Heading Container', 'ncllc-pro'), { label: __('Heading Container', 'ncllc-pro'), alignItems: 'center', containerType: 'header' }, [
            createBlock('core/heading', { level: 2, content: __('Section heading', 'ncllc-pro'), textAlign: 'center' }),
            createBlock('core/paragraph', { placeholder: __('Add supporting text.', 'ncllc-pro'), align: 'center' })
        ]);
    }

    function containerBlockForInsert(type) {
        switch (type) {
            case 'heading':
                return containerHeadingBlock();
            case 'row-two':
                return containerColumns(__('Tile Container', 'ncllc-pro'), 2);
            case 'row-three':
                return containerColumns(__('Tile Container', 'ncllc-pro'), 3);
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

        block = containerChild(__('Column', 'ncllc-pro'), { label: __('Column', 'ncllc-pro'), containerType: 'tile' });

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
            { title: __('Heading container', 'ncllc-pro'), onClick: function() { onSelect('heading'); } },
            { title: __('Blank container', 'ncllc-pro'), onClick: function() { onSelect('blank'); } },
            { title: __('2 column row', 'ncllc-pro'), onClick: function() { onSelect('row-two'); } },
            { title: __('3 column row', 'ncllc-pro'), onClick: function() { onSelect('row-three'); } },
            { title: __('Button row', 'ncllc-pro'), onClick: function() { onSelect('button'); } }
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
            containerInsertDropdown('aj-container-insert aj-container-insert--top', __('Add above', 'ncllc-pro'), function(type) {
                insertContainerBlock(props, context, 'before', containerBlockForInsert(type));
            }),
            containerInsertDropdown('aj-container-insert aj-container-insert--bottom', __('Add below', 'ncllc-pro'), function(type) {
                insertContainerBlock(props, context, 'after', containerBlockForInsert(type));
            }),
            showColumnControls ? el(Button, {
                className: 'aj-container-insert aj-container-insert--left',
                label: __('Add column on the left', 'ncllc-pro'),
                onClick: function() { insertContainerColumn(props, context, 'left'); }
            }, '+') : null,
            showColumnControls ? el(Button, {
                className: 'aj-container-insert aj-container-insert--right',
                label: __('Add column on the right', 'ncllc-pro'),
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
            children.push(containerChild(__('Column ', 'ncllc-pro') + index, { label: __('Column ', 'ncllc-pro') + index, containerType: 'tile' }));
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
                    containerColumns(__('Tile Container', 'ncllc-pro'), 3),
                    containerFooterBlock()
                ];
            case 'section-two':
                return [
                    containerHeadingBlock(),
                    containerColumns(__('Tile Container', 'ncllc-pro'), 2)
                ];
            case 'two':
                return [containerColumns(__('Tile Container', 'ncllc-pro'), 2)];
            case 'three':
                return [containerColumns(__('Tile Container', 'ncllc-pro'), 3)];
            case 'four':
                return [containerColumns(__('Tile Container', 'ncllc-pro'), 4)];
            case 'grid-2x2':
                return [containerColumns(__('Tile Container', 'ncllc-pro'), 4, { gridRows: 2 })];
            case 'left-wide':
                return [containerColumns(__('Tile Container', 'ncllc-pro'), 2)];
            case 'right-wide':
                return [containerColumns(__('Tile Container', 'ncllc-pro'), 2)];
            default:
                return [containerBlankBlock()];
        }
    }

    function applyContainerLayout(props, pattern) {
        var isSection = pattern !== 'one';
        var attrs = {
            layoutSelected: true,
            layoutPreset: pattern,
            label: isSection ? __('Section Container', 'ncllc-pro') : __('AJ Container', 'ncllc-pro'),
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
            { value: 'one', label: __('One column', 'ncllc-pro') },
            { value: 'two', label: __('Two columns', 'ncllc-pro') },
            { value: 'three', label: __('Three columns', 'ncllc-pro') },
            { value: 'four', label: __('Four columns', 'ncllc-pro') },
            { value: 'grid-2x2', label: __('Grid 2x2', 'ncllc-pro') },
            { value: 'left-wide', label: __('Left wide', 'ncllc-pro') },
            { value: 'right-wide', label: __('Right wide', 'ncllc-pro') },
            { value: 'section-two', label: __('Heading + 2 columns', 'ncllc-pro') },
            { value: 'section-three', label: __('Heading + 3 columns + button', 'ncllc-pro') }
        ];

        return el('div', { className: 'aj-container-layout-chooser' },
            el('div', { className: 'aj-container-layout-chooser__intro' },
                el('span', { className: 'dashicons dashicons-screenoptions' }),
                el('strong', {}, __('Container', 'ncllc-pro')),
                el('p', {}, __('Select a container layout to start with.', 'ncllc-pro'))
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
            field(__('Label', 'ncllc-pro'), attrs.label, function(value) { props.setAttributes({ label: value }); }, __('Container label', 'ncllc-pro')),
            segmented(__('Container Type', 'ncllc-pro'), attrs.containerType || 'container', [
                { label: __('Container', 'ncllc-pro'), value: 'container' },
                { label: __('Section', 'ncllc-pro'), value: 'section' },
                { label: __('Row', 'ncllc-pro'), value: 'row' },
                { label: __('Tile', 'ncllc-pro'), value: 'tile' }
            ], function(value) { props.setAttributes({ containerType: value }); }),
            segmented(__('Width', 'ncllc-pro'), attrs.contentWidth || 'boxed', [
                { label: __('Boxed', 'ncllc-pro'), value: 'boxed' },
                { label: __('Full Width', 'ncllc-pro'), value: 'full' }
            ], function(value) { props.setAttributes({ contentWidth: value }); }),
            segmented(__('Quick Add', 'ncllc-pro'), 'none', [
                { label: __('Header', 'ncllc-pro'), value: 'header' },
                { label: __('Row', 'ncllc-pro'), value: 'row' },
                { label: __('Footer', 'ncllc-pro'), value: 'footer' }
            ], function(value) {
                if (!dispatch || !createBlock) {
                    return;
                }

                var block = value === 'row' ? containerColumns(__('Tile Container', 'ncllc-pro'), Math.max(2, attrs.columns || 3)) : containerChild(value === 'header' ? __('Heading Container', 'ncllc-pro') : __('Footer Container', 'ncllc-pro'), {
                    label: value === 'header' ? __('Heading Container', 'ncllc-pro') : __('Footer Container', 'ncllc-pro'),
                    containerType: value === 'header' ? 'header' : 'footer',
                    alignItems: 'center'
                }, value === 'header' ? [createBlock('core/heading', { level: 2, content: __('Section heading', 'ncllc-pro'), textAlign: 'center' })] : [createBlock('core/buttons', { layout: { type: 'flex', justifyContent: 'center' } }, [createBlock('core/button', { text: __('Button', 'ncllc-pro') })])]);

                dispatch('core/block-editor').insertBlocks(block, undefined, props.clientId);
            }, __('Add a heading, row, or footer/button area without converting everything into columns.', 'ncllc-pro')),
            segmented(__('Layout', 'ncllc-pro'), attrs.layoutMode || 'flex', [
                { label: __('Flex', 'ncllc-pro'), value: 'flex' },
                { label: __('Grid', 'ncllc-pro'), value: 'grid' }
            ], function(value) { props.setAttributes({ layoutMode: value }); }),
            !isGrid ? segmented(__('Direction', 'ncllc-pro'), attrs.direction || 'row', [
                { label: __('Row', 'ncllc-pro'), value: 'row', icon: '→' },
                { label: __('Column', 'ncllc-pro'), value: 'column', icon: '↓' },
                { label: __('Row Reverse', 'ncllc-pro'), value: 'row-reverse', icon: '←' },
                { label: __('Column Reverse', 'ncllc-pro'), value: 'column-reverse', icon: '↑' }
            ], function(value) { props.setAttributes({ direction: value }); }, __('Define the direction in which blocks inside this container are placed.', 'ncllc-pro')) : null,
            !isGrid ? segmented(__('Children Width', 'ncllc-pro'), attrs.childrenWidth || 'equal', [
                { label: __('Auto', 'ncllc-pro'), value: 'auto' },
                { label: __('Equal', 'ncllc-pro'), value: 'equal' }
            ], function(value) { props.setAttributes({ childrenWidth: value }); }) : null,
            isGrid ? el(RangeControl, { label: __('Columns', 'ncllc-pro'), min: 1, max: 6, value: attrs.columns || 2, onChange: function(value) { props.setAttributes({ columns: value }); } }) : null,
            isGrid ? el(RangeControl, { label: __('Rows', 'ncllc-pro'), min: 1, max: 6, value: attrs.gridRows || 1, onChange: function(value) { props.setAttributes({ gridRows: value }); } }) : null,
            segmented(__('Align Items', 'ncllc-pro'), attrs.alignItems || 'stretch', [
                { label: __('Start', 'ncllc-pro'), value: 'flex-start', icon: '▔' },
                { label: __('Center', 'ncllc-pro'), value: 'center', icon: '≡' },
                { label: __('End', 'ncllc-pro'), value: 'flex-end', icon: '▁' },
                { label: __('Stretch', 'ncllc-pro'), value: 'stretch', icon: '▮' }
            ], function(value) { props.setAttributes({ alignItems: value }); }, isGrid ? __('Define the vertical alignment for grid items inside this container.', 'ncllc-pro') : __('Define the vertical alignment inside this container.', 'ncllc-pro')),
            segmented(__('Justify Content', 'ncllc-pro'), attrs.justify || 'center', [
                { label: __('Start', 'ncllc-pro'), value: 'flex-start', icon: '▌▌' },
                { label: __('Center', 'ncllc-pro'), value: 'center', icon: '|▌|' },
                { label: __('End', 'ncllc-pro'), value: 'flex-end', icon: '▌▌' },
                { label: __('Space Between', 'ncllc-pro'), value: 'space-between', icon: '▌  ▌' },
                { label: __('Space Around', 'ncllc-pro'), value: 'space-around', icon: ' ▌ ▌ ' },
                { label: __('Space Evenly', 'ncllc-pro'), value: 'space-evenly', icon: '▌ ▌ ▌' }
            ], function(value) { props.setAttributes({ justify: value }); }, isGrid ? __('Define the horizontal alignment for grid items within this container.', 'ncllc-pro') : __('Define the horizontal alignment inside this container.', 'ncllc-pro')),
            isGrid ? segmented(__('Align Content', 'ncllc-pro'), attrs.alignContent || 'stretch', [
                { label: __('Start', 'ncllc-pro'), value: 'start', icon: '▔' },
                { label: __('Center', 'ncllc-pro'), value: 'center', icon: '≡' },
                { label: __('End', 'ncllc-pro'), value: 'end', icon: '▁' },
                { label: __('Stretch', 'ncllc-pro'), value: 'stretch', icon: '▮' },
                { label: __('Between', 'ncllc-pro'), value: 'space-between', icon: '▔▁' },
                { label: __('Evenly', 'ncllc-pro'), value: 'space-evenly', icon: '≡≡' }
            ], function(value) { props.setAttributes({ alignContent: value }); }) : null,
            !isGrid ? segmented(__('Wrap', 'ncllc-pro'), attrs.wrapMode || 'wrap', [
                { label: __('No Wrap', 'ncllc-pro'), value: 'nowrap', icon: '↔' },
                { label: __('Wrap', 'ncllc-pro'), value: 'wrap', icon: '↵' },
                { label: __('Reverse', 'ncllc-pro'), value: 'wrap-reverse', icon: '↩' }
            ], function(value) { props.setAttributes({ wrapMode: value }); }) : null,
            el(RangeControl, { label: __('Gap', 'ncllc-pro'), min: 0, max: 96, value: attrs.gap || 16, onChange: function(value) { props.setAttributes({ gap: value }); } }),
            el(RangeControl, { label: __('Max width', 'ncllc-pro'), min: 320, max: 1800, value: attrs.maxWidth || 1100, onChange: function(value) { props.setAttributes({ maxWidth: value }); } }),
            el(RangeControl, { label: __('Minimum height', 'ncllc-pro'), min: 0, max: 900, value: attrs.minHeight || 0, onChange: function(value) { props.setAttributes({ minHeight: value }); } })
        ];
    }

    function mediaControls(props) {
        var attrs = props.attributes;

        return [
            el(RangeControl, { label: __('Minimum height', 'ncllc-pro'), min: 100, max: 800, value: attrs.minHeight || 320, onChange: function(value) { props.setAttributes({ minHeight: value }); } }),
            el(SelectControl, {
                label: __('Aspect ratio', 'ncllc-pro'),
                value: attrs.aspectRatio || '16 / 9',
                options: [
                    { label: '16:9', value: '16 / 9' },
                    { label: '4:3', value: '4 / 3' },
                    { label: '1:1', value: '1 / 1' },
                    { label: __('Auto', 'ncllc-pro'), value: '' }
                ],
                onChange: function(value) { props.setAttributes({ aspectRatio: value }); }
            })
        ];
    }

    registerContainerBlock('ajnanda/div-block', __('AJ Div Block', 'ncllc-pro'), __('Simple wrapper block.', 'ncllc-pro'), 'aj-div', [], {
        attributes: { minHeight: { type: 'number', default: 0 } },
        controls: function(props) {
            return el(RangeControl, { label: __('Minimum height', 'ncllc-pro'), min: 0, max: 800, value: props.attributes.minHeight || 0, onChange: function(value) { props.setAttributes({ minHeight: value }); } });
        }
    });
    registerContainerBlock('ajnanda/flexbox', __('AJ Flexbox', 'ncllc-pro'), __('Flexible row or column layout.', 'ncllc-pro'), 'aj-flexbox', [['core/paragraph', { placeholder: __('Flex item', 'ncllc-pro') }]], {
        attributes: { direction: { type: 'string', default: 'row' }, justify: { type: 'string', default: 'flex-start' }, alignItems: { type: 'string', default: 'stretch' }, wrap: { type: 'boolean', default: true }, gap: { type: 'number', default: 16 } },
        controls: layoutControls,
        className: function(attrs) { return classNames('aj-flexbox--' + attrs.direction, attrs.wrap === false ? 'aj-flexbox--nowrap' : ''); }
    });
    registerContainerBlock('ajnanda/container', __('AJ Container', 'ncllc-pro'), __('Constrained content container.', 'ncllc-pro'), 'aj-container', [], {
        label: function(attrs) {
            return attrs.label || __('AJ Container', 'ncllc-pro');
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
    registerContainerBlock('ajnanda/grid', __('AJ Grid', 'ncllc-pro'), __('Responsive grid layout.', 'ncllc-pro'), 'aj-grid', [['core/group', { className: 'aj-card' }], ['core/group', { className: 'aj-card' }], ['core/group', { className: 'aj-card' }]], {
        attributes: { columns: { type: 'number', default: 3 }, gap: { type: 'number', default: 20 } },
        controls: gridControls
    });

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

    function mediaEmbedBlock(name, title, icon, className, placeholder, extraAttrs, extraControlBuilder) {
        registerBlockType(name, {
            title: title,
            category: category,
            icon: icon,
            attributes: withStyleAttributes(Object.assign({ url: { type: 'string' }, minHeight: { type: 'number', default: 320 }, aspectRatio: { type: 'string', default: '16 / 9' } }, extraAttrs || {})),
            edit: function(props) {
                return el(Fragment, {}, inspector(controlsWithCommon(props, [field(__('URL', 'ncllc-pro'), props.attributes.url, function(value) { props.setAttributes({ url: value }); }, placeholder)].concat(mediaControls(props), extraControlBuilder ? extraControlBuilder(props) : []))), el('div', styledProps(className, props.attributes), props.attributes.url || placeholder));
            },
            save: function(props) {
                return el('div', styledProps(className, props.attributes), props.attributes.url ? el('iframe', { src: props.attributes.url, loading: 'lazy', allowFullScreen: true, title: title }) : null);
            }
        });
    }

    mediaEmbedBlock('ajnanda/youtube', __('AJ YouTube', 'ncllc-pro'), 'video-alt3', 'aj-embed', 'https://www.youtube.com/embed/...');
    mediaEmbedBlock('ajnanda/video', __('AJ Video', 'ncllc-pro'), 'format-video', 'aj-embed', 'Video embed URL', { controls: { type: 'string', default: 'playback' } }, function(props) {
        return el(SelectControl, { label: __('Controls', 'ncllc-pro'), value: props.attributes.controls || 'playback', options: [{ label: __('Playback controls', 'ncllc-pro'), value: 'playback' }, { label: __('Minimal', 'ncllc-pro'), value: 'minimal' }], onChange: function(value) { props.setAttributes({ controls: value }); } });
    });
    mediaEmbedBlock('ajnanda/google-maps', __('AJ Google Maps Embed', 'ncllc-pro'), 'location-alt', 'aj-embed', 'Google Maps embed URL', { zoom: { type: 'number', default: 12 } }, function(props) {
        return el(RangeControl, { label: __('Zoom', 'ncllc-pro'), min: 1, max: 20, value: props.attributes.zoom || 12, onChange: function(value) { props.setAttributes({ zoom: value }); } });
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
            edit: function(props) {
                return el(Fragment, {},
                    inspector(controlsWithCommon(props, extraControls(props, options))),
                    el('section', styledProps(className, props.attributes, extraClass(props.attributes, options)), el(InnerBlocks, { template: template || [], templateLock: false }))
                );
            },
            save: function(props) {
                return el('section', styledProps(className, props.attributes, extraClass(props.attributes, options)), el(InnerBlocks.Content));
            }
        });
    }

    simpleCardBlock('ajnanda/info-box', __('AJ Info Box', 'ncllc-pro'), 'welcome-widgets-menus', 'aj-info-box', [['ajnanda/icon'], ['core/heading', { level: 3, content: 'Info Box' }], ['core/paragraph', { placeholder: 'Add supporting text.' }]], {
        attributes: { mediaPosition: { type: 'string', default: 'top' } },
        controls: function(props) {
            return el(SelectControl, { label: __('Icon/Image position', 'ncllc-pro'), value: props.attributes.mediaPosition || 'top', options: [{ label: __('Top', 'ncllc-pro'), value: 'top' }, { label: __('Left', 'ncllc-pro'), value: 'left' }, { label: __('Right', 'ncllc-pro'), value: 'right' }], onChange: function(value) { props.setAttributes({ mediaPosition: value }); } });
        },
        className: function(attrs) { return 'aj-media-' + attrs.mediaPosition; }
    });
    simpleCardBlock('ajnanda/call-to-action', __('AJ Call To Action', 'ncllc-pro'), 'megaphone', 'aj-call-to-action', [['core/heading', { level: 2, content: 'Ready to get started?' }], ['core/paragraph', { placeholder: 'Add a short call to action.' }], ['ajnanda/button', { text: 'Get Started' }]], {
        attributes: { layout: { type: 'string', default: 'stacked' } },
        controls: function(props) {
            return el(SelectControl, { label: __('Layout', 'ncllc-pro'), value: props.attributes.layout || 'stacked', options: [{ label: __('Stacked', 'ncllc-pro'), value: 'stacked' }, { label: __('Inline', 'ncllc-pro'), value: 'inline' }], onChange: function(value) { props.setAttributes({ layout: value }); } });
        },
        className: function(attrs) { return 'aj-cta--' + attrs.layout; }
    });
    simpleCardBlock('ajnanda/buttons', __('AJ Buttons', 'ncllc-pro'), 'button', 'aj-buttons', [['core/buttons', {}, [['core/button', { text: 'Button' }], ['core/button', { text: 'Button' }]]]], {
        attributes: { orientation: { type: 'string', default: 'horizontal' }, gap: { type: 'number', default: 12 } },
        controls: function(props) {
            return [el(SelectControl, { label: __('Orientation', 'ncllc-pro'), value: props.attributes.orientation || 'horizontal', options: [{ label: __('Horizontal', 'ncllc-pro'), value: 'horizontal' }, { label: __('Vertical', 'ncllc-pro'), value: 'vertical' }], onChange: function(value) { props.setAttributes({ orientation: value }); } }), el(RangeControl, { label: __('Button gap', 'ncllc-pro'), min: 0, max: 60, value: props.attributes.gap || 12, onChange: function(value) { props.setAttributes({ gap: value }); } })];
        },
        className: function(attrs) { return 'aj-buttons--' + attrs.orientation; }
    });
    simpleCardBlock('ajnanda/marketing-button', __('AJ Marketing Button', 'ncllc-pro'), 'external', 'aj-marketing-button', [['core/buttons', { layout: { type: 'flex', justifyContent: 'center' } }, [['core/button', { text: 'Marketing Button' }]]]], {
        attributes: { showIcon: { type: 'boolean', default: true }, iconPosition: { type: 'string', default: 'after' } },
        controls: function(props) {
            return [el(ToggleControl, { label: __('Show icon', 'ncllc-pro'), checked: !!props.attributes.showIcon, onChange: function(value) { props.setAttributes({ showIcon: value }); } }), el(SelectControl, { label: __('Icon position', 'ncllc-pro'), value: props.attributes.iconPosition || 'after', options: [{ label: __('Before', 'ncllc-pro'), value: 'before' }, { label: __('After', 'ncllc-pro'), value: 'after' }], onChange: function(value) { props.setAttributes({ iconPosition: value }); } })];
        },
        className: function(attrs) { return classNames(attrs.showIcon ? 'aj-marketing-button--icon' : '', 'aj-icon-' + attrs.iconPosition); }
    });
    editableTextBlock('ajnanda/blockquote', __('AJ Blockquote', 'ncllc-pro'), 'format-quote', 'blockquote', 'aj-blockquote', 'Add a quote or testimonial.', 'Quote');
    simpleCardBlock('ajnanda/content-timeline', __('AJ Content Timeline', 'ncllc-pro'), 'networking', 'aj-timeline', [['core/heading', { level: 3, content: 'Timeline Item' }], ['core/paragraph', { placeholder: 'Add milestone details.' }]], {
        attributes: { linePosition: { type: 'string', default: 'left' } },
        controls: function(props) {
            return el(SelectControl, { label: __('Line position', 'ncllc-pro'), value: props.attributes.linePosition || 'left', options: [{ label: __('Left', 'ncllc-pro'), value: 'left' }, { label: __('Center', 'ncllc-pro'), value: 'center' }], onChange: function(value) { props.setAttributes({ linePosition: value }); } });
        },
        className: function(attrs) { return 'aj-timeline--line-' + attrs.linePosition; }
    });

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

    simpleCardBlock('ajnanda/how-to', __('AJ How To', 'ncllc-pro'), 'media-document', 'aj-how-to', [['core/heading', { level: 2, content: 'How To' }], ['core/list', { values: '<li>Step one</li><li>Step two</li><li>Step three</li>' }]], {
        attributes: { showSchema: { type: 'boolean', default: false }, stepStyle: { type: 'string', default: 'numbered' } },
        controls: function(props) {
            return [el(ToggleControl, { label: __('Enable HowTo schema', 'ncllc-pro'), checked: !!props.attributes.showSchema, onChange: function(value) { props.setAttributes({ showSchema: value }); } }), el(SelectControl, { label: __('Step style', 'ncllc-pro'), value: props.attributes.stepStyle || 'numbered', options: [{ label: __('Numbered', 'ncllc-pro'), value: 'numbered' }, { label: __('Bullets', 'ncllc-pro'), value: 'bullets' }, { label: __('Cards', 'ncllc-pro'), value: 'cards' }], onChange: function(value) { props.setAttributes({ stepStyle: value }); } })];
        },
        className: function(attrs) { return 'aj-how-to--' + attrs.stepStyle; }
    });
    editableTextBlock('ajnanda/inline-notice', __('AJ Inline Notice', 'ncllc-pro'), 'info', 'div', 'aj-inline-notice', 'Add an important notice.', 'Notice');
    simpleCardBlock('ajnanda/modal', __('AJ Modal Placeholder', 'ncllc-pro'), 'welcome-comments', 'aj-modal-placeholder', [['core/heading', { level: 3, content: 'Modal Placeholder' }], ['core/paragraph', { placeholder: 'Static modal content placeholder.' }]], {
        attributes: { triggerText: { type: 'string', default: 'Open Modal' }, modalWidth: { type: 'number', default: 640 } },
        controls: function(props) {
            return [field(__('Trigger text', 'ncllc-pro'), props.attributes.triggerText, function(value) { props.setAttributes({ triggerText: value }); }), el(RangeControl, { label: __('Modal width', 'ncllc-pro'), min: 320, max: 1200, value: props.attributes.modalWidth || 640, onChange: function(value) { props.setAttributes({ modalWidth: value }); } })];
        }
    });
    simpleCardBlock('ajnanda/slider', __('AJ Slider Placeholder', 'ncllc-pro'), 'slides', 'aj-slider-placeholder', [['core/image'], ['core/heading', { level: 3, content: 'Slide Title' }], ['core/paragraph', { placeholder: 'Slide text.' }]], {
        attributes: { autoplay: { type: 'boolean', default: false }, delay: { type: 'number', default: 4 }, showArrows: { type: 'boolean', default: true } },
        controls: function(props) {
            return [el(ToggleControl, { label: __('Autoplay', 'ncllc-pro'), checked: !!props.attributes.autoplay, onChange: function(value) { props.setAttributes({ autoplay: value }); } }), el(RangeControl, { label: __('Delay seconds', 'ncllc-pro'), min: 1, max: 12, value: props.attributes.delay || 4, onChange: function(value) { props.setAttributes({ delay: value }); } }), el(ToggleControl, { label: __('Show arrows', 'ncllc-pro'), checked: !!props.attributes.showArrows, onChange: function(value) { props.setAttributes({ showArrows: value }); } })];
        }
    });
    simpleCardBlock('ajnanda/lottie-animation', __('AJ Lottie Animation Placeholder', 'ncllc-pro'), 'controls-repeat', 'aj-lottie-placeholder', [['core/paragraph', { content: 'Lottie animation placeholder.' }]], {
        attributes: { jsonUrl: { type: 'string', default: '' }, loop: { type: 'boolean', default: true }, autoplay: { type: 'boolean', default: true } },
        controls: function(props) {
            return [field(__('Lottie JSON URL', 'ncllc-pro'), props.attributes.jsonUrl, function(value) { props.setAttributes({ jsonUrl: value }); }), el(ToggleControl, { label: __('Loop', 'ncllc-pro'), checked: !!props.attributes.loop, onChange: function(value) { props.setAttributes({ loop: value }); } }), el(ToggleControl, { label: __('Autoplay', 'ncllc-pro'), checked: !!props.attributes.autoplay, onChange: function(value) { props.setAttributes({ autoplay: value }); } })];
        }
    });
    simpleCardBlock('ajnanda/team', __('AJ Team', 'ncllc-pro'), 'groups', 'aj-team', [['core/image'], ['core/heading', { level: 3, content: 'Team Member' }], ['core/paragraph', { content: 'Role or short bio.' }]], {
        attributes: { imageShape: { type: 'string', default: 'rounded' }, socialLinks: { type: 'boolean', default: false } },
        controls: function(props) {
            return [el(SelectControl, { label: __('Image shape', 'ncllc-pro'), value: props.attributes.imageShape || 'rounded', options: [{ label: __('Rounded', 'ncllc-pro'), value: 'rounded' }, { label: __('Circle', 'ncllc-pro'), value: 'circle' }, { label: __('Square', 'ncllc-pro'), value: 'square' }], onChange: function(value) { props.setAttributes({ imageShape: value }); } }), el(ToggleControl, { label: __('Show social links area', 'ncllc-pro'), checked: !!props.attributes.socialLinks, onChange: function(value) { props.setAttributes({ socialLinks: value }); } })];
        },
        className: function(attrs) { return 'aj-team--image-' + attrs.imageShape; }
    });
    simpleCardBlock('ajnanda/testimonials', __('AJ Testimonials', 'ncllc-pro'), 'format-chat', 'aj-testimonials', [['core/quote', { value: 'Add testimonial text.', citation: 'Customer Name' }]], {
        attributes: { layout: { type: 'string', default: 'single' }, showQuoteIcon: { type: 'boolean', default: true } },
        controls: function(props) {
            return [el(SelectControl, { label: __('Layout', 'ncllc-pro'), value: props.attributes.layout || 'single', options: [{ label: __('Single', 'ncllc-pro'), value: 'single' }, { label: __('Grid', 'ncllc-pro'), value: 'grid' }, { label: __('Carousel placeholder', 'ncllc-pro'), value: 'carousel' }], onChange: function(value) { props.setAttributes({ layout: value }); } }), el(ToggleControl, { label: __('Show quote icon', 'ncllc-pro'), checked: !!props.attributes.showQuoteIcon, onChange: function(value) { props.setAttributes({ showQuoteIcon: value }); } })];
        },
        className: function(attrs) { return 'aj-testimonials--' + attrs.layout; }
    });
    simpleCardBlock('ajnanda/review', __('AJ Review', 'ncllc-pro'), 'star-filled', 'aj-review', [['ajnanda/star-ratings'], ['core/quote', { value: 'Add review text.', citation: 'Reviewer Name' }]], {
        attributes: { enableSchema: { type: 'boolean', default: false }, reviewerImage: { type: 'boolean', default: false } },
        controls: function(props) {
            return [el(ToggleControl, { label: __('Enable review schema', 'ncllc-pro'), checked: !!props.attributes.enableSchema, onChange: function(value) { props.setAttributes({ enableSchema: value }); } }), el(ToggleControl, { label: __('Reviewer image area', 'ncllc-pro'), checked: !!props.attributes.reviewerImage, onChange: function(value) { props.setAttributes({ reviewerImage: value }); } })];
        }
    });
    simpleCardBlock('ajnanda/price-list', __('AJ Price List', 'ncllc-pro'), 'money-alt', 'aj-price-list', [['core/list', { values: '<li>Service - $99</li><li>Service - $149</li>' }]], {
        attributes: { currency: { type: 'string', default: '$' }, layout: { type: 'string', default: 'list' } },
        controls: function(props) {
            return [field(__('Currency symbol', 'ncllc-pro'), props.attributes.currency, function(value) { props.setAttributes({ currency: value }); }), el(SelectControl, { label: __('Layout', 'ncllc-pro'), value: props.attributes.layout || 'list', options: [{ label: __('List', 'ncllc-pro'), value: 'list' }, { label: __('Cards', 'ncllc-pro'), value: 'cards' }], onChange: function(value) { props.setAttributes({ layout: value }); } })];
        },
        className: function(attrs) { return 'aj-price-list--' + attrs.layout; }
    });
    simpleCardBlock('ajnanda/social-share', __('AJ Social Share', 'ncllc-pro'), 'share', 'aj-social-share', [['core/buttons', {}, [['core/button', { text: 'Share' }], ['core/button', { text: 'LinkedIn' }], ['core/button', { text: 'Email' }]]]], {
        attributes: { networks: { type: 'string', default: 'Facebook, LinkedIn, Email' }, iconOnly: { type: 'boolean', default: false } },
        controls: function(props) {
            return [field(__('Networks', 'ncllc-pro'), props.attributes.networks, function(value) { props.setAttributes({ networks: value }); }, 'Facebook, LinkedIn, Email'), el(ToggleControl, { label: __('Icon only', 'ncllc-pro'), checked: !!props.attributes.iconOnly, onChange: function(value) { props.setAttributes({ iconOnly: value }); } })];
        },
        className: function(attrs) { return attrs.iconOnly ? 'aj-social-share--icon-only' : ''; }
    });
    simpleCardBlock('ajnanda/separator', __('AJ Separator', 'ncllc-pro'), 'minus', 'aj-separator-block', [], {
        attributes: { thickness: { type: 'number', default: 1 }, width: { type: 'number', default: 100 } },
        controls: function(props) {
            return [el(RangeControl, { label: __('Thickness', 'ncllc-pro'), min: 1, max: 16, value: props.attributes.thickness || 1, onChange: function(value) { props.setAttributes({ thickness: value }); } }), el(RangeControl, { label: __('Width percent', 'ncllc-pro'), min: 10, max: 100, value: props.attributes.width || 100, onChange: function(value) { props.setAttributes({ width: value }); } })];
        }
    });

    registerContainerBlock('ajnanda/form', __('AJ Form', 'ncllc-pro'), __('Static form layout.', 'ncllc-pro'), 'aj-form', [['ajnanda/label'], ['ajnanda/input'], ['ajnanda/submit-button']], {
        attributes: { submitAction: { type: 'string', default: 'none' }, fieldGap: { type: 'number', default: 14 } },
        controls: function(props) {
            return [el(SelectControl, { label: __('Submit action', 'ncllc-pro'), value: props.attributes.submitAction || 'none', options: [{ label: __('Placeholder only', 'ncllc-pro'), value: 'none' }, { label: __('Email placeholder', 'ncllc-pro'), value: 'email' }, { label: __('Webhook placeholder', 'ncllc-pro'), value: 'webhook' }], onChange: function(value) { props.setAttributes({ submitAction: value }); } }), el(RangeControl, { label: __('Field gap', 'ncllc-pro'), min: 0, max: 48, value: props.attributes.fieldGap || 14, onChange: function(value) { props.setAttributes({ fieldGap: value, gap: value }); } })];
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
                        field(__('Name', 'ncllc-pro'), attrs.name, function(value) { props.setAttributes({ name: value }); }),
                        field(__('Placeholder', 'ncllc-pro'), attrs.placeholder, function(value) { props.setAttributes({ placeholder: value }); }),
                        tag === 'input' && defaults.type !== 'checkbox' ? el(SelectControl, { label: __('Input type', 'ncllc-pro'), value: attrs.fieldType || 'text', options: [{ label: __('Text', 'ncllc-pro'), value: 'text' }, { label: __('Email', 'ncllc-pro'), value: 'email' }, { label: __('Phone', 'ncllc-pro'), value: 'tel' }, { label: __('Number', 'ncllc-pro'), value: 'number' }, { label: __('URL', 'ncllc-pro'), value: 'url' }], onChange: function(value) { props.setAttributes({ fieldType: value }); } }) : null,
                        tag !== 'label' ? el(ToggleControl, { label: __('Required', 'ncllc-pro'), checked: !!attrs.required, onChange: function(value) { props.setAttributes({ required: value }); } }) : null
                    ])),
                    tag === 'label' ? el(RichText, Object.assign({ tagName: 'label', value: attrs.text, placeholder: __('Label', 'ncllc-pro'), onChange: function(value) { props.setAttributes({ text: value }); } }, styledProps('aj-label', attrs))) : el(tag, Object.assign({ placeholder: attrs.placeholder, type: attrs.fieldType || defaults.type || undefined, value: '', readOnly: true, required: !!attrs.required }, styledProps('aj-field', attrs)))
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

    registerContainerBlock('ajnanda/tabs', __('AJ Tabs', 'ncllc-pro'), __('Tabbed content placeholder.', 'ncllc-pro'), 'aj-tabs', [['core/heading', { level: 3, content: 'Tab Title' }], ['core/paragraph', { placeholder: 'Tab content' }]], {
        attributes: { tabPosition: { type: 'string', default: 'top' }, activeTab: { type: 'number', default: 1 } },
        controls: function(props) {
            return [el(SelectControl, { label: __('Tab position', 'ncllc-pro'), value: props.attributes.tabPosition || 'top', options: [{ label: __('Top', 'ncllc-pro'), value: 'top' }, { label: __('Left', 'ncllc-pro'), value: 'left' }, { label: __('Right', 'ncllc-pro'), value: 'right' }], onChange: function(value) { props.setAttributes({ tabPosition: value }); } }), el(RangeControl, { label: __('Default active tab', 'ncllc-pro'), min: 1, max: 10, value: props.attributes.activeTab || 1, onChange: function(value) { props.setAttributes({ activeTab: value }); } })];
        },
        className: function(attrs) { return 'aj-tabs--' + attrs.tabPosition; }
    });
    registerContainerBlock('ajnanda/accordion', __('AJ Accordion', 'ncllc-pro'), __('Expandable content layout.', 'ncllc-pro'), 'aj-accordion', [['core/details', { summary: 'Accordion item' }]], {
        attributes: { collapseOtherItems: { type: 'boolean', default: true }, expandFirstItem: { type: 'boolean', default: true }, iconPosition: { type: 'string', default: 'left' } },
        controls: function(props) {
            return [el(ToggleControl, { label: __('Collapse other items', 'ncllc-pro'), checked: !!props.attributes.collapseOtherItems, onChange: function(value) { props.setAttributes({ collapseOtherItems: value }); } }), el(ToggleControl, { label: __('Expand first item', 'ncllc-pro'), checked: !!props.attributes.expandFirstItem, onChange: function(value) { props.setAttributes({ expandFirstItem: value }); } }), el(SelectControl, { label: __('Icon position', 'ncllc-pro'), value: props.attributes.iconPosition || 'left', options: [{ label: __('Left', 'ncllc-pro'), value: 'left' }, { label: __('Right', 'ncllc-pro'), value: 'right' }], onChange: function(value) { props.setAttributes({ iconPosition: value }); } })];
        },
        className: function(attrs) { return 'aj-accordion--icon-' + attrs.iconPosition; }
    });
    registerContainerBlock('ajnanda/image-box', __('AJ Image Box', 'ncllc-pro'), __('Image with text.', 'ncllc-pro'), 'aj-image-box', [['ajnanda/image'], ['ajnanda/heading', { content: 'Image Box' }], ['ajnanda/text-editor']], {
        attributes: { imagePosition: { type: 'string', default: 'top' } },
        controls: function(props) {
            return el(SelectControl, { label: __('Image position', 'ncllc-pro'), value: props.attributes.imagePosition || 'top', options: [{ label: __('Top', 'ncllc-pro'), value: 'top' }, { label: __('Left', 'ncllc-pro'), value: 'left' }, { label: __('Right', 'ncllc-pro'), value: 'right' }], onChange: function(value) { props.setAttributes({ imagePosition: value }); } });
        },
        className: function(attrs) { return 'aj-media-' + attrs.imagePosition; }
    });
    registerContainerBlock('ajnanda/icon-box', __('AJ Icon Box', 'ncllc-pro'), __('Icon with text.', 'ncllc-pro'), 'aj-icon-box', [['ajnanda/icon'], ['ajnanda/heading', { content: 'Icon Box', level: 3 }], ['ajnanda/text-editor']], {
        attributes: { iconPosition: { type: 'string', default: 'top' } },
        controls: function(props) {
            return el(SelectControl, { label: __('Icon position', 'ncllc-pro'), value: props.attributes.iconPosition || 'top', options: [{ label: __('Top', 'ncllc-pro'), value: 'top' }, { label: __('Left', 'ncllc-pro'), value: 'left' }, { label: __('Right', 'ncllc-pro'), value: 'right' }], onChange: function(value) { props.setAttributes({ iconPosition: value }); } });
        },
        className: function(attrs) { return 'aj-media-' + attrs.iconPosition; }
    });
    registerContainerBlock('ajnanda/basic-gallery', __('AJ Basic Gallery', 'ncllc-pro'), __('Simple image gallery wrapper.', 'ncllc-pro'), 'aj-gallery', [['core/gallery']], { attributes: { columns: { type: 'number', default: 3 }, gap: { type: 'number', default: 16 } }, controls: gridControls });
    registerContainerBlock('ajnanda/image-gallery', __('AJ Image Gallery', 'ncllc-pro'), __('Simple image gallery wrapper.', 'ncllc-pro'), 'aj-gallery', [['core/gallery']], { attributes: { columns: { type: 'number', default: 3 }, gap: { type: 'number', default: 16 } }, controls: gridControls });
    registerBlockType('ajnanda/icon-list', {
        title: __('AJ Icon List', 'ncllc-pro'),
        description: __('Create a list highlighted with icons or images.', 'ncllc-pro'),
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
                    el(PanelBody, { title: __('Icon', 'ncllc-pro'), initialOpen: true },
                        segmented(__('Type', 'ncllc-pro'), attrs.iconType || 'icon', [
                            { label: __('Icon', 'ncllc-pro'), value: 'icon' },
                            { label: __('Image', 'ncllc-pro'), value: 'image' },
                            { label: __('None', 'ncllc-pro'), value: 'none' }
                        ], function(value) { props.setAttributes({ iconType: value }); }),
                        attrs.iconType === 'icon' ? field(__('Icon', 'ncllc-pro'), attrs.icon, function(value) { props.setAttributes({ icon: value }); }, '→') : null,
                        attrs.iconType === 'image' ? el('div', { className: 'aj-image-control' },
                            attrs.iconImageUrl ? el('img', { src: attrs.iconImageUrl, alt: '' }) : el('div', { className: 'aj-image-control__empty' }, '+'),
                            el(MediaUploadCheck, {}, el(MediaUpload, {
                                onSelect: function(media) { props.setAttributes({ iconImageUrl: media.url }); },
                                allowedTypes: ['image'],
                                render: function(obj) { return el(Button, { variant: 'secondary', onClick: obj.open }, attrs.iconImageUrl ? __('Replace Image', 'ncllc-pro') : __('Choose Image', 'ncllc-pro')); }
                            }))
                        ) : null,
                        el(RangeControl, { label: __('Icon size', 'ncllc-pro'), min: 10, max: 80, value: attrs.iconSize || 24, onChange: function(value) { props.setAttributes({ iconSize: value }); } }),
                        field(__('Icon color', 'ncllc-pro'), attrs.iconColor, function(value) { props.setAttributes({ iconColor: value }); }, '#111827'),
                        field(__('Icon background', 'ncllc-pro'), attrs.iconBackground, function(value) { props.setAttributes({ iconBackground: value }); }, '#ffffff')
                    ),
                    el(PanelBody, { title: __('Content', 'ncllc-pro'), initialOpen: false },
                        el(SelectControl, { label: __('Layout', 'ncllc-pro'), value: attrs.layout || 'stack', options: [{ label: __('Stack', 'ncllc-pro'), value: 'stack' }, { label: __('Inline', 'ncllc-pro'), value: 'inline' }, { label: __('Grid', 'ncllc-pro'), value: 'grid' }], onChange: function(value) { props.setAttributes({ layout: value }); } }),
                        attrs.layout === 'grid' ? el(RangeControl, { label: __('Columns', 'ncllc-pro'), min: 1, max: 6, value: attrs.columns || 1, onChange: function(value) { props.setAttributes({ columns: value }); } }) : null,
                        el(RangeControl, { label: __('Space between icon and text', 'ncllc-pro'), min: 0, max: 48, value: attrs.iconGap || 12, onChange: function(value) { props.setAttributes({ iconGap: value }); } }),
                        el(RangeControl, { label: __('Space between items', 'ncllc-pro'), min: 0, max: 60, value: attrs.itemGap || 10, onChange: function(value) { props.setAttributes({ itemGap: value }); } })
                    ),
                    el(PanelBody, { title: __('Advanced', 'ncllc-pro'), initialOpen: false }, commonControls(props))
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
        title: __('AJ List Item', 'ncllc-pro'),
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
                    el(PanelBody, { title: __('Icon', 'ncllc-pro'), initialOpen: true },
                        segmented(__('Type', 'ncllc-pro'), attrs.iconType || 'inherit', [
                            { label: __('Inherit', 'ncllc-pro'), value: 'inherit' },
                            { label: __('Icon', 'ncllc-pro'), value: 'icon' },
                            { label: __('Image', 'ncllc-pro'), value: 'image' },
                            { label: __('None', 'ncllc-pro'), value: 'none' }
                        ], function(value) { props.setAttributes({ iconType: value }); }),
                        attrs.iconType === 'icon' ? field(__('Icon', 'ncllc-pro'), attrs.icon, function(value) { props.setAttributes({ icon: value }); }, '→') : null,
                        attrs.iconType === 'image' ? el('div', { className: 'aj-image-control' },
                            attrs.iconImageUrl ? el('img', { src: attrs.iconImageUrl, alt: '' }) : el('div', { className: 'aj-image-control__empty' }, '+'),
                            el(MediaUploadCheck, {}, el(MediaUpload, {
                                onSelect: function(media) { props.setAttributes({ iconImageUrl: media.url }); },
                                allowedTypes: ['image'],
                                render: function(obj) { return el(Button, { variant: 'secondary', onClick: obj.open }, attrs.iconImageUrl ? __('Replace Image', 'ncllc-pro') : __('Choose Image', 'ncllc-pro')); }
                            }))
                        ) : null,
                        el(ToggleControl, { label: __('Link', 'ncllc-pro'), checked: !!attrs.linkEnabled, onChange: function(value) { props.setAttributes({ linkEnabled: value }); } }),
                        attrs.linkEnabled ? urlField(attrs.url, function(value) { props.setAttributes({ url: value }); }) : null
                    ),
                    el(PanelBody, { title: __('Advanced', 'ncllc-pro'), initialOpen: false }, commonControls(props))
                ),
                el('li', styledProps('aj-icon-list-item', attrs),
                    el('span', { className: 'aj-icon-list-item__marker', 'data-icon-type': attrs.iconType || 'inherit', 'data-icon': attrs.icon || '', 'data-icon-image': attrs.iconImageUrl || '', style: attrs.iconType === 'image' && attrs.iconImageUrl ? { backgroundImage: 'url(' + attrs.iconImageUrl + ')' } : {} }),
                    el(RichText, { tagName: attrs.linkEnabled ? 'a' : 'span', className: 'aj-icon-list-item__content', href: attrs.linkEnabled ? attrs.url || '#' : undefined, value: attrs.content, placeholder: __('List item', 'ncllc-pro'), onChange: function(value) { props.setAttributes({ content: value }); } })
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

    function postAttrs(defaultCount) {
        return { count: { type: 'number', default: defaultCount }, showExcerpt: { type: 'boolean', default: true }, showImage: { type: 'boolean', default: true }, buttonText: { type: 'string', default: 'Read More' }, order: { type: 'string', default: 'desc' }, orderBy: { type: 'string', default: 'date' }, columns: { type: 'number', default: 3 } };
    }

    function postControls(props) {
        return [
            el(RangeControl, { label: __('Post count', 'ncllc-pro'), min: 1, max: 12, value: props.attributes.count, onChange: function(value) { props.setAttributes({ count: value }); } }),
            el(RangeControl, { label: __('Columns', 'ncllc-pro'), min: 1, max: 4, value: props.attributes.columns || 3, onChange: function(value) { props.setAttributes({ columns: value }); } }),
            el(SelectControl, { label: __('Order by', 'ncllc-pro'), value: props.attributes.orderBy || 'date', options: [{ label: __('Date', 'ncllc-pro'), value: 'date' }, { label: __('Title', 'ncllc-pro'), value: 'title' }, { label: __('Menu order', 'ncllc-pro'), value: 'menu_order' }], onChange: function(value) { props.setAttributes({ orderBy: value }); } }),
            el(SelectControl, { label: __('Order', 'ncllc-pro'), value: props.attributes.order || 'desc', options: [{ label: __('Descending', 'ncllc-pro'), value: 'desc' }, { label: __('Ascending', 'ncllc-pro'), value: 'asc' }], onChange: function(value) { props.setAttributes({ order: value }); } }),
            el(ToggleControl, { label: __('Show featured image', 'ncllc-pro'), checked: props.attributes.showImage !== false, onChange: function(value) { props.setAttributes({ showImage: value }); } }),
            el(ToggleControl, { label: __('Show excerpt', 'ncllc-pro'), checked: !!props.attributes.showExcerpt, onChange: function(value) { props.setAttributes({ showExcerpt: value }); } }),
            field(__('Button text', 'ncllc-pro'), props.attributes.buttonText, function(value) { props.setAttributes({ buttonText: value }); })
        ];
    }

    dynamicBlock('ajnanda/posts', __('AJ Posts', 'ncllc-pro'), 'admin-post', postAttrs(3), postControls);
    dynamicBlock('ajnanda/post-grid', __('AJ Post Grid', 'ncllc-pro'), 'grid-view', postAttrs(6), postControls);
    dynamicBlock('ajnanda/post-carousel', __('AJ Post Carousel Placeholder', 'ncllc-pro'), 'images-alt2', Object.assign(postAttrs(6), { autoplay: { type: 'boolean', default: false }, delay: { type: 'number', default: 4 } }), function(props) {
        return postControls(props).concat([el(ToggleControl, { label: __('Autoplay', 'ncllc-pro'), checked: !!props.attributes.autoplay, onChange: function(value) { props.setAttributes({ autoplay: value }); } }), el(RangeControl, { label: __('Delay seconds', 'ncllc-pro'), min: 1, max: 12, value: props.attributes.delay || 4, onChange: function(value) { props.setAttributes({ delay: value }); } })]);
    });
    dynamicBlock('ajnanda/post-timeline', __('AJ Post Timeline', 'ncllc-pro'), 'backup', Object.assign(postAttrs(5), { dateFormat: { type: 'string', default: 'M j, Y' } }), function(props) {
        return postControls(props).concat(field(__('Date format', 'ncllc-pro'), props.attributes.dateFormat, function(value) { props.setAttributes({ dateFormat: value }); }, 'M j, Y'));
    });
    dynamicBlock('ajnanda/search', __('AJ Search', 'ncllc-pro'), 'search', {
        placeholder: { type: 'string', default: 'Search...' },
        buttonText: { type: 'string', default: 'Search' },
        layout: { type: 'string', default: 'inline' },
        buttonPosition: { type: 'string', default: 'right' }
    }, function(props) {
        return [
            field(__('Placeholder', 'ncllc-pro'), props.attributes.placeholder, function(value) { props.setAttributes({ placeholder: value }); }),
            field(__('Button text', 'ncllc-pro'), props.attributes.buttonText, function(value) { props.setAttributes({ buttonText: value }); }),
            el(SelectControl, {
                label: __('Layout', 'ncllc-pro'),
                value: props.attributes.layout || 'inline',
                options: [
                    { label: __('Inline', 'ncllc-pro'), value: 'inline' },
                    { label: __('Stacked', 'ncllc-pro'), value: 'stacked' }
                ],
                onChange: function(value) { props.setAttributes({ layout: value }); }
            }),
            el(SelectControl, {
                label: __('Button position', 'ncllc-pro'),
                value: props.attributes.buttonPosition || 'right',
                options: [
                    { label: __('Right', 'ncllc-pro'), value: 'right' },
                    { label: __('Left', 'ncllc-pro'), value: 'left' }
                ],
                onChange: function(value) { props.setAttributes({ buttonPosition: value }); }
            })
        ];
    });
    dynamicBlock('ajnanda/nav-menu', __('AJ Menu/Nav Menu', 'ncllc-pro'), 'menu', {
        menuLocation: { type: 'string', default: 'primary' },
        layout: { type: 'string', default: 'horizontal' },
        depth: { type: 'number', default: 2 },
        dropdownOnHover: { type: 'boolean', default: true }
    }, function(props) {
        return [
            el(SelectControl, { label: __('Menu location', 'ncllc-pro'), value: props.attributes.menuLocation, options: [{ label: 'Primary', value: 'primary' }, { label: 'Footer', value: 'footer' }], onChange: function(value) { props.setAttributes({ menuLocation: value }); } }),
            el(SelectControl, { label: __('Layout', 'ncllc-pro'), value: props.attributes.layout || 'horizontal', options: [{ label: __('Horizontal', 'ncllc-pro'), value: 'horizontal' }, { label: __('Vertical', 'ncllc-pro'), value: 'vertical' }], onChange: function(value) { props.setAttributes({ layout: value }); } }),
            el(RangeControl, { label: __('Menu depth', 'ncllc-pro'), min: 1, max: 4, value: props.attributes.depth || 2, onChange: function(value) { props.setAttributes({ depth: value }); } }),
            el(ToggleControl, { label: __('Open submenu on hover', 'ncllc-pro'), checked: props.attributes.dropdownOnHover !== false, onChange: function(value) { props.setAttributes({ dropdownOnHover: value }); } })
        ];
    });
    dynamicBlock('ajnanda/table-of-contents', __('AJ Table of Contents', 'ncllc-pro'), 'list-view', {
        title: { type: 'string', default: 'On this page' },
        minLevel: { type: 'number', default: 2 },
        maxLevel: { type: 'number', default: 3 },
        ordered: { type: 'boolean', default: true },
        collapsible: { type: 'boolean', default: false }
    }, function(props) {
        return [
            field(__('Title', 'ncllc-pro'), props.attributes.title, function(value) { props.setAttributes({ title: value }); }),
            el(RangeControl, { label: __('Minimum heading level', 'ncllc-pro'), min: 1, max: 6, value: props.attributes.minLevel || 2, onChange: function(value) { props.setAttributes({ minLevel: value }); } }),
            el(RangeControl, { label: __('Maximum heading level', 'ncllc-pro'), min: 1, max: 6, value: props.attributes.maxLevel || 3, onChange: function(value) { props.setAttributes({ maxLevel: value }); } }),
            el(ToggleControl, { label: __('Ordered list', 'ncllc-pro'), checked: props.attributes.ordered !== false, onChange: function(value) { props.setAttributes({ ordered: value }); } }),
            el(ToggleControl, { label: __('Collapsible placeholder', 'ncllc-pro'), checked: !!props.attributes.collapsible, onChange: function(value) { props.setAttributes({ collapsible: value }); } })
        ];
    });
    dynamicBlock('ajnanda/taxonomy-list', __('AJ Taxonomy List', 'ncllc-pro'), 'category', {
        taxonomy: { type: 'string', default: 'category' },
        layout: { type: 'string', default: 'pills' },
        hideEmpty: { type: 'boolean', default: false },
        showCount: { type: 'boolean', default: false }
    }, function(props) {
        return [
            el(SelectControl, { label: __('Taxonomy', 'ncllc-pro'), value: props.attributes.taxonomy, options: [{ label: 'Categories', value: 'category' }, { label: 'Tags', value: 'post_tag' }], onChange: function(value) { props.setAttributes({ taxonomy: value }); } }),
            el(SelectControl, { label: __('Layout', 'ncllc-pro'), value: props.attributes.layout || 'pills', options: [{ label: __('Pills', 'ncllc-pro'), value: 'pills' }, { label: __('List', 'ncllc-pro'), value: 'list' }, { label: __('Inline', 'ncllc-pro'), value: 'inline' }], onChange: function(value) { props.setAttributes({ layout: value }); } }),
            el(ToggleControl, { label: __('Hide empty terms', 'ncllc-pro'), checked: !!props.attributes.hideEmpty, onChange: function(value) { props.setAttributes({ hideEmpty: value }); } }),
            el(ToggleControl, { label: __('Show post count', 'ncllc-pro'), checked: !!props.attributes.showCount, onChange: function(value) { props.setAttributes({ showCount: value }); } })
        ];
    });
    dynamicBlock('ajnanda/login-placeholder', __('AJ Login Placeholder', 'ncllc-pro'), 'admin-users', {
        loggedOutText: { type: 'string', default: 'Login area placeholder.' },
        loginText: { type: 'string', default: 'Log In' },
        logoutText: { type: 'string', default: 'Log Out' }
    }, function(props) {
        return [
            field(__('Logged out text', 'ncllc-pro'), props.attributes.loggedOutText, function(value) { props.setAttributes({ loggedOutText: value }); }),
            field(__('Login button text', 'ncllc-pro'), props.attributes.loginText, function(value) { props.setAttributes({ loginText: value }); }),
            field(__('Logout button text', 'ncllc-pro'), props.attributes.logoutText, function(value) { props.setAttributes({ logoutText: value }); })
        ];
    });
})(window.wp);
