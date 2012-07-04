<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html id="BackendHtml">
<head>
    <link href="<?php echo $this->_hlink(array('lite_module' => 'general/liteCss', 'name' => 'backend.css'), 'lite') ?>" rel="stylesheet" type="text/css">
    <link href="<?php echo $this->_hlink(array('lite_module' => 'general/liteCss', 'name' => $lang['CSS']), 'lite') ?>" rel="stylesheet" type="text/css">
    {?$css}
        <link href="{$_baseurl}{$css}" rel="stylesheet" type="text/css">
    {/?}
    <title>{$page_title}</title>
    <script type="text/javascript" language="javascript">
        if (parent.frames.length) {
        parent.location.href = window.location.href;
        }
    </script>
{@jquery_js.tpl}
{@jquery_cookie_js.tpl}
    <script type="text/javascript">
        $(function () {
            var $Body = $('#BackendBody');
            var $FullScreenPanel = $('#BackendFullScreenPanel');
            var $ContentContainer = $('#BackendContentContainer');
            var state = $.cookie('state');
            if (!state) {
                state = 'normal';
            }

            var SwitchNormalView = function () {
                $FullScreenPanel.hide();
                $ContentContainer.css({
                    position: 'static',
                    height: 'auto',
                    width: 'auto'
                });
                state = 'normal';
                $.cookie('state', state);
                if (typeof(SwitchNormalViewCallback) == 'function') {
                    SwitchNormalViewCallback();
                }
            };

            var SwitchFullScreen = function () {
                $FullScreenPanel.show();
                var windowHeight = SoftInt($Body.height())
                    - SoftInt($FullScreenPanel.height())
                    - SoftInt($ContentContainer.css('paddingTop'))
                    - SoftInt($ContentContainer.css('paddingBottom'))
                    - SoftInt($ContentContainer.css('marginTop'))
                    - SoftInt($ContentContainer.css('marginBottom'))
                    - SoftInt($ContentContainer.css('borderTopWidth'))
                    - SoftInt($ContentContainer.css('borderBottomWidth'));
                var contentHeight = $ContentContainer.height();
                var windowWidth = SoftInt($Body.width())
                    - SoftInt($ContentContainer.css('paddingLeft'))
                    - SoftInt($ContentContainer.css('paddingRight'))
                    - SoftInt($ContentContainer.css('marginLeft'))
                    - SoftInt($ContentContainer.css('marginRight'))
                    - SoftInt($ContentContainer.css('borderLeftWidth'))
                    - SoftInt($ContentContainer.css('borderRightWidth'));
                var contentWidth = $ContentContainer.width();
                $ContentContainer.css({
                    position: 'absolute',
                    left: 0,
                    top: $FullScreenPanel.height(),
                    width: windowWidth < contentWidth ? contentWidth : windowWidth,
                    height: windowHeight < contentHeight ? contentHeight : windowHeight,
                    zIndex: 1
                });
                state = 'full';
                $.cookie('state', state);

                if (typeof(SwitchFullScreenCallback) == 'function') {
                    SwitchFullScreenCallback();
                }
            };

            var SoftInt = function (string) {
                var ret = parseInt(string);
                if (ret) {
                    return ret;
                }
                else {
                    return 0;
                }
            }

            $(window).resize(function () {
                if (state == 'normal') {
                    SwitchNormalView();
                }
                else {
                    SwitchFullScreen();
                }
            }).trigger('resize');

            $('#BackendSwitchFullScreen').click(function () {
                SwitchFullScreen();
            });
            $('#BackendSwitchNormalView').click(function () {
                SwitchNormalView();
            });
        });
    </script>
</head>