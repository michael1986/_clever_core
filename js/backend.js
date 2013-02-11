function Backend(data) {
    var backendInstance = this;

    backendInstance.linkAddToFavorites = data.linkAddToFavorites;
    backendInstance.backendInstance = data.backendInstance;
    backendInstance.popupModalInstance = new PopupModal();
    backendInstance.$AddFavContainer = $('<div>').appendTo('body');

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
    $('#BackendFavoritesContainer').hover(
        function () {
            $('#BackendFavoriteList').show();
        },
        function () {
            $('#BackendFavoriteList').hide();
        }
    );
    $('#BackendAddToFavorites').click(function () {
        backendInstance.loading(true);
        $(this).blur();
        $.ajax({
            url: backendInstance.linkAddToFavorites,
            success: function (res) {
                backendInstance.loading(false);
                backendInstance.$AddFavContainer.html(res);
                backendInstance.popupModalInstance.popupShow(backendInstance.$AddFavContainer);
            },
            error: function () {
                backendInstance.loading(false);
            }
        });
    });
}

Backend.prototype.loading = function (show) {
    var loadingLabel = 'BackendLoading';
    if (show) {
        this.popupModalInstance.popupShow('#BackendLoading', {}, loadingLabel);
    }
    else {
        this.popupModalInstance.popupHide(loadingLabel);
    }
}

Backend.prototype.handleAjax = function (res) {
    backendInstance.popupModalInstance.popupHide();
    backendInstance.$AddFavContainer.html();
}

