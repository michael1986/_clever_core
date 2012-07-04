<div class="form01FieldContainer">
    <div class="form01FieldTitle{?$error} form01FieldError{/?}">
        <label for="{$id}{$significance}">
            {$title}{?$mandatory}<span class="form01FieldMandatory">*</span>{/?}
        </label>
    </div>
    <div class="form01FieldField{?$error} form01FieldError{/?}">
        <input type="radio" name="{$name}" id="{$id}{$significance}" value="{$significance}" class="{?$class}{$class}{!}form01FieldCheckboxInput{/?}"{?$value} checked="checked"{/?}>
        {?$note}
        <label for="{$id}{$significance}" class="form01FieldNote">
            {$note}
        </label>{/?}
    </div>
</div>
