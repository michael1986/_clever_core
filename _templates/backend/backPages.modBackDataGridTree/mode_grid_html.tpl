{@mode_grid_data.tpl}
{@mode_grid_pagination.tpl}
<script type="text/javascript">
(function () {
    // find all open subpages
    var openPagesList = $.cookie('openPagesList');
    if (openPagesList) {
        openPagesList = openPagesList.split(' ');
    }
    else {
        openPagesList = [];
    }

    // functions

    var getPageDataDom = function (page_id) {
        return $('#{$prefix_data_grid}DataGrid div.rowData[page_id=' + page_id + ']');
    }

    var openSubPages = function ($page_data_dom) {
        var page_id = $page_data_dom.attr('page_id');

        $('#{$prefix_data_grid}DataGrid div.rowData[page_parent_id=' + page_id + ']').closest('tr').show();

        var className = getClassName($page_data_dom, 'Minus');
        $page_data_dom.find('div.rowIcon').removeClass().addClass('rowIcon').addClass(className);

        if (!openPagesList) {
            openPagesList = [];
        }
        var found = false;
        for (var i = 0; i < openPagesList.length; i++) {
            if (openPagesList[i] == page_id) {
                found = true;
                break;
            }
        }
        if (!found) {
            openPagesList[openPagesList.length] = page_id;
            $.cookie('openPagesList', openPagesList.join(' '));
        }
    }

    var closeSubPages = function ($page_data_dom) {
        var page_id = $page_data_dom.attr('page_id');

        if (!openPagesList) {
            openPagesList = [];
        }
        for (var i = 0; i < openPagesList.length; i++) {
            if (openPagesList[i] == page_id) {
                openPagesList.splice(i, 1);
            }
        }
        $.cookie('openPagesList', openPagesList.join(' '));

        var $subPages = $('#{$prefix_data_grid}DataGrid div[page_parent_id=' + page_id + ']');
        if ($subPages.length) {
            $subPages.each(function () {
                $(this).closest('tr').hide();
                // $(this).closest('tr').slideUp();
                closeSubPages($(this));
            });

            var className = getClassName($page_data_dom, 'Plus');

            $page_data_dom.find('div.rowIcon').removeClass().addClass('rowIcon').addClass(className);
        }
    }

    var getClassName = function ($page_data_dom, suff) {
        var has_content = $page_data_dom.attr('page_module');
        var className = 'backendItem';
        if (has_content) {
            className += 'Page';
        }
        else {
            className += 'Folder';
        }
        if (suff) {
            className += suff;
        }
        return className;
    }

    // main routine

    // close all subpages
    var $subPages = $('#{$prefix_data_grid}DataGrid div.rowData[page_parent_id!=0]');
    $subPages.closest('tr').css({
        display: 'none'
    });

    // open all open subpages and draw icons
    $('#{$prefix_data_grid}DataGrid div.rowData').each(function () {
        var $page_data_dom = $(this);
        var page_id = $page_data_dom.attr('page_id');

        if ($('#{$prefix_data_grid}DataGrid div.rowData[page_parent_id=' + page_id + ']').length) {
            $page_data_dom.find('div.rowIcon').click(function (e) {
                e.stopPropagation();

                var open = false;
                for (var i = 0; i < openPagesList.length; i++) {
                    if (openPagesList[i] == page_id) {
                        open = true;
                        break;
                    }
                }
                if (open) {
                    closeSubPages($page_data_dom);
                }
                else {
                    openSubPages($page_data_dom);
                }
            });
            var found = false;
            for (var i = 0; i < openPagesList.length; i++) {
                if (openPagesList[i] == page_id) {
                    openSubPages($page_data_dom);
                    found = true;
                    break;
                }
            }
            if (!found) {
                var className = getClassName($page_data_dom, 'Plus');
                $page_data_dom.find('div.rowIcon').removeClass().addClass('rowIcon').addClass(className);
            }
        }
        else {
            var className = getClassName($page_data_dom);
            $page_data_dom.find('div.rowIcon').removeClass().addClass('rowIcon').addClass(className);
        }
    });

})();
</script>
