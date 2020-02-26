/*!
	AJAX Form v1.1.5
	Info: Processes forms through AJAX then calls relevant Success / Failure function
	Author: Matthew Murray ( admin@matdragon.com )

	Change Log:
	v1.1.1 - Added "success_redirect" to remove loader from normal forms on complete, but keep them for redirect responses
	v1.1.2 - Fix for errors displayed on forms within forms
	v1.1.3 - Changed existing "success_redirect" to "hide_loader"
	v1.1.4 - Added "redirect_override" argument
	v1.1.5 - Updated how file uploads are handled to accommodate multiple files per input
*/

(function ($) {
    'use strict';

    $.fn.ipsAjaxForm = function (args = {}) {

        return this.each(function () {

            var loader_message = '';
            var submit_type = 'submit';
            var submit_delegate = '';
            var auto_submit = false;
            var hide_loader = true;

            var methods = {

                'add_error': function ($form, target, message) {
                    var $field = $form.find('input[name="' + target + '"], input[name="' + target + '[]"], select[name="' + target + '"], textarea[name="' + target + '"]');

                    if ($field.length) {
                        var $fieldset = $field.closest('fieldset');
                        var content = '<p class="message error">' + message + '</p>';

                        $fieldset.addClass('error').append(content);
                    } else {
                        var content = '<p class="message error">' + message + '</p>';

                        methods.add_message($form, content);
                    }
                },

                'add_message': function ($form, content) {
                    $form.prepend(content);
                },

                'remove_error': function ($target) {
                    $target.removeClass('error').find('.message').remove();
                },

                'clear_form': function ($form) {
                    $form.find('.message').remove();
                    $form.find('.error').removeClass('error');
                },

                'watch_errors': function ($form) {
                    $form.on('click', 'input, focus, select, textarea', function () {
                        var $fieldset = $(this).parents('fieldset');
                        if ($fieldset.hasClass('error')) {
                            methods.remove_error($fieldset);
                        }
                    });
                },

                'add_loader': function ($form) {
                    var message = '';
                    if (loader_message != '') {
                        message = '<p class="loader-message">' + loader_message + '</p>';
                    }
                    $form.prepend('<div class="loader active"><div class="loader-icon"></div>' + message + '</div>');
                },

                'remove_loader': function ($form) {
                    $form.find('.loader').remove();
                }
            };

            if (typeof(args.auto_submit) != "undefined" && args.auto_submit == true) {
                submit_type = 'change paste submit';
                submit_delegate = ':input';
                auto_submit = true;
            }

            if (typeof(args.loader_message) != "undefined") {
                loader_message = args.loader_message;
            }

            // auto submit forms can still be submitted as a normal form which is a problem
            $(this).on(submit_type, submit_delegate, function (e) {

                var keycode = e.which;

                if (!auto_submit || (e.type === 'paste' || e.type === 'change' || (
                    (keycode === 46 || keycode === 8) || // delete & backspace
                    (keycode > 47 && keycode < 58) || // number keys
                    keycode == 32 || keycode == 13 || // spacebar & return key(s) (if you want to allow carriage returns)
                    (keycode > 64 && keycode < 91) || // letter keys
                    (keycode > 95 && keycode < 112) || // numpad keys
                    (keycode > 185 && keycode < 193) || // ;=,-./` (in order)
                    (keycode > 218 && keycode < 223)))) { // [\]' (in order))

                    e.preventDefault();
                    methods.add_loader($(this));

                    if (typeof args.before === 'function') {
                        args.before.call(this);
                    }

                    if (auto_submit) {
                        var $form = $(this).parents('form');
                    } else {
                        var $form = $(this);
                    }
                    var form_data = new FormData();
                    var action = $form.attr('action') || '/';

                    var content = $form.serializeArray(); // + '&ajax=1';
                    $.each(content, function (key, input) {
                        form_data.append(input.name, input.value);
                    });

                    // File uploads
                    var file_inputs = $form.find('input[type="file"]');
                    $.each($(file_inputs), function (key, input) {
                        var input_name = $(input).attr('name');
                        $.each(input.files, function(file_key, file_item) {
                            form_data.append(input_name + '[' + file_key + ']', file_item);
                        });
                    });

                    $.ajax({
                        url: action,
                        method: 'POST',
                        type: 'POST',
                        dataType: 'json',
                        contentType: false,
                        processData: false,
                        data: form_data,
                        context: this,
                        success: function (json) {

                            if (json.fragments) { // Fragments
                                $.each(json.fragments, function (key, value) {
                                    var content = '';
                                    var init_form = false;

                                    if (typeof value === 'object') {
                                        content = value.content;
                                        init_form = true;
                                    } else {
                                        content = value;
                                    }

                                    $(key).replaceWith(content);

                                    if (init_form) {
                                        var id = $(content).attr('id');
                                        $('#' + id).ipsAjaxForm({});
                                    }
                                });
                            }

                            if (json.html) { // HTML
                                if (typeof args.html === 'function') {
                                    args.html.call(this, json.html);
                                }
                            }

                            if (json.reload) { // Reload
                                if (typeof args.reload_override === 'function') {
                                    args.reload_override.call(this, json);
                                } else {
                                    hide_loader = false;
                                    //window.location.replace(json.redirect);
                                    location.reload();
                                }
                            } else if (json.redirect) { // Redirect
                                if (typeof args.redirect_override === 'function') {
                                    args.redirect_override.call(this, json);
                                } else {
                                    hide_loader = false;
                                    window.location.replace(json.redirect);
                                }
                            } else {
                                if (json.errors) { // Errors

                                    methods.remove_loader($form);

                                    // Call Failure function
                                    if (typeof args.failure_override === 'function') {
                                        args.failure_override.call(this, json.errors);
                                        args.failure.call(this, json.errors);
                                    } else {
                                        if (typeof args.failure === 'function') {
                                            args.failure.call(this, json.errors);
                                        }

                                        methods.clear_form($form);

                                        $.each(json.errors, function (input, message) {
                                            methods.add_error($form, input, message);
                                        });

                                        methods.watch_errors($form);
                                    }

                                } else if (json.success) { // Success

                                    // Call Success function
                                    if (typeof args.success_override === 'function') {
                                        args.success_override.call(this, json.success);

                                        methods.clear_form($form);
                                    } else {
                                        if (typeof args.success === 'function') {
                                            args.success.call(this, json.success);
                                        }

                                        methods.clear_form($form);

                                        if (json.success !== 'true') {
                                            var content = '<p class="success message">' + json.success + '</p>';
                                            methods.add_message($form, content);
                                        }
                                    }
                                }
                            }
                        },
                        complete: function () {
                            if (typeof args.complete_override === 'function') {
                                args.complete_override.call(this);
                            } else {
                                if (hide_loader) {
                                    methods.remove_loader($(this));
                                }
                            }
                        }
                    });
                }
            });
        });
    };

})(jQuery);
