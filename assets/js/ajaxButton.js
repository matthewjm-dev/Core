/*!
	AJAX Button v1.0
	Info: Processes forms through AJAX then calls relevant Success / Failure function
	Author: Matthew Murray ( admin@matdragon.com )

	Change Log:
	v1.0 - Created from ajaxForm v1.1.5
*/

(function ($) {
	'use strict';

	$.fn.ipsAjaxButton = function (args = {}) {

		return this.each(function () {

			var loader_message = '';
			var hide_loader = true;

			var methods = {
				'add_loader': function ($button) {
					var message = '';
					if (loader_message != '') {
						message = '<p class="loader-message">' + loader_message + '</p>';
					}
					$button.prepend('<div class="loader active"><div class="loader-icon"></div>' + message + '</div>');
				},

				'remove_loader': function ($button) {
					$button.find('.loader').remove();
				}
			};

			if (typeof(args.loader_message) != "undefined") {
				loader_message = args.loader_message;
			}

			$(this).on('click', function (e) {
				e.preventDefault();
				methods.add_loader($(this));

				if (typeof args.before === 'function') {
					args.before.call(this);
				}

				var $button = $(this);
				var action = $button.attr('action') || '/';

				$.ajax({
					url: action,
					method: 'POST',
					type: 'POST',
					dataType: 'json',
					contentType: false,
					processData: false,
					//data: form_data,
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
								methods.remove_loader($button);

								// Call Failure function
								if (typeof args.failure_override === 'function') {
									args.failure_override.call(this, json.errors);
									args.failure.call(this, json.errors);
								} else {
									if (typeof args.failure === 'function') {
										args.failure.call(this, json.errors);
									}

									$.each(json.errors, function (message) {
										add_flash_error(message);
									});
								}

							} else if (json.success) { // Success

								// Call Success function
								if (typeof args.success_override === 'function') {
									args.success_override.call(this, json.success);
								} else {
									if (typeof args.success === 'function') {
										args.success.call(this, json.success);
									}

									if (json.success !== 'true') {
										add_flash_success(json.success);
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
			});
		});
	};

})(jQuery);
