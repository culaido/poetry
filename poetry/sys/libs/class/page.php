<?php

Class cpage {
    var $curr;
    var $total;
    var $url;
    var $prev_msg;
    var $next_msg;
    var $pages;
    var $go;

    function __construct($curr, $total, $url, $pages=10, $prev_msg='Prev', $next_msg='Next', $go=TRUE) {
        $this->curr     = $curr;
        $this->total    = $total;

        $this->url      = $url;
        $this->prev_msg = $prev_msg;
        $this->next_msg = $next_msg;
        $this->pages    = $pages;
        $this->go       = $go;
    }

    public function create() {
        return $this->createPage($this->curr, $this->total, $this->pages, $this->url, $this->prev_msg, $this->next_msg, $this->go);
    }

    private function createPage($curr, $total, $pages, $url, $prev_msg, $next_msg, $go) {
        global $m;

        $prev_msg   = ($prev_msg) ? $prev_msg : 'Prev';
		$next_msg   = ($next_msg) ? $next_msg : 'Next';
    	$separate   = (stristr($url, '?')) ? '&' : '?';

    	$prev_i  = $curr - 1;
    	$html    = ($curr > 1) ? "<a href='{$url}{$separate}page={$prev_i}'>{$prev_msg}</a>" : $prev_msg;
        $disable = ($curr == 1) ? 'page-disable' : '';
		$html   = "<li class='page-prev page-content {$disable}'>{$html}</li>";

    	$start  = ($curr < $pages) ? 1 : $start = $curr - (int)($pages / 2);
    	$end    = $start + $pages - 1;

    	if ($end > $total) $end = $total;


    	for ($i=$start; $i<=$end; $i++) {
    		$html .= ($i == $curr)
                ? "<li class='page-content page-curr'>{$i}</li>"
                : "<li class='page-content'><a href='{$url}{$separate}page={$i}'>{$i}</a></li>";
    	}

    	$next_i = $curr + 1;

    	$html .= ($curr < $total)
            ? "<li class='page-next page-content'><a href='{$url}{$separate}page={$next_i}'>{$next_msg}</a></li>"
            : "<li class='page-next page-content page-disable'>{$next_msg}</li>";

        if ($total > $pages && $go)
            $html .= "<li class='page-go page-content' style='position:relative'>Go: <input id='PageCombo' style='font-size:12px; line-height:1' class='page-input' value='{$curr}' autocomplete='off'></input> / {$total}</li>";

        $script = <<<JS
            var comboTotal   = '{$this->total}';
            var comboUrl     = '{$url}{$separate}page=';
            var comboValue   = '';
            var comboMaxSize = 180;

            $(document).ready(function(){
                $('#PageCombo')
                    .keypress(function(e) {
                        var key = (e.keyCode) ? e.keyCode : e.which;
                        if(key == 13) {
                           window.location.href = comboUrl + $(this).val();
                           e.stopPropagation();
                        }
                    })
                    .bind('click', function(e){
                        showPageCombo();
                        e.stopPropagation();
                    });
            });

            function showPageCombo() {
                var comboList = new Array();
                for (var j=1, i=0; j<=comboTotal; j++)
                    comboList[i++] = j;

                showCombo('PageCombo', comboList)
                $(document)
                    .bind('click.pager', comboEventHide)
                    .bind('keyup.pager', comboEventHide);
            }

            function comboEventHide(e) {
                var e = (!e) ? window.event : e;
                var key = e.keyCode;
                var mou = (e.button) ? e.button : e.which;

                if (key == 27) $('#comboBox').remove();
                if (mou == 1) $('#comboBox').remove();

                $(document)
                    .unbind('click.pager', comboEventHide)
                    .unbind('keyup.pager', comboEventHide);
            }

            //combo
            function showCombo(comboName, values, cb) {
                var _el   = $('#' + comboName),
                    _pos  = _el.position(),
                    curr  = -1,
                    _body = $('<div id="comboBox" class="page-comboBox"></div>'),
                    _list = $('<ul id="combo" class="page-combo ui-helper-reset"></ul>');
                comboValue = _el.val();

                for (var i=0, max=values.length; i < max; ++i) {
                    if (values[i].toString() == comboValue) {
                        curr = i;
                        _list.append("<li id='comboItem" + i + "' class='page-combo-curr' role='pageItem' itemId='" + values[i] + "' >" + values[i] + "</li>");
                    }
                    else
                        _list.append("<li id='comboItem" + i + "' class='page-combo-out' role='pageItem' itemId='" + values[i] + "' >" + values[i] + "</li>");
                }

				_body.append(_list);

				$('body').append(_body);

                $("#comboBox").on('click', "[role='pageItem']", function(e) {
                    e.stopPropagation();
                    window.location.href = comboUrl + $(this).attr("itemId");
                });

				if (_body.height() > comboMaxSize) {
					_body.css({
						width: 45,
						height:comboMaxSize
					});
				}

				_body.position({
					'of' : $('#PageCombo'),
					'my' : 'left top',
					'at' : 'left bottom'
				});

				_body.width(50);

                if (curr != -1) $('#comboBox').scrollTop(curr * $('#comboBox [role="pageItem"]').eq(0).height());
            }
JS;

        js::add($script);

    	return "<ul class='page ui-helper-reset clearfix'>{$html}</ul>";
    }
}