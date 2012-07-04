{?$pages}
<div class="grid01Pagination">
    {?$previous_link}
        <a href="{$previous_link}" class="{?$ajax}{$prefix_data_grid}DGCtrlAjax {/?}{$prefix_data_grid}DGPagination grid01PaginationPrev">Previous</a>
    {/?}
    {*$pages:$page}
        {?$page[skip]}
        <div class="grid01PaginationSkip">...</div>
        {!}
            {?$page[is_current]}
                <div class="grid01PaginationItemOn">{$page[index]}</div>
            {!}
                <a href="{$page[link]}" class="{?$ajax}{$prefix_data_grid}DGCtrlAjax {/?}{$prefix_data_grid}DGPagination grid01PaginationItemOff">{$page[index]}</a>
            {/?}
        {/?}
    {/*}
    {?$next_link}
        <a href="{$next_link}" class="{?$ajax}{$prefix_data_grid}DGCtrlAjax {/?}{$prefix_data_grid}DGPagination grid01PaginationNext">Next</a>
    {/?}
</div>
{/?}