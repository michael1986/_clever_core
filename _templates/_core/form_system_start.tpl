        <form action="{$action}" id="{$id}" method="{$method}"{?$enctype=='multipart'} enctype="multipart/form-data"{/?}>
            {*$hidden_fields:$field}
                {$field}
            {/*}
