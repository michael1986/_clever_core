<select size="{$size}" name="{$name}" id="{$id}"{?$class} class="{$class}"{/?}{?$multiple} multiple{/?}>
    {*$options:$option}
    <option value="{$option[value]}"{?$option[selected]} selected="selected"{/?}{?$option[class]} class="{$option[class]}"{/?}>{$option[label]}</option>
    {/*}
</select>