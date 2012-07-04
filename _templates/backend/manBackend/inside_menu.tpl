<div id="BackendMenuContainer">
    {*$main_menu:$page}
        {?$page[item_on] || $page[item_parent]}
            <div class="backendMenuItemOnContent">
                {$page[page_title]}
            </div>
        {!}
            <a href="{$page[link]}" class="backendMenuItemOffContent">
                {$page[page_title]}
            </a>
        {/?}
    {/*}
    <a href="javascript:void(0)" id="BackendSwitchFullScreen" class="backendSwitchFullScreen">
        {$lang[EXPAND]}
    </a>
</div>
