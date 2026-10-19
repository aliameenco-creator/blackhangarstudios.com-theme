(function (wp) {
    const el = wp.element.createElement;
    [['portfolio', 'Portfolio display'], ['project', 'Production detail']].forEach(function (item) {
        wp.blocks.registerBlockType('black-hangar/' + item[0], {
            apiVersion: 3, title: item[1], icon: 'format-video', category: 'widgets',
            attributes: item[0] === 'portfolio' ? { mode: {type: 'string', default: 'rows'}, twoRows: {type: 'boolean', default: true}, homeOnly: {type: 'boolean', default: false} } : { titleSize: {type: 'number', default: 64} },
            usesContext: ['postId', 'postType'],
            edit: function (props) {
                return el('div', wp.blockEditor.useBlockProps(),
                    item[0] === 'portfolio' ? el(wp.blockEditor.InspectorControls, null,
                        el(wp.components.PanelBody, {title: 'Portfolio display'},
                            el(wp.components.SelectControl, {label: 'Layout', value: props.attributes.mode, options: [{label: 'Latest productions — two moving rows', value: 'latest'}, {label: 'Moving rows', value: 'rows'}, {label: 'Poster grid', value: 'grid'}], onChange: value => props.setAttributes({mode: value})}),
                            el(wp.components.ToggleControl, {label: 'Two opposite rows', checked: props.attributes.twoRows, onChange: value => props.setAttributes({twoRows: value})}),
                            el(wp.components.ToggleControl, {label: 'Homepage selections only', checked: props.attributes.homeOnly, onChange: value => props.setAttributes({homeOnly: value})})
                        )) : el(wp.blockEditor.InspectorControls, null,
                            el(wp.components.PanelBody, {title: 'Production heading'},
                                el(wp.components.RangeControl, {label: 'Title size (desktop px)', min: 32, max: 120, value: props.attributes.titleSize, onChange: value => props.setAttributes({titleSize: value})})
                            )),
                    el(wp.serverSideRender, {block: 'black-hangar/' + item[0], attributes: props.attributes})
                );
            }, save: function () { return null; }
        });
    });
})(window.wp);
