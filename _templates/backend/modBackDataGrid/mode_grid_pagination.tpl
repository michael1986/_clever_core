<?php /* pagination */ ?>
{?$pagination.pages}
<div class="grid01Pagination">
{?$pagination.previous_link}
    <a href="{$pagination.previous_link}" class="{?$ajax}{$prefix_data_grid}DGCtrlAjax {/?}{$prefix_data_grid}DGPagination grid01PaginationPrev">Previous</a>
{/?}
{*$pagination.pages:$page}
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
{?$pagination.next_link}
    <a href="{$pagination.next_link}" class="{?$ajax}{$prefix_data_grid}DGCtrlAjax {/?}{$prefix_data_grid}DGPagination grid01PaginationNext">Next</a>
{/?}
</div>
{/?}
