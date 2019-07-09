import $ from 'jquery';

$('.select2').each(async function () {
    await Promise.all([
        import('select2'),
        import('select2/dist/css/select2.css'),
    ]);

    $(this).select2();
});
