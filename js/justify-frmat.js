(function (wp) {
    const { registerFormatType, toggleFormat } = wp.richText;
    const { RichTextToolbarButton } = wp.blockEditor;
    const { createElement } = wp.element;

    const justifyIcon = createElement(
        'svg',
        {
            width: 24,
            height: 24,
            viewBox: '0 0 24 24',
            xmlns: 'http://www.w3.org/2000/svg'
        },
        createElement('path', {
            d: 'M4 5h16v1.5H4V5zm0 4h16v1.5H4V9zm0 4h16v1.5H4V13zm0 4h16v1.5H4V17z'
        })
    );

    registerFormatType('custom/text-justify', {
        title: 'Justify',
        tagName: 'span',
        className: 'text-justify',
        edit: function (props) {
            return createElement(RichTextToolbarButton, {
                icon: justifyIcon,
                title: 'Justify',
                isActive: props.isActive,
                onClick: function () {
                    props.onChange(
                        toggleFormat(props.value, {
                            type: 'custom/text-justify'
                        })
                    );
                }
            });
        }
    });
})(window.wp);