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
    <!-- 
    <div id="BackendFavoritesContainer" class="backendFavoritesContainer">
        <div href="javascript:void(0)" class="backendShowFavorites">
            {$lang[FAVORITES]}
        </div>
        <div id="BackendFavoriteList" class="backendFavoriteList">
            {*$favorites:$f}
            <div>
                <a href="{$f[f_link]}" class="backendFavoriteItem">{$f[f_title]}</a>
            </div>
            {/*}
            <div>
                <a href="javascript:void(0)" id="BackendAddToFavorites" class="backendAddToFavorites">{$lang[ADD_TO_FAVORITES_MENU_ITEM]}</a>
            </div>
        </div>
    </div>
    -->
    <a href="javascript:void(0)" id="BackendSwitchFullScreen" class="backendSwitchFullScreen">
        {$lang[EXPAND]}
    </a>
</div>
