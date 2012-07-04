function Form(data) {
    this.formId = data.formId;
    this.ajax = data.ajax;
    this.ajaxHandler = data.ajaxHandler;
    this.prefixForm = data.prefixForm;
    this.containerId = this.prefixForm + 'Form';

    var FormInstance = this;

    if (FormInstance.ajax) {
        FormInstance.overwhelmSubmits();
    }
    else {
        $('#' + FormInstance.containerId)
            .off('click', '#' + FormInstance.formId + ' input[type=submit], #' + FormInstance.formId + ' input[type=image]')
            .on('click', '#' + FormInstance.formId + ' input[type=submit], #' + FormInstance.formId + ' input[type=image]', function (event) {
                FormInstance.setWorking(true);
            });
    }
}

Form.prototype.overwhelmSubmits = function () {
    var FormInstance = this;
    $('#' + FormInstance.containerId)
        .off('click', '#' + FormInstance.formId + ' input[type=submit], #' + FormInstance.formId + ' input[type=image]')
        .on('click', '#' + FormInstance.formId + ' input[type=submit], #' + FormInstance.formId + ' input[type=image]', function (event) {

            event.preventDefault();

            FormInstance.setWorking(true);

            var $form = $('#' + FormInstance.formId);

            var data = '';
            var submit = this;

            $form.find('input[type!=submit][type!=image], textarea, select').each(function () {
                if (!$(this).attr('disabled') && !$(this).prop('disabled')) {
                    var type = $(this).attr('type');
                    if (!type) {
                        if ($(this).is('select')) {
                            type = 'select';
                        }
                        else if ($(this).is('textarea')) {
                            type = 'textarea';
                        }
                    }
                    if ((type != 'checkbox' && type != 'radio') || $(this).is(':checked')) {
                        if (type == 'select') {
                            var $select = $(this);
                            $select.find('option:selected').each(function () {
                                if (data) {
                                    data += '&';
                                }
                                data += $select.attr('name') + '=' + $(this).attr('value');
                            });
                        }
                        else {
                            if (data) {
                                data += '&';
                            }
                            data += encodeURIComponent($(this).attr('name')) + '=' + encodeURIComponent($(this).attr('value'));
                        }
                    }
                }
            });

            if (data) {
                data += '&';
            }
            data += encodeURIComponent($(submit).attr('name')) + '=' + encodeURIComponent($(submit).attr('value'));

            $.ajax({
                type: 'post',
                url: $form.attr('action'),
                data: data,
                success: function(responseText) {
                    if (FormInstance.ajaxHandler) {
                        eval(FormInstance.ajaxHandler + "(responseText, FormInstance);");
                    }
                    else {
                        FormInstance.handleAjax(responseText);
                    }
                },
                error: function(responseText) {
                    // alert('Form error: unable to proceed AJAX request');
                    // alert(responseText.responseText);
                    FormInstance.setWorking(false);
                }
            });
            return false;
        });
}

Form.prototype.setWorking = function (yes) {
    var FormInstance = this;
    if (yes) {
        if ($('#' + FormInstance.formId + 'SubmitsWorking').length && $('#' + FormInstance.formId + 'Submits').length) {
            $('#' + FormInstance.formId + 'Submits').hide();
            $('#' + FormInstance.formId + 'SubmitsWorking').show();
        }
    }
    else {
        if ($('#' + FormInstance.formId + 'SubmitsWorking').length && $('#' + FormInstance.formId + 'Submits').length) {
            $('#' + FormInstance.formId + 'Submits').show();
            $('#' + FormInstance.formId + 'SubmitsWorking').hide();
        }
    }
}

Form.prototype.handleAjax = function (responseText) {
    var FormInstance = this;
    if (typeof(responseText) != 'object') {
        $('#' + FormInstance.prefixForm + 'Form').html(responseText);
    }
    else if (responseText.type == 'form_html'){
        $('#' + FormInstance.prefixForm + 'Form').html(responseText.content);
    }
    else if (responseText.type == 'form_link') {
        window.location.href = responseText.content;
    }
    else if (responseText.type == 'form_error') {
        alert('modForm: ' + responseText.content);
    }
    else {
        alert('modForm: AJAX returned unknown result');
        alert(responseText);
    }
}

