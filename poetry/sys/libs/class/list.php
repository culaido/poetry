<?php
class CList {
	var $id;
	var $type;
	var $width;
	var $align;
	var $numFields;
	var $m;
	var $chkData;

    var $chapter;
	var $col;
    var $header;
    var $body;

	var $nrows;

	function __construct($id, $type='', $width='100%') {
		$this->id		= $id;
		$this->style	= $style;
		$this->type		= $type;
		$this->nrows	= 0;
		$this->msgNoData = _T('no data');
		$this->numFields = 0;
        $this->output	 = '';
	}

	function setHeader($header, $config=array())	{
		if (!empty($header)) $this->numFields = count($header);

        $html = '';

		$first = TRUE;
        foreach ($header as $idx=>$val){
			$this->col[] = $idx;

            $width = $val['width'];
            $align = $val['align'];
            $title = $val['title'];

			if ( is_array( $config['sort'] ) && in_array($idx, $config['sort']['allow']) ){

				$url = $config['sort']['url'];

				$precedence = ( $config['sort']['precedence'] == 'ASC') ? 'ASC' : 'DESC';

				if ( $idx == $config['sort']['order'] ) {
					$precedence = ( $config['sort']['precedence'] == 'DESC') ? 'ASC' : 'DESC';
					$title .= " <span class='sort-{$precedence}'></span>";
				}

				$url = $config['sort']['url'];

				if ( strpos($url, '?') === false ) {
					if ( substr($url, -1) != '/' ) $url .= '/';
					$url .= '?';
				} else {
					$url .= '&';
				}

				$args = join('&', array("order={$idx}", "precedence={$precedence}"));
				$title = "<a href='{$url}{$args}' >" . $title . "<a>";
			}


            $html[] = ($this->type == 'checkbox' && $first)
                ? "<th width='{$width}' class='th text-center'>
						<input type='checkbox' role='itemSelectAll' id='{$this->id}_itemall' itemID='{$this->id}' autocomplete=off style='margin:0' />{$title}
					</th>"
                : "<th width='{$width}' class='th text-{$align}'>{$title}</th>";

			$first = FALSE;
		}

		$this->header .= "
            <thead>
                <tr class='header'>" . join('', $html) . "</tr>
            </thead>\n";
	}


	function setFooter($footer) {
		$this->footer = "<tr class='footer' role='footer'>\n";

		foreach ($footer as $val)
			$this->footer .= "<td class=td {$val['attr']}>{$val['text']}</td>";
	}

	function setDataAlign($align) {
        foreach($align as $idx=>$val)
			$this->align[$idx] = "style='text-align:{$val}'";
	}

	function setData($data, $checked='', $disabled='') {
		$this->chkData[$this->nrows] = 0;

		if ($checked == 'checked') {
			$class = 'selected';
			$this->chkData[$this->nrows] = 1;
		}
		else {
			if ($disabled == 'disabled') $this->chkData[$this->nrows] = 2;
		}

		$row = array();

		foreach ($this->col as $n=>$v) {

			$tmp = (($this->type != "") && ($n == 0) )
                ? "<input role='listChkBox' class='cb itemSelect' type='{$this->type}' name='{$this->id}_item' id='{$this->id}_item{$this->nrows}' value='{$data[$v]}' itemID={$this->id} {$checked} {$disabled} autocomplete=off style='margin:0' />"
                : $data[$v];

			$row[$v] = "<td class=td {$this->width[$v]} {$this->align[$v]}>{$tmp}</td>";
		}

		$this->body .= "<tr id={$this->id}_tr{$this->nrows} nrows='{$this->nrows}' itemID='{$this->id}' role='data'>" . join('', $row) . "</tr>";

		$this->nrows++;
	}

    function setSeparate($title) {
        if (!$title) return;
        $this->body .= "<tr class='separate' nrows='{$this->nrows}' itemID='{$this->id}' role='separate'>
                            <td class='td' colspan='{$this->numFields}'>{$title}</td>
                        </tr>";
        $this->nrows++;
    }

    function setChapter($title) {
        if (!$title) return;
        $this->chapter .= "<div class='chapter'>{$title}</div>";
    }

	function Close() {
		global $m;

        $msgNoData = (!empty($this->msgNoData))
            ? $this->msgNoData
            : $m['promptNoRec'];

		if ($this->nrows == 0)
		{
			$this->body .= ($this->numFields > 0)
                ? "<tr id='noData' role='noData'><td class='td' colspan={$this->numFields} style='text-align:center'>{$msgNoData}</td></tr>"
                : "<div class='blackfont' style='text-align:center' role='noData'><p>{$msgNoData}</p><hr size=1 color=#e8e8e8></div>";
		}

		$this->output = "
			<div class='clist'>
                {$this->chapter}
                <table class='tableBox table table-condensed' id={$this->id} cellpadding=0 cellspacing=0>
                    {$this->header}
                    <tbody id='{$this->id}Tbody' class='content'>
                        {$this->body}
                    <tbody>
                </table>
			</div>";

        $this->OutputJS();

        return $this->output;
	}

    function OutputJS() {

		$script = <<<JS
			var _lstLastClick = {};

			$(function(){

				$("#{$this->id} a").click(function(e){ e.stopPropagation(); });

				$("#{$this->id} [role='itemSelectAll']").click(function(){

					var _checked = $(this).is(":checked");

					(_checked)
						? $("#{$this->id} [role='listChkBox']").not(':checked').trigger('click')
						: $("#{$this->id} [role='listChkBox']:checked").trigger('click');
				});

				$("#{$this->id} [role='listChkBox']").change(function(e){

					if ($(this).prop('disabled')) return;

					var _selected =  ( $(this).is(':checked') == true );

					if ('{$this->type}' == 'radio') {
						$("#{$this->id} tr").toggleClass('selected', false);
					}

					$(this).closest('tr').toggleClass('selected', _selected);

					( $("#{$this->id} [role='listChkBox']").length - $("[role='listChkBox']:checked").length == 0 )
						? $("#{$this->id} [role='itemSelectAll']").prop('checked', true)
						: $("#{$this->id} [role='itemSelectAll']").prop('checked', false);

				});

				if ( $("#{$this->id} [role='listChkBox']").length > 0 ) {
					( $("#{$this->id} [role='listChkBox']").length - $("[role='listChkBox']:checked").length == 0 )
						? $("#{$this->id} [role='itemSelectAll']").prop('checked', true)
						: $("#{$this->id} [role='itemSelectAll']").prop('checked', false);
				}

				$("#{$this->id} tbody").on('click', "[role='listChkBox']", function(e){
					e.stopPropagation();
				});

				if ('{$this->type}' == 'checkbox' || '{$this->type}' == 'radio') {
					$("#{$this->id} tbody").on({
						click : function(e) {

							if ($(e.target).parents('tr').find("[role='listChkBox']").prop('disabled')) return;
							$(e.target).parents('tr').find("[role='listChkBox']").trigger('click');

							var curr = $(e.target).parents('tr');

							_lstLastClick = {list:'{$this->id}', rowOdd:curr.index()};
						}
					});

					$("#{$this->id} tbody").on('click', "[role='listChkBox']", function(e){

						var curr = $(e.target).parents('tr');
						var _chk = ($(e.target).parents('tr').find("[role='listChkBox']").is(":checked")) ? true : false;

						if (e.shiftKey && _lstLastClick.list == '{$this->id}') {
							var start = _lstLastClick.rowOdd;
							var end   = curr.index();

							if (start > end) {
								var temp = start;
								start = end;
								end = temp;
							}

							for (var i=(start+1); i<end; i++) {
								$("#{$this->id} tr:eq(" + (i+1) + ") [role='listChkBox']").trigger('click');
							}
						}

						_lstLastClick = {list:'{$this->id}', rowOdd:curr.index()};
					});
				}
			});

			function _lstGetItem(tbl, delim) {
				delim = delim || ",";
				var _list = new Array();
				$("#" + tbl + ' [role="listChkBox"]:checked').each(function(){
					_list.push($(this).val());
				});

				return _list.join(delim);
			}
JS;

		js::add($script);
	}
}
