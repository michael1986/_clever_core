<div class="form01FieldContainer">
    <div class="form01FieldTitle{?$error} form01FieldError{/?}">
        {$title}{?$mandatory}<span class="form01FieldMandatory">*</span>{/?}:
    </div>
    <div class="form01FieldField{?$error} form01FieldError{/?}">
        <textarea name="{$name}" id="{$id}" class="{?$class}{$class}{!}form01FieldTextareaInput{/?}"{?$style} style="{$style}"{/?}>{$value}</textarea>
        {?$note}<br><span class="form01FieldNote">{$note}</span>{/?}
    </div>
</div>
