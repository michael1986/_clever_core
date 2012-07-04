        {?$breadcrumbs && sizeof($breadcrumbs) > 1}
            <div class="backendBreadcrumbsContainer">
                {*$breadcrumbs:$link}
                    {?!$_[link][first]}
                    <div class="backendBreadcrumbsSeparator"></div>
                    {/?}
                    {?$_[link][last]}
                    <div href="{$link[link]}" class="backendBreadcrumbsTitle">{$link[title]}</div>
                    {!}
                    <a href="{$link[link]}" class="backendBreadcrumbsHref backendHref">{$link[title]}</a>
                    {/?}
                {/*}
            </div>
        {/?}
