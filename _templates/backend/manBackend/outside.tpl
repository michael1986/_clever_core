<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html id="BackendHtml">
<head>
    <link href="<?php echo $this->_hlink(array('lite_module' => 'general/liteCss', 'name' => 'backend.css'), 'lite') ?>" rel="stylesheet" type="text/css">
    <link href="<?php echo $this->_hlink(array('lite_module' => 'general/liteCss', 'name' => $lang['CSS']), 'lite') ?>" rel="stylesheet" type="text/css">
    <title>{$page_title}</title>
    <script type="text/javascript">
    if (parent.frames.length) {
        parent.location.href = window.location.href;
    }
    </script>
    {@jquery_js.tpl}
</head>
<body id="BackendBody">

    <div id="BackendHeadContainer">
        {@shred_logo.tpl}
    </div>
    <div id="BackendMenuContainer">
        <div class="backendMenuItemOnContent">
            {$lang[SIGNIN_TITLE]}
        </div>
    </div>
    <div class="backendBox01 backendSigninContentContainer">
        <div class="backendTitle01">&nbsp;</div>
        <div id="BackendSigninContainer">
            <div id="BackendSigninFormContainer">
                {?$form.errors}
                <div id="BackendSigninFormErrorsContainer">
                    {*$form.errors:$e}
                    <div>{$e}</div>
                    {/*}
                </div>
                {/?}
                {@form_system_start.tpl:$form}
                <h3>
                    {$lang[ENTER_LOGIN]} <span>*</span>
                </h3>
                <div class="backendSigninFieldContainer">
                    {@input_text.tpl:$form.fields[user_login], class=backendSigninEnterLogin}
                </div>
                <p>{$lang[ENTER_LOGIN_NOTE]}</p>
                <h3>
                    {$lang[ENTER_PASSWORD]} <span>*</span>
                </h3>
                <div class="backendSigninFieldContainer">
                    {@input_password.tpl:$form.fields[user_password], class=backendSigninEnterLogin}
                </div>
                <p>{$lang[ENTER_PASSWORD_NOTE]}</p>
                <div class="backendSigninSubmitContainer">
                    {@input_image.tpl:$form.submits[ok], class=backendBtnSignin}
                </div>
                {@form_system_end.tpl:$form}
            </div>
            <div class="backendBox02 backendSigninInstructions">
                {$lang[GREETING]}
            </div>
        </div>
    </div>

    <div id="BackendCopyrContainer">
        <div class="poweredByCC"></div>
        Copyright &copy; 2010-<?php echo date('Y') ?> CleverCore. All rights reserved.
    </div>

</body>
</html>


