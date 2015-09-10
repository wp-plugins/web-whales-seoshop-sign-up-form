(function($) {
	$(document).on('submit', 'form.ww_seoshop-sign-up-form', function(event) {
		event.preventDefault();

		var form = $(this), btn = $('[type=submit]', form), msg_box = $('.ww_message-box', form);

		$.ajax({
			beforeSend : function() {
				$('[name]', form).removeClass('form-error');
				btn.prop('disabled', true);
				form.addClass('busy');
			}, cache   : false, complete : function(data, status) {
				var r = data.responseJSON;
				r.callback_success = r.callback_success || false;
				r.error_fields = r.error_fields || false;
				r.message = r.message || '';
				r.status = r.status || false;

				if (r.status) {
					$('.ww_form-rows', form).slideUp(function() { $(this).remove() });
				} else if (typeof r.error_fields == 'object') {
					$.each(r.error_fields, function(i, v) { $('[name=' + v + ']', form).addClass('form-error'); });

					if (typeof window['grecaptcha'] != 'undefined') {
						grecaptcha.reset();
					}
				}

				if (r.message != '') {
					msg_box.html(r.message).slideDown(function() {
						$(this).trigger('change.seoshop-sign-up-form');

						btn.prop('disabled', false);
						form.removeClass('busy');

						if (r.status && r.callback_success !== false) {
							eval(r.callback_success);
						}
					});
				} else {
					msg_box.slideUp(function() {
						$(this).html('');

						btn.prop('disabled', false);
						form.removeClass('busy');

						if (r.status && r.callback_success !== false) {
							eval(r.callback_success);
						}
					});
				}
			}, data    : form.serializeArray(), dataType : 'json', type : 'POST'
		});
	}).on('change.seoshop-sign-up-form', '.ww_message-box', function() {
		var _this = $(this), offset = 50 + ( $('#wpadminbar').outerHeight(true) || 0 ), pos = _this.offset().top, w_top = $(window).scrollTop();

		if (typeof window['ww_seoshop_options'] != 'undefined') {
			offset += parseInt(ww_seoshop_options.sticky_header_offset || 0);
		}

		pos -= offset;

		if (pos < w_top) {
			$('html, body').animate({scrollTop : pos}, Math.abs(w_top - pos) * 3.5);
		}
	});
})(jQuery);