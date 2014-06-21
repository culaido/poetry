jQuery.fn.uniform = function(g) {
    "use strict";

    var c = this;
    var f = {};
    var d = jQuery.extend(jQuery.fn.uniform.defaults, g);
    var b = jQuery.fn.uniform.language;

    this.validators = {
        get_val: function(j, l, h) {
            var m = h;
            l = l.split(" ");
            for (var k = 0; k < l.length; k += 1) {
                if (l[k] === j) {
                    if ((l[k + 1] != "undefined") && ("val-" === l[k + 1].substr(0, 4))) {
                        m = parseInt(l[k + 1].substr(4), 10);
                        return m;
                    }
                }
            }

            return m;
        },
        required: function(j, h) {
            var i;

            if (j.is('[type=editor]')) {

                var c = $.trim(strip_tags(getEditorValue(j.attr('name')), '<a><img><video><audio><iframe>'));

                c = c.replace(/&nbsp;/gi, '');
                c = c.replace(/[\r|\n]/gi, '');
                c = c.replace(/ /gi, '');

                return (c != '') ? true : e("requiredEditor", h);
            }

            if (j.is(":radio")) {

                i = j.attr("name");
                if ($("input[name=" + i + "]:checked").length > 0) {
                    return true;
                }

                return e("req_radio", h);
            }
            if (j.is(":checkbox")) {
                i = j.attr("name");

                if ($('[name="' + i + '"]').is(":checked")) {
                    return true;
                }
                return e("req_checkbox", h);
            }
            if ($.trim(j.val()) === "") {
                return e("required", h);
            }
            return true;
        },

        minLength: function(j, h) {
            var i = this.get_val("minLength", j.attr("class"), 0);
            if ((i > 0) && (jQuery.trim(j.val()).length < i)) {
                return e("minlength", h, i);
            }
            return true;
        },
        min: function(j, h) {
            var i = this.get_val("min", j.attr("class"), 0);
            if ((parseInt(j.val(), 10) < i)) {
                return e("min", h, i);
            }
            return true;
        },
        maxLength: function(i, h) {
            var j = this.get_val("maxLength", i.attr("class"), 0);
            if ((j > 0) && (jQuery.trim(i.val()).length > j)) {
                return e("maxlength", h, j);
            }
            return true;
        },
        max: function(j, h) {
            var i = this.get_val("max", j.attr("class"), 0);
            if ((parseInt(j.val(), 10) > i)) {
                return e("max", h, i);
            }
            return true;
        },
        email: function(i, h) {
            if (i.val().match(/^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/) || i.val() === '') {
                return true;
            } else {
                return e("email", h);
            }
        },
        url: function(i, h) {
            if (i.val().match(/^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_\-]*)(\.[A-Z0-9][A-Z0-9_\-]*)+)(:(\d+))?\/?/i) || i.val() === '') {
                return true;
            }
            return e("url", h);
        },
        number: function(i, h) {
            if (i.val().match(/(^-?\d\d*\.\d*$)|(^-?\d\d*$)|(^-?\.\d\d*$)/) || i.val() === "") {
                return true;
            }
            return e("number", h);
        },
        integer: function(i, h) {
            if (i.val().match(/(^-?\d\d*$)/) || i.val() === "") {
                return true;
            }
            return e("integer", h);
        },
        alpha: function(i, h) {
            if (i.val().match(/^[a-zA-Z]+$/)) {
                return true;
            }
            return e("alpha", h);
        },
        alphaNum: function(i, h) {
            if (i.val().match(/\W/)) {
                return e("alphanum", h);
            }
            return true;
        },

        date: function(i, h) {
            if (i.val().match("([0-9]{4})-([0]?[1-9]|[1][0-2])-([0]?[1-9]|[1|2][0-9]|[3][0|1])$") || i.val() === "") {
                return true;
            }
            return e("date", h);
        },

        validateCallback: function(m, h) {
            var k = m.attr("class").split(" "),
                l = "";
            for (var j = 0; j < k.length; j += 1) {
                if (k[j] === "validateCallback") {
                    if (k[j + 1] != "undefined") {
                        l = k[j + 1];
                        break;
                    }
                }
            }
            if (window[l] != "undefined" && (typeof window[l] === "function")) {
                return window[l](m, h);
            }
            return e("callback", h, l);
        }
    };


    function strip_tags(input, allowed) {

        if (!allowed) allowed = step.allow_tag;

        allowed = (((allowed || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
        var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
            commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;

        return input.replace(commentsAndPhpTags, '').replace(tags, function($0, $1) {
            return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
        });
    }

    var a = function(h) {
        return h.attr('item-name');
    };

    var e = function(h) {

        var j = b[h],
            n = j.split("%"),
            k = n[0],
            m = /^([ds])(.*)$/,
            o;

        for (var l = 1; l < n.length; l += 1) {
            o = m.exec(n[l]);

            if (!o || arguments[l] === null) {
                continue;
            }

            if (o[1] === "d") {
                k += parseInt(arguments[l], 10);
            } else {
                if (o[1] === "s") {
                    k += arguments[l];
                }
            }
            k += o[2];
        }
        return k;
    };

    function getAllEditorVal() {

        var v = '';

        $('[data-role="cform-editor"]').each(function(idx, item) {
            v += $.trim(getEditorValue($(item).attr('name')));
        });

        return v;
    }

    return this.each(function() {
        var h = jQuery(this);
        var j = function(n, l, m) {
            var k = n.closest('.control-group').andSelf()
                .toggleClass(d.error_class, !l)
                .toggleClass(d.valid_class, l);
        };


		$.Evt('form.beforeSubmit').subscribe(function() {
			h.submit();
		});

		$.Evt('form.validation').subscribe(function(l) {
			h.validation(l);
		});

		$.Evt('form.validationFail').subscribe(function(f) {

			if (!f) return;

			if (typeof f.msg === 'string') {
				alert(f.msg);
				return;
			}

			if (f.msg) {
				$.each(f.msg, function(i, m) {
					var that = $('#' + m.id);

					$.Evt('formItem.error').publish($('#' + m.id), m.msg, true);
				});

				h.find('.' + d.error_class + ":first input").focus();
			}
		});

        h.find(d.field_selector).each(function() {
            var l = $(this),
                k = l.val();
            l.data("default-color", l.css("color"));
            if (k === l.data("default-value") || !k) {
                l.not("select").css("color", d.default_value_color);
                l.val(l.attr("data-default-value"));
            }
        });

        /* ask for leave */

        $(window).load(function() {

            var i = $(d.field_selector).serialize(),
                b = '';

            $(window).bind("beforeunload", function(k) {

                if (!d.ask_on_leave && !h.hasClass("askOnLeave")) return;

                if (i != $(d.field_selector).serialize())
                    return ($.isFunction(d.on_leave_callback)) ? d.on_leave_callback(h) : e("on_leave");

                if (b != getAllEditorVal())
                    return ($.isFunction(d.on_leave_callback)) ? d.on_leave_callback(h) : e("on_leave");
            });
        });


        h.submit(function() {

            var k, l = true;
            var f = false;

            d.ask_on_leave = false;

            h.find(d.field_selector).each(function() {
                if ($(this).val() === $(this).data("default-value")) {
                    $(this).val("");
                }
            });

            if (d.prevent_submit || h.hasClass("preventSubmit")) {
                h.find(d.field_selector).not(':disabled').each(function() {
                    $(this).blur();

                    if (!f && $(this).hasClass(d.error_class)) {
                        f = true;
                        $(this).focus();
                    }
                });

                if ($.isFunction(d.submit_callback)) {
                    l = d.submit_callback(h);
                }

                if (h.find("." + d.error_class).length || !l) {

                    k = ($.isFunction(d.prevent_submit_callback)) ? d.prevent_submit_callback(h, e("submit_msg"), [e("submit_help")]) : jQuery.fn.uniform.showFormError(h, e("submit_msg"), [e("submit_help")]);
                }

            } else {
                k = true;
            }

            if (h.parents("#qunit-fixture").length) {
                k = false;
            }

            if (k === false) {
                $.Evt('form.validationFail').publish();
            } else {
				$.Evt('form.validationSuccess').publish();
            }
            return k;
        });

        h.delegate(d.field_selector, "click", function() {
            if ($(this).attr('type') == 'radio' || $(this).attr('type') == 'checkbox') {
                $(this).blur();
            }
        });

        h.delegate(d.field_selector, "focus", function() {

            h.find("." + d.focused_class).removeClass(d.focused_class);
            var k = $(this);
            k.parents().filter("." + d.holder_class + ":first").addClass(d.focused_class);
            if (k.val() === k.data("default-value")) {
                k.val("");
            }
            k.not("select").css("color", k.data("default-color"));
        });

        h.delegate(d.field_selector, 'blur', function() {
            var o = $(this),
                n = true,
                l, k = a($(this));

            if ($(this).attr('validByServer') == true) return;

            for (l in c.validators) {
                if (o.hasClass(l)) {
                    var m = c.validators[l](o, k);
                    if (typeof(m) === "string") {
                        n = false;

						$.Evt('formItem.error').publish(o, m);
						o.trigger(d.error_class, m);
                    }
                }
            }

            if (n) {
				$.Evt('formItem.success').publish(o);
                o.trigger("success");
            }

            return;
        });

        h.delegate(d.field_selector, d.error_class, function(k, l) {
            j($(this), false, l);
        });


        h.delegate(d.field_selector, "success", function(k, l) {
            j($(this), true);
        });

        $('body').delegate("[validByServer='true']", 'change', function() {
            $(this).popover('destroy').attr('validByServer', null);

            $(this).closest('.control-group').andSelf()
                .toggleClass(d.error_class, false)
                .toggleClass(d.valid_class, true);
        });

        $('input[autofocus]:not(:focus)').eq(0).focus();
    });
};

jQuery.fn.uniform.defaults = {
    submit_callback: false,
    prevent_submit: true,
    ask_on_leave: true,
    on_leave_callback: false,
    prevent_submit_callback: false,

    valid_class: "",
    error_class: "has-error",

    focused_class: "focused",

    holder_class: "ctrlHolder",
    field_selector: "[data-role^='cform-']",
    default_value_color: "#555"
};

$(function(){
	$(document).find('[data-role="cform-item"]')
		.focus(function(){ $.Evt('formItem.active'  ).publish( $(this) ); })
		.blur (function(){ $.Evt('formItem.deactive').publish( $(this) ); });
});


$.Evt('formItem.active').subscribe(function(e){
	$(e).closest('.fieldset').addClass('active');
});

$.Evt('formItem.deactive').subscribe(function(e){
	$(e).closest('.fieldset').removeClass('active');
});

$.Evt('formItem.error').subscribe(function(e, content, server){
	$(e).closest('.fieldset').addClass('error');
	$(e).closest('.form-group').addClass('has-error');

	if ( server ) $(e).attr('validByServer', true);

	var sw = $(document.body).width();

	var placement = 'right';
	if ( ( parseInt($(e).offset().left, 10) + $(e).width() + 280 ) > sw){
		placement = 'bottom';
	}
	
	$(e).popover('destroy')
		.popover({container:'body', content: content,
				placement 	: placement,
				template: '<div class="popover form-popover"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title"></h3><div class="popover-content"><p></p></div></div></div>',
				trigger	: 'keydown'});
});

$.Evt('formItem.success').subscribe(function(e){
	$(e).closest('.fieldset').removeClass('error');
	$(e).closest('.form-group').removeClass('has-error');

	$(e).attr('validByServer', false);

	$(e).popover('destroy');
});











jQuery.fn.uniform.showFormError = function(d, e, c) {
    return false;
    var a, b;
    if ($("#errorMsg").length) {
        $("#errorMsg").remove();
    }

    b = $("<div />").attr("id", "errorMsg").html("<h3>" + e + "</h3>");
    if (c.length) {
        b.append($("<ol />"));
        for (a in c) {
            $("ol", b).append($("<li />").text(c[a]));
        }
    }
    d.prepend(b);
    $("html, body").animate({
        scrollTop: d.offset().top
    }, 500);
    $("#errorMsg").slideDown();
    return false;
};

jQuery.fn.uniform.showFormSuccess = function(b, c) {
    return false;

    var a;
    if ($("#okMsg").length) {
        $("#okMsg").remove();
    }
    a = $("<div />").attr("id", "okMsg").html("<h3>" + c + "</h3>");
    b.prepend(a);
    $("html, body").animate({
        scrollTop: b.offset().top
    }, 500);
    $("#okMsg").slideDown();
    return false;
};
