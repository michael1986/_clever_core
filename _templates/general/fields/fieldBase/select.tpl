<div class="form01FieldContainer">
    <div class="form01FieldTitle{?$error} form01FieldError{/?}">
        {$title}{?$mandatory}<span class="form01FieldMandatory">*</span>{/?}:
    </div>
    <div class="form01FieldField{?$error} form01FieldError{/?}">
        <select size="{$size}" name="{$name}" id="{$id}" class="{?$class}{$class}{!}form01FieldSelectInput{/?}"{?$multiple} multiple{/?}>
        {*$options:$option}
        <option value="{$option[value]}"{?$option[selected]} selected="selected"{/?} class="{?$option[class]}{$option[class]}{!}form01FieldSelectOption{/?}">{$option[label]}</option>
        {/*}
        </select>
        {?$note}<br><span class="form01FieldNote">{$note}</span>{/?}
    </div>
</div>
