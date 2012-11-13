<div class="form01FieldContainer">
    <div class="form01FieldTitle{?$error} form01FieldError{/?}">
        <label for="{$id}">
            {$title}{?$mandatory}<span class="form01FieldMandatory">*</span>{/?}
        </label>
    </div>
    <div class="form01FieldField{?$error} form01FieldError{/?}">
        <input type="checkbox" name="{$name}" id="{$id}" value="{$significance}" class="{?$class}{$class}{!}form01FieldCheckboxInput{/?}"{?$value} checked="checked"{/?}{?$style} style="{$style}"{/?}>
        {?$note}
        <label for="{$id}" class="form01FieldNote">
            {$note}
        </label>{/?}
    </div>
</div>
