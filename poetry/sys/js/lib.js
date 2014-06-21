var ui = ui || {};


ui.modal = (function() {

    return {

        close: function() {
			$('body').css('overflow', 'auto');
			bootbox.hideAll();
		},
		
		html : function(title, html, _w, _h) {

			if (!_w) _w = 640;
			if (!_h) _h = 640;

			if ( $(window).width() < _w ) _w = $(window).width() - 20;
			if ( $(window).height() < _h ) _h = $(window).width();
			
			var _option = new Array();

			_option.width	= _w;
			_option.height	= _h;

			bootbox.dialog({
				title: title,
				message:html,
				onShow : function(){
					var initModalHeight	 = _option.height + 60;
					var userScreenHeight = $( window ).height();

					if (initModalHeight > userScreenHeight)
						$('.modal-dialog').css('overflow', 'auto');
					else {
						$('.modal-dialog').css('margin-top', (userScreenHeight / 2) - (initModalHeight/2));
					}

					$('.bootbox-body').css({width : '100%', height : '100%'});
					$('.modal-body').css({width : _w, height : _h, 'overflow':'auto'});

					$.Evt('modal.show').publish();

					return false;
				},
				onEscape : function(){
					$('body').css('overflow', 'auto');
					$.Evt('modal.close').publish();
				}
			});

			$('body').css('overflow', 'hidden');
		},
		
        show : function(title, _url, _w, _h) {

			var content = $('<iframe>', { src : _url,
				frameborder	: 0, css : {'border' : 0, 'width' : '100%', 'height' : '100%'}
			});

			if (!_w) _w = 640;
			if (!_h) _h = 640;

			var _option = new Array();

			_option.width	= _w;
			_option.height	= _h;

			bootbox.dialog({
				title: title,
				message:content,
				onShow : function(){
					var initModalHeight	 = _option.height + 60;
					var userScreenHeight = $( window ).height();

					if (initModalHeight > userScreenHeight)
						$('.modal-dialog').css('overflow', 'auto');
					else {
						$('.modal-dialog').css('margin-top', (userScreenHeight / 2) - (initModalHeight/2));
					}

					$('.bootbox-body').css({width : '100%', height : '100%'});
					$('.modal-body').css({width : _w, height : _h, 'overflow':'auto'});

					$.Evt('modal.show').publish();

					return false;
				},
				onEscape : function(){
					$('body').css('overflow', 'auto');
					$.Evt('modal.close').publish();
				}
			});


			$('body').css('overflow', 'hidden');
        },
    }}
());


function urlencode(str) {
	str = (str + '').toString();
	return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').
	replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
}
