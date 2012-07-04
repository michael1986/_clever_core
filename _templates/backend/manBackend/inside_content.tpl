<div id="BackendFullScreenPanel" class="backendPanel01" style="display: none; position: absolute; top: 0">
    <div id="BackendFullScreenControlsPanelContainer" style="padding: 1px; float: left">
    </div>
    <div style="padding: 1px; float: right">
    {@control.tpl:link=javascript:void(0), id=BackendSwitchNormalView, title={$lang[CONTRACT]}}
    </div>
</div>
{@inside_columns.tpl}

<div style="{?$left_column_present}margin-left: 200px; {/?}">
    <div id="BackendContentContainer" class="backendRightColumnContainer" style="padding: 10px 25px">
    {$content}
    </div>
</div>
</div>