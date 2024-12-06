const { registerBlockType } = wp.blocks;
const { createElement } = wp.element;
const { __ } = wp.i18n;

// Register a custom block category.
wp.domReady(() => {
    // Check if the category already exists.
    const categories = wp.blocks.getCategories();

    if (!categories.some((category) => category.slug === 'slm-plus')) {
        // Add the custom category.
        wp.blocks.setCategories([
            ...categories,
            {
                slug: 'slm-plus',
                title: __('SLM Plus', 'slm-plus'),
                icon: 'admin-network', // Dashicon or a custom icon.
            },
        ]);
    }
});


// Register the block in the "SLM Plus" category.
registerBlockType('slm-plus/forgot-license', {
    title: __('Forgot License', 'slm-plus'),
    icon: 'lock', // Dashicon or custom icon for the block.
    category: 'slm-plus', // Assign to the SLM Plus category.
    attributes: {},

    edit: () => {
        // Create a more realistic preview for the editor.
        return createElement(
            'div',
            { className: 'slm-forgot-license-preview' },
            createElement(
                'form',
                { className: 'slm-forgot-license-form' },
                createElement(
                    'label',
                    { htmlFor: 'slm-email' },
                    __('Enter your email address:', 'slm-plus')
                ),
                createElement('input', {
                    type: 'email',
                    id: 'slm-email',
                    placeholder: __('example@domain.com', 'slm-plus'),
                    disabled: true, // Disable interaction in the editor.
                }),
                createElement(
                    'button',
                    { type: 'button', disabled: true },
                    __('Retrieve License', 'slm-plus')
                )
            )
        );
    },

    save: () => {
        // The saved output will still be the shortcode for frontend rendering.
        return createElement('p', {}, '[slm_forgot_license]');
    },
});


// Register the "List Licenses" block.
registerBlockType('slm-plus/list-licenses', {
    title: __('List Licenses', 'slm-plus'),
    icon: 'list-view', // Dashicon for the block.
    category: 'slm-plus', // Assign to the SLM Plus category.
    attributes: {},

    edit: () => {
        return createElement(
            'div',
            { className: 'slm-list-licenses-preview' },
            createElement(
                'p',
                { className: 'slm-list-licenses-preview-message' },
                __('This block will display a list of licenses associated with the logged-in user.', 'slm-plus')
            ),
            createElement(
                'ul',
                { className: 'slm-list-licenses-placeholder' },
                createElement('li', {}, __('License Key: ************', 'slm-plus')),
                createElement('li', {}, __('Product: Example Product', 'slm-plus')),
                createElement('li', {}, __('Status: Active', 'slm-plus')),
                createElement('li', {}, __('Expiry Date: 2024-12-31', 'slm-plus'))
            )
        );
    },

    save: () => {
        // Outputs the shortcode for rendering on the frontend.
        return createElement('p', {}, '[slm_list_licenses]');
    },
});
