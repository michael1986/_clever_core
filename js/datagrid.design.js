function DataGridDesign(prefix) {
    var DataGridDesign = this;
    DataGridDesign.prefix = prefix;

    var colorOff = '#f3f8fa';
    var colorOn = '#e7f4fa';
    $('.' + DataGridDesign.prefix + 'DataGridTr').each(function () {
        var $container = $(this);
        $(this).find('.itemCheckbox').change(function (e) {
            if ($(this).attr('checked')) {
                $container.css({background: colorOn});
            }
            else {
                $container.css({background: colorOff});
            }
        });
    }).hover(
        function () {
            if (!$(this).find('.itemCheckbox').attr('checked')) {
                $(this).css({background: colorOn});
            }
        }, function () {
            if (!$(this).find('.itemCheckbox').attr('checked')) {
                $(this).css({background: colorOff});
            }
        }
    ).click(function (e) {
        if (!$(e.target).closest('a, input').length) {
            var $check = $(this).find('.itemCheckbox');
            if ($check.attr('checked')) {
                $check.removeAttr('checked');
            }
            else {
                $check.attr('checked', 'checked');
            }
            $check.trigger('change');
        }
    });
}
