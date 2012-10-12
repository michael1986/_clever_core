{@form_settings.tpl}
{@form_system_start.tpl}
{?$title}
<div class="form01PopupTitle">
    {$title}
</div>
{/?}
<div class="popupModalContentContainer form01PopupContainer">
    {@errors.tpl}
    {@infos.tpl}
    {@fields_html.tpl}
</div>
{@submits_html.tpl}
{@form_system_end.tpl}

