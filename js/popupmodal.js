if (typeof(PopupModal) == 'undefined') {
    /**
    * PopupModal class
    */
    var PopupModal;

    var PopupModalSets = {};
    var PopupModalSetsLabels = [];

    (function () {

        PopupModal = function (passedOptions) {
            this.options = jQuery.extend({
                tplDialog: false, 
                tplAlert: false, 
                tplConfirm: false
            }, passedOptions);

            if (this.options.tplDialog) {
                this.$tplDialog = jQuery(this.options.tplDialog);
            }
            if (!this.$tplDialog || !this.$tplDialog.length) {
                this.$tplDialog = jQuery('#PopupModalDialog');
                if (!this.$tplDialog.length) {
                    this.$tplDialog = jQuery('\
                        <div id="PopupModalDialog">\
                            <div class="popupModalText"></div>\
                            <div class="popupModalButtons"></div>\
                        </div>').css({display: 'none'}).appendTo('body');
                }
            }

            if (this.options.tplAlert) {
                this.$tplAlert = jQuery(this.options.tplAlert);
            }
            if (!this.$tplAlert || !this.$tplAlert.length) {
                this.$tplAlert = jQuery('#PopupModalAlert');
                if (!this.$tplAlert.length) {
                    this.$tplAlert =
                        jQuery('<div id="PopupModalAlert">')
                        .css({display: 'none'})
                        .appendTo('body')
                        .html(jQuery(this.options.tplDialog).html());
                    this.$tplAlert.find('.popupModalButtons').append('<input type="button" value="Ok" class="popupModalOk">');
                }
            }

            if (this.options.tplConfirm) {
                this.$tplConfirm = jQuery(this.options.tplConfirm);
            }
            if (!this.$tplConfirm || !this.$tplConfirm.length) {
                this.$tplConfirm = jQuery('#PopupModalConfirm');
                if (!this.$tplConfirm.length) {
                    this.$tplConfirm =
                        jQuery('<div id="PopupModalConfirm">')
                        .css({display: 'none'})
                        .appendTo('body')
                        .html(jQuery(this.options.tplDialog).html());
                    this.$tplConfirm.find('.popupModalButtons')
                        .append('<input type="button" value="Yes" class="popupModalYes">')
                        .append('<input type="button" value="No" class="popupModalNo">');
                }
            }

            return this;
        };

        PopupModal.prototype.confirm = function(message, callbackYes, callbackNo, buttonYes, buttonNo) {
            var PopupModalInstance = this;
            var label = PopupModalInstance.popupShow(PopupModalInstance.options.tplConfirm);
            var buttonYesInstance = PopupModalInstance.$tplConfirm.find('.popupModalYes');
            if (buttonYes) {
                buttonYesInstance.val(buttonYes);
            }
            buttonYesInstance.unbind('click').bind('click', function () {
                PopupModalInstance.popupHide(label);
                if (callbackYes) {
                    callbackYes();
                }
            });
            var buttonNoInstance = PopupModalInstance.$tplConfirm.find('.popupModalNo');
            if (buttonNo) {
                buttonNoInstance.val(buttonNo);
            }
            buttonNoInstance.unbind('click').bind('click', function () {
                PopupModalInstance.popupHide(label);
                if (callbackNo) {
                    callbackNo();
                }
            });
            PopupModalInstance.$tplConfirm.find('.popupModalText').html(message);
        };

        PopupModal.prototype.alert = function(message, callbackOk, buttonOk) {
            var PopupModalInstance = this;
            var label = PopupModalInstance.popupShow(PopupModalInstance.options.tplAlert);
            var buttonOkInstance = PopupModalInstance.$tplAlert.find('.popupModalOk');
            if (buttonOk) {
                buttonOkInstance.val(buttonOk);
            }
            buttonOkInstance.unbind('click').bind('click', function () {
                PopupModalInstance.popupHide(label);
                if (callbackOk) {
                    callbackOk();
                }
            });
            PopupModalInstance.$tplAlert.find('.popupModalText').html(message);
        };

        PopupModal.prototype.popupShow = function(selector, passedOptions, label) {
            var i;
            var modalSet = false;
            var labelIndex = false;
            var $content = jQuery(selector);
            for (i in PopupModalSetsLabels) {
                if (PopupModalSets[PopupModalSetsLabels[i]]['content'][0] == $content[0]) {
                    // 2011-10-03 - avoid "Loading" window blinking
                    // this.popupHide(PopupModalSetsLabels[i]);
                    // break;
                    return;
                }
            }
            if (!label) {
                labelIndex = 0;
                for (i in PopupModalSets) {
                    if (labelIndex <= PopupModalSets[i]['labelIndex']) {
                        labelIndex = PopupModalSets[i]['labelIndex'] + 1;
                    }
                }
                label = '__PopupModalLabel' + labelIndex;
            }

            PopupModalSetsLabels[PopupModalSetsLabels.length] = label;

            if (PopupModalSets[label]) {
                modalSet = PopupModalSets[label];
            }
            else {
                var currentZIndex = 100;
                for ( i in PopupModalSets) {
                    if (currentZIndex <= PopupModalSets[i]['zIndex']) {
                        currentZIndex = PopupModalSets[i]['zIndex'] + 2;
                    }
                }
                modalSet = jQuery.extend(jQuery.extend({
                    // options
                    easyClose: true,
                    background: '#000',
                    show: 200,
                    hide: 200,
                    contentContainerSelector: '.popupModalContentContainer', // previously was popupModalContent
                    opacity: 0.4,
                    maxHeight: 0.7,
                    maxWidth: 0.7,
                    center: true,
                    DOMSafety: false
                }, passedOptions), {
                    // internal data
                    mask: false,
                    content: false,
                    zIndex: currentZIndex,
                    labelIndex: labelIndex

                    ,label: label
                    ,selector: selector
                });
                // modalSet['contentContainer'] = $content.find(modalSet['contentContainerSelector']);

                PopupModalSets[label] = modalSet;
            }

            if (modalSet['content']) {
                this.contentHide(label);
            }
            modalSet['content'] = $content;

            if (!modalSet['DOMSafety'] && modalSet['center']) {
                // save DOM position to restore it when popup closing
                modalSet['contentPrevElement'] = modalSet['content'].prev();
                if (!modalSet['contentPrevElement'].length) {
                    modalSet['contentPrevElement'] = false;
                    modalSet['contentParentElement'] = modalSet['content'].parent();
                }
                else {
                    modalSet['contentParentElement'] = false;
                }
                modalSet['content'].appendTo('body');
            }

            // mask
            if (!modalSet['mask']) {
                modalSet['mask'] = jQuery('#PopupModalMask' + label);

                if (modalSet['mask'].length == 0) {
                    modalSet['mask'] = jQuery('<div id="PopupModalMask' + label + '">').
                        css({
                            position: 'fixed',
                            left: 0,
                            top: 0,
                            zIndex: modalSet['zIndex'] + 1,
                            backgroundColor: modalSet.background,
                            display: 'none'
                        });
                }

                var maskHeight = '100%';
                var maskWidth = '100%';

                modalSet['mask'].css({
                    width: maskWidth,
                    height: maskHeight
                });
                if (modalSet.show) {
                    modalSet['mask'].stop(true).fadeTo(modalSet['show'], modalSet['opacity']);
                }
                else {
                    modalSet['mask'].show().css({opacity: modalSet['opacity']});
                }
            }
            modalSet['mask'].appendTo('body');
            // modalSet['mask'].insertBefore(modalSet['content']);

            // Content
            modalSet['content'].css({
                zIndex: modalSet['zIndex'] + 2
            });
            if (modalSet['center']) {
                modalSet['content'].css({
                    position: 'fixed'
                });
            }
            // modalSet['contentContainer'] = getContentContainer(modalSet);
            var contentContainer = getContentContainer(modalSet);

            // this should be restored when popup hides
            /*
            modalSet['keepMaxWidth'] = modalSet['contentContainer'].css('maxWidth');
            modalSet['keepMaxHeight'] = modalSet['contentContainer'].css('maxHeight');
            modalSet['keepOverflow'] = modalSet['contentContainer'].css('overflow');
            */
            modalSet['keepMaxWidth'] = contentContainer.css('maxWidth');
            modalSet['keepMaxHeight'] = contentContainer.css('maxHeight');
            modalSet['keepOverflow'] = contentContainer.css('overflow');

            popupCenter(label);

            if (modalSet.show) {
                modalSet['content'].stop(true).fadeTo(modalSet.show, 1, function () {
                    if (jQuery.browser.msie) {
                        this.style.removeAttribute('filter');
                    }
                });
            }
            else {
                modalSet['content'].show();
            }
            return label;
        };

        PopupModal.prototype.popupExists = function(label) {
            return PopupModalSets[label] ? PopupModalSets[label] : false;
        };

        PopupModal.prototype.popupCenter = function(label) {
            popupCenter(label);
        };

        PopupModal.prototype.popupToFront = function(label) {
            var FrontZIndex = 0;
            var i;
            for (i in PopupModalSets) {
                if (FrontZIndex < PopupModalSets[i]['zIndex'] && i != label) {
                    FrontZIndex = PopupModalSets[i]['zIndex'];
                }
            }
            if (FrontZIndex) {
                PopupModalSets[label]['zIndex'] = FrontZIndex + 2;
                PopupModalSets[label]['mask'].css({
                    zIndex: PopupModalSets[label]['zIndex'] + 1
                });
                PopupModalSets[label]['content'].css({
                    zIndex: PopupModalSets[label]['zIndex'] + 2
                });
            }
        };

        PopupModal.prototype.popupHide = function(label, resetContent) {
            if (!label) {
                label = PopupModalSetsLabels[PopupModalSetsLabels.length - 1];
            }
            if (!label) {
                alert('popup label is not passed to popupHide');
                return ;
            }
            var modalSet = PopupModalSets[label];
            if (!modalSet) {
                alert('modalSet is not exists for label "' + label + '" inside PopupModal.popupHide');
                return ;
            }

            jQuery(document).unbind('keydown.popupmodal');
            this.maskHide(label);
            this.contentHide(label, resetContent);

            for (var i = 0; i < PopupModalSetsLabels.length; i++) {
                if (PopupModalSetsLabels[i] == label) {
                    PopupModalSetsLabels.splice(i, 1);
                    PopupModalSets[label]['content'] = false;
                    break;
                }
            }
            // 2012-02-15: restored deletion - if not deleting, popup with label (popup-B), called from the popup without label (popup-A) 
            // will be bringed backward when popup-A will be opened second time
            delete PopupModalSets[label];
        };

        PopupModal.prototype.maskHide = function (label) {
            if (!label) {
                alert('popup label is not passed to maskHide');
                return ;
            }
            var modalSet = PopupModalSets[label];
            if (!modalSet) {
                alert('modalSet is not exists for label "' + label + '" inside PopupModal.maskHide');
                return ;
            }

            if (modalSet['mask']) {
                if (modalSet.hide) {
                    var maskFading = modalSet['mask'];
                    modalSet['mask'].fadeOut(modalSet.hide, function () {
                        // 2012-04-24
                        /*
                        if (!modalSet['DOMSafety'] && modalSet['center']) {
                            if (modalSet['contentPrevElement']) {
                                modalSet['contentPrevElement'].after(maskFading);
                            }
                            else {
                                modalSet['contentParentElement'].prepend(maskFading);
                            }
                        }
                        modalSet['mask'] = false;
                        */
                        modalSet['mask'].detach();
                        modalSet['mask'] = false;
                    });
                }
                else {
                    modalSet['mask'].hide();
                    // 2012-04-24
                    /*
                    if (!modalSet['DOMSafety'] && modalSet['center']) {
                        if (modalSet['contentPrevElement']) {
                            modalSet['contentPrevElement'].after(modalSet['mask']);
                        }
                        else {
                            modalSet['contentParentElement'].prepend(modalSet['mask']);
                        }
                        modalSet['mask'] = false;
                    }
                    */
                    modalSet['mask'].detach();
                    modalSet['mask'] = false;
                }
            }
        };

        PopupModal.prototype.contentHide = function (label, resetContent) {
            if (!label) {
                alert('popup label is not passed to contentHide');
                return ;
            }
            var modalSet = PopupModalSets[label];
            if (!modalSet) {
                alert('modalSet is not exists for label "' + label + '" inside PopupModal.contentHide');
                return ;
            }

            if (modalSet['content']) {
                var contentContainer = getContentContainer(modalSet);
                if (0 && modalSet.hide) {
                    var contentFading = modalSet['content'];
                    modalSet['content'].fadeOut(modalSet.hide, function () {
                        if (!modalSet['DOMSafety'] && modalSet['center']) {
                            // restore DOM position
                            if (modalSet['contentPrevElement']) {
                                modalSet['contentPrevElement'].after(contentFading);
                            }
                            else {
                                modalSet['contentParentElement'].prepend(contentFading);
                            }
                        }
                        contentContainer.css({
                            'maxWidth': modalSet['keepMaxWidth'],
                            'maxHeight': modalSet['keepMaxHeight'],
                            'overflow': modalSet['keepOverflow']
                        });
                        if (resetContent) {
                            var $resetContent = jQuery(resetContent);
                            if ($resetContent) {
                                $resetContent.html('');
                            }
                            else {
                                contentFading.html('');
                            }
                        }
                    });
                }
                else {
                    modalSet['content'].hide();
                    if (!modalSet['DOMSafety'] && modalSet['center']) {
                        // restore DOM position
                        if (modalSet['contentPrevElement']) {
                            modalSet['contentPrevElement'].after(modalSet['content']);
                        }
                        else {
                            modalSet['contentParentElement'].prepend(modalSet['content']);
                        }
                    }
                    contentContainer.css({
                        'maxWidth': modalSet['keepMaxWidth'],
                        'maxHeight': modalSet['keepMaxHeight'],
                        'overflow': modalSet['keepOverflow']
                    });
                    if (resetContent) {
                        var $resetContent = jQuery(resetContent);
                        if ($resetContent) {
                            $resetContent.html('');
                        }
                        else {
                            modalSet['content'].html('');
                        }
                    }
                }
                modalSet['content'] = false;
            }
        };

        var popupCenter = function (label, soft) {
            if (!label) {
                alert('popup label is not passed to popupCenter');
                return ;
            }
            var modalSet = PopupModalSets[label];
            if (!modalSet) {
                alert('modalSet is not exists for label "' + label + '" inside popupCenter');
                return ;
            }

            if (modalSet['content'] && modalSet['center']) {
                var winH = jQuery(window).height();
                var winW = jQuery(window).width();
                if (modalSet['maxHeight'] || modalSet['maxWidth']) {
                    var contentContainer = getContentContainer(modalSet);
                    contentContainer.css({
                        overflow: 'auto'
                    });
                    contentContainer.css({
                        maxHeight: winH * modalSet['maxHeight'],
                        maxWidth: winW * modalSet['maxWidth']
                    });
                }

                if (soft) {
                    modalSet['content'].animate({
                        left: winW / 2 - modalSet['content'].width() / 2,
                        top: winH / 2 - modalSet['content'].height() / 2
                    }, 100);
                }
                else {
                    modalSet['content'].css({
                        left: winW / 2 - modalSet['content'].width() / 2,
                        top: winH / 2 - modalSet['content'].height() / 2
                    });
                }
            }
        };
        var getContentContainer = function (modalSet) {
            var $contentContainer = modalSet['content'].find(modalSet['contentContainerSelector']);
            if (!$contentContainer.length) {
                $contentContainer = modalSet['content'];
            }
            return $contentContainer;
        };

        var popupCenterCycle = function () {
            for (var label in PopupModalSets) {
                popupCenter(label, true);
            }
            setTimeout(popupCenterCycle, 200);
        }
        popupCenterCycle();

    })();
}