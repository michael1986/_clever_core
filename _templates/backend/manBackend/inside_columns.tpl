<?php $left_column_present = false; ?>
<div class="backendLeftRightColumnContainer">
{*$main_menu:$page}
    {?($page[item_on] || $page[item_parent]) && $page[pages]}
        <?php $left_column_present = true; ?>
        <div class="backendLeftColumnContainer">
        {*$page[pages]:$page2}
            {?$page2[pages]}
                <div class="backendLeftMenuCategory">{$page2[page_title]}</div>
                {*$page2[pages]:$page3}
                    {?$page3[item_on]}
                        <div href="{$page3[link]}" class="backendLeftMenuItemOn">{$page3[page_title]}</div>
                    {!}
                        <a href="{$page3[link]}" class="backendLeftMenuItemOff">{$page3[page_title]}</a>
                    {/?}
                {/*}
            {!}
                {?$page2[item_on]}
                    <div href="{$page2[link]}" class="backendLeftMenuItemOn">{$page2[page_title]}</div>
                {!}
                    <a href="{$page2[link]}" class="backendLeftMenuItemOff">{$page2[page_title]}</a>
                {/?}
            {/?}
        {/*}
</div>
{/?}
{/*}