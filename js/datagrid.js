function DataGrid(data) {
    this.prefixDataGrid = data.prefixDataGrid;
    this.linkLoadGrid = data.linkLoadGrid;

    this.popupLoadingLabel = this.prefixDataGrid + 'DGLoadingPopupLabel';
    this.popupContentL1Label = this.prefixDataGrid + 'DGContentL1PopupLabel';
    this.popupContentL2Label = this.prefixDataGrid + 'DGContentL2PopupLabel';

    this.lang = data.lang;
    this.popupModalInstance = new PopupModal({
        tplAlert: '#' + this.prefixDataGrid + 'DataGridAlert',
        tplConfirm: '#' + this.prefixDataGrid + 'DataGridConfirm'
    });

    var DataGridInstance = this;
    $('#' + DataGridInstance.prefixDataGrid + 'DataGrid')
		.off('click', '.' + DataGridInstance.prefixDataGrid + 'DGCtrlRows')
		.on('click', '.' + DataGridInstance.prefixDataGrid + 'DGCtrlRows', function (e) {
			var id = '';
			var errorMessage = $(this).attr('error');
			$('#' + DataGridInstance.prefixDataGrid + 'DataGrid .itemCheckbox:checked').each(function () {
				if (id) {
					id += ',';
				}
				id += $(this).val();
			});
			if (id) {
				$(this).removeAttr('disableFollow');
				$(this).attr('href', $(this).attr('href').replace(encodeURIComponent('{DATA_GRID_ID}'), encodeURIComponent(id)));
			}
			else if (errorMessage) {
				DataGridInstance.popupModalInstance.alert(errorMessage);
				$(this).attr('disableFollow', 'disableFollow');
				return false;
			}
		});
    $('#' + DataGridInstance.prefixDataGrid + 'DataGrid')
		.off(
			'click',
			'a.' + DataGridInstance.prefixDataGrid + 'DGCtrlRow' +
				', a.' + DataGridInstance.prefixDataGrid + 'DGCtrlRows' +
				', a.' + DataGridInstance.prefixDataGrid + 'DGCtrlOther' +
				', a.' + DataGridInstance.prefixDataGrid + 'DGPagination'
		)
		.on(
			'click', 
			'a.' + DataGridInstance.prefixDataGrid + 'DGCtrlRow' +
				', a.' + DataGridInstance.prefixDataGrid + 'DGCtrlRows' +
				', a.' + DataGridInstance.prefixDataGrid + 'DGCtrlOther' +
				', a.' + DataGridInstance.prefixDataGrid + 'DGPagination',
			function (e) {
				e.preventDefault();
				var confirmMessage = $(this).attr('confirm');
				var href = this;
				if (confirmMessage) {
					DataGridInstance.popupModalInstance.confirm(
						confirmMessage,
						function () {
							DataGridInstance.handleHref(href);
						}
					);
				}
				else {
					DataGridInstance.handleHref(href);
				}
			}
		);

    $('#' + DataGridInstance.prefixDataGrid + 'DataGrid')
		.off('change', '.itemCheckbox')
		.on('change', '.itemCheckbox', function (e) {
			if ($('#' + DataGridInstance.prefixDataGrid + 'DataGrid .itemCheckbox:checked').length) {
				$('#' + DataGridInstance.prefixDataGrid + 'DataGridControlsRows').fadeIn(200);
			}
			else {
				$('#' + DataGridInstance.prefixDataGrid + 'DataGridControlsRows').fadeOut(200);
			}
		});

    $('#' + DataGridInstance.prefixDataGrid + 'DataGrid')
		.off('click', '#' + DataGridInstance.prefixDataGrid + 'DataGridCheckAll')
		.on('click', '#' + DataGridInstance.prefixDataGrid + 'DataGridCheckAll', function () {
			if ($(this).attr('checked')) {
				$('#' + DataGridInstance.prefixDataGrid + 'DataGrid .itemCheckbox').attr('checked', 'checked').trigger('change');
			}
			else {
				$('#' + DataGridInstance.prefixDataGrid + 'DataGrid .itemCheckbox').removeAttr('checked').trigger('change');
			}
		});
}

DataGrid.prototype.handleHref = function (href) {
    var DataGridInstance = this;
    var $href = $(href);
    if ($href.hasClass(DataGridInstance.prefixDataGrid + 'DGCtrlPopup') || $href.hasClass(DataGridInstance.prefixDataGrid + 'DGCtrlAjax')) {
        DataGridInstance.loadContent($href.attr('href'));
    }
    else {
        window.location.href = $href.attr('href');
    }
}

DataGrid.prototype.loadGrid = function() {
    var DataGridInstance = this;
    this.loadContent(this.linkLoadGrid);
}

DataGrid.prototype.loadContent = function (link, windowContainer, contentContainer) {
    var DataGridInstance = this;
    if (!windowContainer) {
        windowContainer = false;
    }
    if (!contentContainer) {
        contentContainer = false;
    }
    DataGridInstance.popupModalInstance.popupShow('#' + DataGridInstance.prefixDataGrid + 'DataGridLoading', {}, DataGridInstance.popupLoadingLabel);
    DataGridInstance.popupModalInstance.popupToFront(DataGridInstance.popupLoadingLabel);
    $.ajax({
        type: "POST",
        url: link,
        success: function(responseText) {
            // 2012-02-20: перенесено из DataGrid.prototype.parseAjaxResponse
            // вываливало ошибку, когда в попапе находилось содержимое (форма), для которого был
            // установлен DataGrid.prototype.handleAjax при ajax-переходах, т.к. была попытка закрыть попап с
            // меткой loading, которого в данном случае не существует
            DataGridInstance.popupModalInstance.popupHide(DataGridInstance.popupLoadingLabel);
            DataGridInstance.parseAjaxResponse(responseText, windowContainer, contentContainer);
        },
        error: function(msg) {
            DataGridInstance.popupModalInstance.popupHide(DataGridInstance.popupLoadingLabel);
        }
    });
}

DataGrid.prototype.handleAjax = function (responseText, Form) {
    var DataGridInstance = this;
    if (typeof(responseText) != 'object' || !this.parseAjaxResponse(responseText)) {
        Form.handleAjax(responseText);
    }
}

DataGrid.prototype.parseAjaxResponse = function (responseText, windowContainer, contentContainer) {
    var DataGridInstance = this;
    if (!windowContainer) {
        if (responseText.type == 'grid_popup') {
            if (responseText.level == 1) {
                windowContainer = '#' + DataGridInstance.prefixDataGrid + 'DataGridPopupL1';
            }
            else {
                windowContainer = '#' + DataGridInstance.prefixDataGrid + 'DataGridPopupL2';
            }
        }
        else if (responseText.type == 'grid_html') {
            windowContainer = '#' + DataGridInstance.prefixDataGrid + 'DataGrid';
        }
    }
    if (responseText.type == 'grid_popup' || responseText.type == 'grid_html') {
        if (!contentContainer) {
            contentContainer = windowContainer + ' .' + DataGridInstance.prefixDataGrid + 'ContentContainer';
        }
        if (!($contentContainer = $(contentContainer)).length) {
            contentContainer = windowContainer;
            $contentContainer = $(contentContainer);
        }
    }

    if (responseText.type == 'grid_popup') {
        $contentContainer.html(responseText.content);
        // 2012-02-20: перенесено к вызову этого метода в DataGrid.prototype.loadContent => success: function(responseText)
        // DataGridInstance.popupModalInstance.popupHide(DataGridInstance.popupLoadingLabel);
        if (responseText.level == 1) {
            DataGridInstance.popupModalInstance.popupShow(windowContainer, {}, DataGridInstance.popupContentL1Label);
        }
        else {
            DataGridInstance.popupModalInstance.popupShow(windowContainer, {}, DataGridInstance.popupContentL2Label);
        }
        return true;
    }
    else if (responseText.type == 'grid_html') {
        $contentContainer.html(responseText.content);
        // 2012-02-20: перенесено к вызову этого метода в DataGrid.prototype.loadContent => success: function(responseText)
        // DataGridInstance.popupModalInstance.popupHide(DataGridInstance.popupLoadingLabel);
        DataGridInstance.popupModalHide(1);
        return true;
    }
    else if (responseText.type == 'grid_js') {
        eval(responseText.content);
        return true;
    }
    else if (responseText.type == 'grid_link') {
        window.location.href = responseText.content;
        return true;
    }
    else {
        // 2012-02-20: перенесено к вызову этого метода в DataGrid.prototype.loadContent => success: function(responseText)
        // DataGridInstance.popupModalInstance.popupHide(DataGridInstance.popupLoadingLabel);
        return false;
    }
}

DataGrid.prototype.popupModalHide = function (level) {
    if (!level) {
        level = 1;
    }
    var DataGridInstance = this;
    if (level == 1) {
        var suff = 'L1';
    }
    else {
        var suff = 'L2';
    }
    var windowContainer = '#' + DataGridInstance.prefixDataGrid + 'DataGridPopup' + suff;
    var contentContainer = windowContainer + ' .' + DataGridInstance.prefixDataGrid + 'ContentContainer';
    if (!($contentContainer = $(contentContainer)).length) {
        contentContainer = windowContainer;
    }

    if (level == 1 && this.popupModalInstance.popupExists(this.popupContentL1Label)) {
        this.popupModalInstance.popupHide(this.popupContentL1Label, contentContainer);
        level = 2;
    }
    if (level == 2 && this.popupModalInstance.popupExists(this.popupContentL2Label)) {
        this.popupModalInstance.popupHide(this.popupContentL2Label, contentContainer);
    }
}


