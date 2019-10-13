document.querySelectorAll('.select2').forEach(async el => {
    const { default: $ } = await import('jquery');

    if (!window.$) {
        window.$ = window.jQuery = $;
    }

    await Promise.all([
        import('select2'),
        import('select2/dist/css/select2.css'),
    ]);

    $(el).select2();
});
