<div class="form01FieldContainer">
    <div class="form01FieldTitle{?$error} form01FieldError{/?}">
        {$title}{?$mandatory}<span class="form01FieldMandatory">*</span>{/?}:
    </div>
    <div class="form01FieldField{?$error} form01FieldError{/?}">
        {*$options:$option}
        {?$_[option][queue]}<br>{/?}
        <input type="checkbox" id="{$option[id]}" name="{$name}" value="{$option[value]}"{?$option[selected]} checked="checked"{/?} class="{?$option[class]}{$option[class]}{!}form01FieldCheckboxGroupInput{/?}"> <label for="{$option[id]}">{$option[label]}</label>
        {/*}
        {?$note}<br><span class="form01FieldNote">{$note}</span>{/?}
    </div>
</div>
