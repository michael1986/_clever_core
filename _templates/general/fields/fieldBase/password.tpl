<div class="form01FieldContainer">
    <div class="form01FieldTitle{?$error} form01FieldError{/?}">
        {$title}{?$mandatory}<span class="form01FieldMandatory">*</span>{/?}:
    </div>
    <div class="form01FieldField{?$error} form01FieldError{/?}">
        <input type="password" id="{$id}" name="{$name}" value="{$value}" class="{?$class}{$class}{!}form01FieldPasswordInput{/?}"{?$style} style="{$style}"{/?}>
        {?$note}<br><span class="form01FieldNote">{$note}</span>{/?}
    </div>
    <div style="clear: both"></div>
</div>
