<div class="form01FieldContainer">
    <div class="form01FieldTitle{?$error} form01FieldError{/?}">
        {$title}{?$mandatory}<span class="form01FieldMandatory">*</span>{/?}:
    </div>
    <div class="form01FieldField{?$error} form01FieldError{/?}">
        <input type="hidden" name="{$name}[original]" value="{$value[original]}">
        <input type="hidden" name="{$name}[current]" value="{$value[current]}">
        {?$value[current]}
            {$lang[EXISTING_FILE]}:
            <b>{$value[current]}</b> (<input type="checkbox" id="{$id}_remove" name="{$name}[remove]"> <label for="{$id}_remove">{$lang[REMOVE]}</label>)<br>
        {/?}
        <input type="file" id="{$id}" name="{$name}[new]" class="{?$class}{$class}{!}backendFieldFileInput{/?}"{?$style} style="{$style}"{/?}>
        {?$note}<br><span class="form01FieldNote">{$note}</span>{/?}
    </div>
</div>
