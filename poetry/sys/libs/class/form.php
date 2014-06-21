<?php
class CForm {

	static $_ALREADY_SUBMIT = FALSE;
	static $_CALL_STACK = array();

	const ItemValueTag  = 'cform-item';
	const ItemEditorTag = 'cform-editor';

	static $_formAry = array();

	static $_dynamicJS   = array(); // dynamic js
	static $_eventSystem = array(); // form event forword
	static $_eventCustom = array(); // custom event listener

	static public function getId($obj, $id) {
		return ($obj === NULL) ? $id : get_class($obj) . '_' . $obj->_blockId . '_' . $id;
	}

	static public function has($obj, $id) {
		return isset(self::$_formAry[self::getId($obj, $id)]);
	}

	static private function validator($validator=array()) {
		if (!is_array($validator)) return '';

		$valid = array();

		foreach ($validator as $k => $v) {

			if ($k === 'max' || $k ==='min') {
				$valid[] = "number {$k} val-{$v}";
				continue;
			}

			if ($k === 'maxLength' || $k === 'minLength') {
				$valid[] = "{$k} val-{$v}";
				continue;
			}

			if ($k === 'sameAs') {
				$valid[] = "{$k} {$v}";
				continue;
			}

			$valid[] = $v;
		}

		return join(' ', $valid);
	}




	static private function getAttrStr($type, $attr=array()) {

		if (!is_array($attr)) return;

		$attribute = array();

		foreach ($attr as $k => $v) {
	//		sys::print_var( $k );

			if ($k == 'data-form-dynamic') continue;

			if (in_array($type, array('checkbox', 'radio')))
				if (in_array($k, array('checked', 'selected', 'disabled', 'value', 'title', 'id'))) continue;

			if ($k == 'disabled' && $v == FALSE) continue;

			$attribute[] = "{$k}='" . str_replace("'", "\\'", $v) . "'";
		}

		return join(' ', $attribute);
	}



	static private function addElement($obj, $type, $id, $val, $attr=array(), $validator=array(), $return=TRUE) {

		$id = self::getId($obj, $id);

		// validator
		$valid = array();

		if (is_array($validator)){

			foreach ($validator as $k => $v) {

				if ($k === 'max' || $k ==='min') {
					$valid[]  = "number {$k} val-{$v}";
					$attr[$k] = $v;
					continue;
				}

				if ($k === 'maxLength' || $k === 'minLength') {
					$valid[] = "{$k} val-{$v}";
					$attr[$k] = $v;
					continue;
				}

				if ($k === 'sameAs') {
					$valid[] = "{$k} {$v}";
					$attr[$k] = $v;
					continue;
				}
				$valid[] = $v;

			}
		}

		$validator = $valid;

		// attr
		$attribute = array();

		foreach ($attr as $k => $v) {

			if ( $k == 'condition' ) continue;

			switch ($k) {
				case 'data-form-trigger':
				case 'data-form-listener':
					// orverride the value of attr, for jQuery selector
					$v = self::eventHandler($id, $k, $v);
					break;

				case 'data-form-dynamic':
					$title    = lib::htmlChars($v['title']);
					$url	  = lib::lockUrl(lib::htmlChars($v['url']), array('source' => $id) );
					$display  = $v['display'];

					$dynamicSuffix = " <a data-url='{$url}' data-title='{$title}' data-toggle='modal' data-target='#dynamicEditModal' data-role='dynamicAdd' data-source='{$id}'>{$display}</a>";
					self::addDynamicEditModalJS($id, $v['script']);
					continue 2; // do not put to attribute. PHP continue works on switch case...real surprising
				break;

				case 'data-form-formatNoMatches':
					continue 2;
				break;

				default:
					// do nothing
				break;
			}

			$attr[$k] = $v;

			$v = lib::htmlChars($v);

		}

		$attr_str	= self::getAttrStr($type, $attr);

		$addToFrmAry = ($attr['template'] == TRUE) ? FALSE : TRUE;

		$fields = array (
			'id'    => $id,
			'name'  => $id,
			'class' => join(' ', $valid) . ' ' . $attr['class']
		);

		foreach ($fields as $k => $v) { $fields_str .= " {$k}='" . str_replace("'", "\\'", $v) . "'"; }

		switch ($type) {

			case 'hidden':
				$input = "<input value='{$val}' type='hidden' {$attr_str} {$fields_str} data-role='" . self::ItemValueTag . "' />";
			break;

			case 'text':
				$req = ( $attr['hideRequired'] != TRUE && in_array('required', $valid) ) ? '<span class="req">*</span>' : '';
				$input = "<input value='{$val}' type='text' {$fields_str} {$attr_str} data-role='" . self::ItemValueTag . "' />{$req}";
			break;

			case 'password':
				$req = ( $attr['hideRequired'] != TRUE && in_array('required', $valid) ) ? '<span class="req">*</span>' : '';
				$input = "<span class='form-group'><input value='{$val}' type='password' {$fields_str} {$attr_str} data-role='" . self::ItemValueTag . "' />{$req}</span>";
			break;

			case 'textarea':
				$input = "<textarea {$attr_str} {$fields_str} data-role='" . self::ItemValueTag . "'>{$val}</textarea>";
			break;

			case 'select':
				$option = array();
				if (is_array($val)) {
					foreach ($val as $k => $v) {
						$option[] = self::option($v);
					}
				}
				$optionHTML = join("", $option);
				$input = "<select class='form-control' data-role='" . self::ItemValueTag . "' data-tooltipPlacement='top' {$attr_str} {$fields_str}>{$optionHTML}</select>";
			break;

			case 'radios' :

				$html = '';
				if (is_array($val)) {
					foreach ($val as $k=>$v){
						$extra = array('id' => $id, 'k' => $k);
						$radio[] = self::option($v, 'radio', $extra);
					}
				}
				$outputHTML = join("", $radio);
				$input = $outputHTML;

			break;

			case 'checkbox' :

				$checked = ($attr['checked']) ? 'checked' : '';
				$disabled = ($attr['disabled']) ? 'disabled' : '';

				$input = "<label class='checkbox inline'><input value='{$val}' data-role='" . self::ItemValueTag . "' type='checkbox' {$fields_str} {$attr_str} {$checked} {$disabled}/>" . $attr['title'] . "</label>";
			break;

			case 'datepicker':
				$req = ( $attr['hideRequired'] != TRUE && in_array('required', $valid) ) ? '<span class="req">*</span>' : '';

				$input = "
					<span class='form-group date' data-date='{$val}' data-date-format='yyyy-mm-dd'>
						<input role='datepicker' style='width:85px' maxlength='10' value='{$val}' type='text' {$fields_str} {$attr_str} data-role='" . self::ItemValueTag . "' /> {$req}
					</span>";

			break;

			case 'timepicker':
				$req = ( $attr['hideRequired'] != TRUE && in_array('required', $valid) ) ? '<span class="req">*</span>' : '';

				$input = "
					<span class='form-group date' data-date='{$val}' data-date-format='yyyy-mm-dd'>
						<input role='timepicker' style='width:45px' maxlength='5' value='{$val}' type='text' {$fields_str} {$attr_str} data-role='" . self::ItemValueTag . "' /> {$req}
					</span>";

			break;

			case 'button':
				$addToFrmAry = FALSE;
				$input = "<button type='button' class='btn' id='{$id}' name='{$id}' {$attr_str}>{$val}</button>";

			break;

			case 'editor':
		        require_once(CLASS_PATH . '/' . 'ceditor.php');

				$input = "<span class='form-group'><textarea type='editor' data-role='" . self::ItemEditorTag . "' {$fields_str} rows='10'>{$val}</textarea></span>";
				$script = <<<JS
						$(function(){
							createEditor('{$id}', '{$width}', '{$height}');
						});
JS;
				js::add($script);

				break;

			case 'submit':
				$addToFrmAry = FALSE;
				$input  = "<button type='button' data-role='form-submit' class='btn btn-primary' {$fields_str} {$attr_str} data-loading-text='" . _T('form-submit-loading') . "'>{$val}</button>";
			break;

			case 'cancel':
				$addToFrmAry = FALSE;
				$input = "<button type='button' data-role='form-cancel' class='btn btn-default' id='{$id}' name='{$id}' {$attr_str}>{$val}</button>";
			break;

			default:
			return;
		}


		if ($addToFrmAry) {

			self::$_formAry[$id] = array(
				'type'		=> $type,
				'value'		=> $val,
				'attr'		=> $attr,
				'display'   => $input,
				'validator'	=> $validator,
				'return'	=> $return
			);
		}
		return ($return) ? $input : '';
	}

	static function file($obj, $id, $text, $pageId, $args=array()) {

		$id = self::getId($obj, $id);

		$url = lib::lockUrl(lib::htmlChars(WEB_ROOT . '/ajax/sys.modules.mod_fileUpload/mod_fileUpload.upload'), array('pageId' => $pageId, 'name' => $id) );

		$default = array(
			'multiple'			=> true,
			'url'				=> $url,
			'dataType'			=> 'json',
			'formAcceptCharset'	=> 'utf-8',
			'singleFileUploads' => true,
			'limitMultiFileUploads' => 3
		);

		$args = ($args + $default);
		$json = json_encode($args);
		
		$fileUploadLeaveNotify = _T('fileUploadLeaveNotify');

		$js =<<<file
			$('#{$id}').fileupload({$json}).prop('disabled', !$.support.fileInput)
				.parent().addClass($.support.fileInput ? undefined : 'disabled');

			$('#{$id}').fileupload({
				'progressall' : function(e, data){
					$.Evt('{$id}.progressall').publish(data);},
				'done'  : function(e, data){
					console.log( data.result['{$id}'] );
					$.Evt('{$id}.done').publish(data.result['{$id}']);},
				'start' : function(e, data){
					$.Evt('{$id}.start').publish(data);},	
				'fail' : function(e, data){
					$.Evt('{$id}.fail').publish(data);},	
				'chunkfail' : function(e, data){
					$.Evt('{$id}.chunkfail').publish(data);},
				'processfail' : function(e, data){

					var msg = new Array();
					$(data.files).each(function(idx, itm){
						if ( itm.hasOwnProperty('error') )
							msg.push(itm.error);
					});

					alert( msg.join('\/r\/n') );
				}
			});
						
			$(window).bind('beforeunload', function () {
				if ($('#{$id}').data('blueimp-fileupload')._active) {
					return '{$fileUploadLeaveNotify}';
				}
			});
			
file;
		js::add( $js );
		js::addLib(JS_PATH . '/fileUploader/vendor/jquery.ui.widget.js');

		js::addLib(JS_PATH . '/fileUploader/jquery.fileupload.js');
		js::addLib(JS_PATH . '/fileUploader/jquery.fileupload-process.js');

		js::addLib(JS_PATH . '/fileUploader/jquery.fileupload-validate.js');

		css::addFile(CSS_PATH . '/jquery.fileupload.css');

		$multi = ($args['multiple'] == false) ? '' : 'multiple';
		return "
			<span class='btn btn-success fileinput-button'>
				" . theme::icon('plus') . "
				<span>{$text}</span>
				<input id='{$id}' type='file' name='{$id}[]' {$multi}/>
			</span>";

	}


	static function hidden($obj, $id, $val, $validator = array('readonly'), $attr = array(), $return=FALSE) {
		$val = lib::htmlChars($val);
		return self::addElement($obj, 'hidden', $id, $val, $attr, $validator, $return);
	}

	static function text($obj, $id, $val, $validator=array(), $attr=array()) {
		$val = lib::htmlChars($val);
		$validator[] = 'form-control';
		return self::addElement($obj, 'text', $id, $val, $attr, $validator);
	}

	static function password($obj, $id, $val, $validator=array(), $attr=array()) {
		$val = lib::htmlChars($val);
		$validator[] = 'form-control';

		return self::addElement($obj, 'password', $id, $val, $attr, $validator);
	}

	static function textarea($obj, $id, $val, $validator=array(), $attr=array()) {
		$val = lib::htmlChars($val);
		$attr['class'] = $attr['class'] . ' input-block-level';
		return self::addElement($obj, 'textarea', $id, $val, $attr, $validator);
	}

	static function select($obj, $id, $option, $validator=array(), $attr=array()) {
		return self::addElement($obj, 'select', $id, $option, $attr, $validator);
	}

	static function multiSelect($obj, $id, $option, $validator=array(), $attr=array()) {

		js::addlib(JS_PATH . '/select2.min.js');
		css::addFile(CSS_PATH . "/select2.css");
		return self::addElement($obj, 'multiSelect', $id, $option, $attr, $validator);
	}

	private static function option($data, $type='option', $extra=array()) {

		if (!is_array($data)) return;

		$value    = $data['value'];
		$text     = $data['text'];
		$title    = ($data['title'] == '') ? $text : $data['title'];


		$attr_str = self::getAttrStr('radio', $data['attr']);
		$disabled = ($data['disabled'] === TRUE || strtolower($data['disabled']) === 'disabled' || strtolower($data['disabled']) === 'true') ? "disabled" : "";

		switch ($type) {
			case 'option':
				$selected = ($data['selected'] === TRUE || strtolower($data['selected']) === 'selected' || strtolower($data['selected']) === 'true') ? "selected" : "";
				$tpl = "<option title='{$title}' value='{$value}' {$attr_str} {$selected} {$disabled}>{$text}</option>";
				break;
			case 'radio':
				$id = $extra['id'];
				$k  = $extra['k'];
				$checked = ($data['checked'] === TRUE || strtolower($data['checked']) === 'checked' || strtolower($data['checked']) === 'true') ? "checked" : "";
				// dynamic
				if (isset($data['attr']['data-form-dynamic'])) {
					$v            = $data['attr']['data-form-dynamic'];
					$dynamicTitle = lib::htmlChars($v['title']);
					$url	      = lib::lockUrl(lib::htmlChars($v['url']), array('source' => "{$id}{$k}") );
					$height       = $v['height'];
					$display      = $v['display'];

					self::addDynamicEditModalJS("{$id}{$k}", $v['script']);
				}
				$tpl = 	"<label class='radio-inline'><input data-role='" . self::ItemValueTag . "' type='radio' id='{$id}{$k}' name='{$id}' value='{$value}' {$checked} {$disabled}>{$title}</label>";
				break;
		}

		return $tpl;
	}

	static function radios($obj, $id, $options, $validator=array(), $attr=array()){
		return self::addElement($obj, 'radios', $id, $options, $attr, $validator);
	}

	static function checkbox($obj, $id, $val, $validator=array(), $attr=array()) {
		$val = lib::htmlChars($val);
		return self::addElement($obj, 'checkbox', $id, $val, $attr, $validator);
	}


	/*
	 * val: [val1, val2, val3, ...]
	 */
	static function tag($obj, $id, $val, $validator = array(), $attr=array()) {

		js::addlib(JS_PATH . '/bootstrap-tagmanager.js');
		css::addFile(CSS_PATH . "/bootstrap-tagmanager.css");

		$hAttr = $attr;
		unset($hAttr['data-form-listener']);
		unset($hAttr['data-form-trigger']);

		$hidden = self::hidden($obj, $id, $val, array(), $hAttr, TRUE);

		$attr['rel-hidden'] = self::getId($obj, $id);

		$attr['maxLength'] = empty($attr['maxLength']) ? 32 : $attr['maxLength'];

		return self::addElement($obj, 'tag', $id . '_tag', explode(',', $val), $attr) . $hidden;
	}

	static function editor($obj, $id, $val, $validator = array(), $attr = array()) {
		$val = lib::htmlChars($val);
		return self::addElement($obj, 'editor', $id, $val, $attr, $validator);
	}

	static function button($obj, $id, $val, $attr=array()){
		return self::addElement($obj, 'button', $id, lib::htmlChars($val), $attr);
	}

	static function addSubmit($val = '', $attr = array()) {

		if ($val=='') $val = _T('ok');

		$val = lib::htmlChars($val);
		// default class
		return self::addElement(NULL, 'submit', 'Submit', $val, $attr);
	}

	static function addCancel($val = '', $attr = array()) {

		if ($val=='') $val=_T('cancel');

		$val = lib::htmlChars($val);
		return self::addElement(NULL, 'cancel', 'Cancel', $val, $attr);
	}

	static function fieldset($label, $element) {
		$args = func_get_args();

		// first item is label
		$label = array_shift($args);

		$element = join($args);

		return "
			<div class='fieldset clearfix'>
				<label class='control-label'>{$label}</label>
				<div class='controls'>{$element}</div>
			</div>

		";
	}

	static function icoFieldset($ico, $element) {
		$args = func_get_args();

		// first item is label
		$label = array_shift($args);

		$element = join($args);

		return "
			<div class='fieldset icon clearfix'>
				<div class='controls'>
					<div class='input-group-addon fa fa-{$ico}'></div>
					{$element}
				</div>
			</div>

		";
	}

	static function hFieldset($text, $element) {
		$args = func_get_args();

		// first item is label
		$label = array_shift($args);

		$element = join($args);

		return "
			<div class='fieldset form-horizontal clearfix'>
			  <div class='form-group'>
				<label class='col-md-2 control-label'>{$text}</label>
				<div class='col-md-10'>
					{$element}
				</div>
			  </div>
			</div>

		";
	}





	static function datepicker($obj, $id, $val, $validator=array('date'), $attr=array()){

		js::addlib(JS_PATH . '/bootstrap-datepicker.js');
		js::addlib(JS_PATH . '/locales/bootstrap-datepicker.zh-TW.js');
		css::addFile(CSS_PATH . "/datepicker.css");

		static $datepickerHasScript;
		if ($datepickerHasScript !== TRUE) {

$datepicker =<<<datepicker
	$(window).load(function(){
		$('[role="datepicker"]').datepicker({
			autoclose : 'true',
			format : 'yyyy-mm-dd',
			language:'zh-TW'
		}).on('changeDate', function(){ $(this).trigger('blur'); });
	});
datepicker;

			js::addLast($datepicker);

			$datepickerHasScript = TRUE;
		}

		$attr['data-tooltipPlacement'] = 'top';

		return self::addElement($obj, 'datepicker', $id, substr($val, 0, 10), $attr, $validator);
	}

	static function timepicker($obj, $id, $val, $validator=array('time'), $attr=array()){

		js::addlib(JS_PATH . '/bootstrap-timepicker.js');
		css::addFile(CSS_PATH . "/timepicker.css");

		static $timepickerHasScript;
		if ($timepickerHasScript !== TRUE) {

$timepicker =<<<timepicker
	$(window).load(function(){
		$('[role="timepicker"]').timepicker({ 'timeFormat': 'H:i', 'scrollDefaultTime' : '10:00' });
	});
timepicker;

			js::addLast($timepicker);

			$timepickerHasScript = TRUE;
		}

		$attr['data-tooltipPlacement'] = 'top';

		return self::addElement($obj, 'timepicker', $id, substr($val, 0, 5), $attr, $validator);
	}




	static function box() {
		$args = func_get_args();
		return '<div class="box clearfix">' . join('', $args) . '</div>';
	}

	static function hbox() {
		$args = func_get_args();
		return '<div class="form-inline">' . join('', $args) . '</div>';
	}


	static function submit($url='', $complete_url = '', $pageMode = '') {
		// logging
		$k = debug_backtrace();


		array_push(self::$_CALL_STACK, $k[0]);
		if (self::$_ALREADY_SUBMIT) {
			sys::exception(self::$_CALL_STACK, 'From submit called more than once');
		}

		// set flag as TRUE
		self::$_ALREADY_SUBMIT = TRUE;
		$urlinfo = parse_url( $_SERVER['REQUEST_URI'] );
		parse_str($urlinfo['query'], $url_param);

		unset($url_param['ajaxAuth']);
		unset($url_param['_pageMode']);
		unset($url_param['_lock']);

		$submit_url = $url;
		if ($submit_url == '') {
			$submit_url = $urlinfo['path'];
		}

		if ($complete_url == '') {
			$qry = (count($url_param)==0) ? '' : '?' . http_build_query($url_param);
			$complete_url = $urlinfo['path'] . $qry;
		}

		$_lockAry = $url_param;

		if (count(self::$_formAry) == 0) return;


		foreach(self::$_formAry as $key => $item) {
			if (in_array('readonly', $item['validator']) == TRUE) {
				$_lockAry[$key] = $item['value'];
			}
		}

		$pageMode = ($pageMode=='') ? Module::$_pageMode : $pageMode;
		$_lockAry['_pageMode']	= $pageMode;
		$_lock					= join(',', $_lockAry);

		$submit_url = lib::lockUrl($submit_url, $_lockAry);

		self::hidden(NULL, '_fmSubmit', 'yes');

		$debug_func = (__DEBUG__) ? '' : '$(this).prop("disabled", true);';

		$_onCancel = (self::$_onCancel) ? json_encode(self::$_onCancel) : "''";

		$script = <<<JS
		var _url_submit_   = '{$submit_url}',
		    _url_complete_ = '{$complete_url}',
		    _onCancel	   = {$_onCancel};
	
		var composition_flag = false;

		
		$(window).load(function(){
			if ( $(document).find(':focus').length == 0 ){
				$(document).find('[data-role="cform-item"]').get(0).focus();
			}
		});


		(function() {
			// TODO: change event binding timing not using on()

			$('body').on('click', 'button[data-role="form-submit"]', function(){
				{$debug_func}
				$.Evt('form.beforeSubmit').publish();
			});

			$('body').on('click', 'button[data-role="form-cancel"]', function(){

				if (_onCancel) {
					execCFormAction(_onCancel);
				} else {
					history.go(-1);	// default action
				}
			}).on('keyup', '[data-role="cform-item"]:not(textarea)', function(e){


				var code = e.keyCode || e.which;
				if (code == 13) {
					if ( composition_flag ) {
						composition_flag = false;
						return;
					}

					$('button[data-role="form-submit"]').eq(0).trigger('click');
				}
			
			}).on('compositionend', '[data-role="cform-item"]', function(e){
				e.preventDefault();
				e.stopPropagation();

				composition_flag = true;
			});

			$.formCollector = {};

			$.formCollector.collect = function(area) {

				var _ipt = (!area)
					? $.merge($("[data-role='cform-item']"), $("[data-role='cform-editor']"))
					: $.merge(area.find("[data-role='cform-item']"), area.find("[data-role='cform-editor']"));

				var data = {};

				$.each(_ipt, function(i, obj) {
					// combine data
					var type = (obj.type) ? obj.type : $(obj).attr('type');

					if (!type) return;

					if (type == 'textarea') {
						if ( $(obj).attr('type') == 'editor' ) type = 'editor';
					}

					idx = $(obj).attr('id');
					if (type == 'inline' || type == 'editor')
						data[idx] = getEditorValue(idx);
					else if (type == 'radio'){
						idx = $(obj).attr('name');
						data[idx] = $('[name="' + $(obj).attr('name') + '"]:checked').val();
					}
					else if (type == 'checkbox') {

						if ($(obj).is(':checked')) {

							var name = $(obj).attr('name');

							if ( $('[name="' + name + '"]').length > 1 ) {

								var val = new Array();

								$('[name="' + name + '"]:checked').each(function(i, v){
									val.push( $(v).val() );
								});

								data[name] = val.join(',');
							} else {
								data[idx] = $(obj).val();
							}

						}
						else {
							delete data[idx];
						}
					}
					else if (type == 'select-one') {
						data[idx] = $(obj).find(':selected').val();
					} else if (type === 'select-multiple') {
						var _val = $(obj).val();
						data[idx] = (_val) ? _val.join(",") : "";
					}
					else
						data[idx] = $(obj).val();

				});

				return data;
			}
		})();

		$.Evt('form.validationFail').subscribe(function(){
			$('button[data-role="form-submit"]').prop('disabled', false);
		});

		$.Evt('form.validationSuccess').subscribe(function(){
			var _post = $.formCollector.collect();
			ajax_post(_url_submit_, _post);
		});

		$.Evt('form.hold').subscribe(function(){
			$('button[data-role="form-submit"]').prop('disabled', true);
		});

		$.Evt('form.release').subscribe(function(){
			$('button[data-role="form-submit"]').prop('disabled', false);
		});

		var ajax_post = function(url, data){
			showMsg('loading');
			$.Evt('form.beforePost').publish();

			$.post(url, data, function(obj) {

				$.Evt('form.afterPost').publish(obj);

				$('button[data-role="form-submit"]').prop('disabled', false);
				clearProgressMsg();

				if (obj.ret.status == false) {
					$.Evt('form.validationFail').publish(obj.ret);
					$('button[data-role="form-submit"]').prop('disabled', false);

					return false;
				}

				$.Evt('form.submited').publish();

				execCFormAction(obj.ret.action);
			}, 'json');
		}

		$(function(){
			if ($('#page').length > 0)
				$('#page').uniform();
			else
				$('body').uniform();
		});

		function execCFormAction( action ) {

			$(action.alert).each( function(idx, val) {
				alert(val);
			});

			if (action.redir) {

				if (action.redir=='parentReload') {
					parent.window.location.reload(true);
					return;
				} else if (action.redir=='closeModal') {
					parent.ui.modal.close();
				} else {
					newLocation(action.redir);
					// window.location.href = action.redir;
				}

			} else if (action.parentUrl) {
				parent.window.location.href = action.parentUrl;
			} else if (action.reload) {
				window.location.reload(true);
				return;
			} else {
				newLocation(_url_complete_);
				//window.location.href = _url_complete_;
			}
		}

		function newLocation( location ) {
			var newLocation = ( typeof location === "string" ) ? location : window.location.href;

			window.location.href = newLocation;
			return;
		}
JS;

		js::add($script);

		js::addlib(JS_PATH . '/jquery.form.validation.js');

		$lang = array(
			'required'		=> _T('valid-required'),
			'req_radio'		=> _T('valid-req_radio'),
			'req_checkbox'	=> _T('valid-req_checkbox'),

			'email'		=> _T('valid-email'),
			'number'	=> _T('valid-number'),
			'integer'	=> _T('valid-integer'),
			'alpha'		=> _T('valid-alpha'),
			'alphanum'	=> _T('valid-alphanum'),

			'url'		=> _T('valid-url'),
			'on_leave'	=> _T('valid-on_leave'),
			'date'		=> _T('valid-date'),

			'max'	=> _T('valid-max'),
			'min'	=> _T('valid-min'),

			'minlength'	=> _T('valid-minLength'),
			'maxlength'	=> _T('valid-maxLength'),

			'callback'	=> 'Failed to validate %s field. Validator function (%s) is not defined!',

			'submit_msg' => '%s',
			'submit_help' => '%s',
			'submit_success' => '%s'
		);

		js::add("jQuery.fn.uniform.language = " . json_encode($lang) . ";");

		self::progress();
	}

	static function render() {

		if (!self::$_formAry) return;
		// output hidden component
//sys::print_var(self::$_formAry);
		foreach(self::$_formAry as $item) {
			$type[$item['type']] = TRUE;
			echo ($item['type'] == 'hidden' && $item['return'] == FALSE) ? $item['display'] : '';

		}

		if ($type['textarea'] === TRUE) {
			$script = <<<JS
	$(function(){ $('textarea').autosize(); });
JS;
			js::addlib(JS_PATH . '/jquery.autosize.js');
			js::add($script);
		}

		$script = <<<js

	$('body').delegate("a[data-role='filePick']", 'choosed', function(data) {
		$(this).trigger('filePicked', data);

		$(this).find('[data-role="actual"]').show().nextAll().hide();
		$(this).closest('[data-role="uploadBox"]').find('[data-role="cross"],[data-role="banner-cross"]').show();

		$('#' + $(this).attr('data-source')).val(data.ret.downloadUrl);

		$(this).find('[data-role="actual"]').attr('src', data.ret.downloadUrl).parent().css('zoom', 1);

	});

	$('body').delegate('img[data-role="cross"]', 'click', function(e) {

		e.preventDefault();

		var o = $(this).closest('[data-role="uploadBox"]');
		var i = o.find('[data-role="actual"]');
		var s = i.attr('default'); // chrome does not display fail image when set empty src
		i.attr('src', s);

		$(this).hide();

		$('#' + o.find("[data-role='filePick']").attr('data-source')).val('');
		$('[data-source="' + o.find("[data-role='filePick']").attr('data-source') + '"]' ).trigger('fileRemoved');

		return false;
	});

	$('a[data-role="filePick"]').delegate('[data-role="actual"]', 'changed', function(e) {
		var that = $(this),
			target = that.parent();
		if (that.attr('src') === that.attr('default')) {
			that.closest("[data-role='uploadBox']").find('[data-role="cross"]').hide();
			$("#" + target.attr('data-source')).val('');
		} else {
			that.closest("[data-role='uploadBox']").find('[data-role="cross"]').show();
			$("#" + target.attr('data-source')).val(that.attr('src'));
		}

	});
js;
		js::add($script);

		self::renderEvent();
	}




	static function validate() {

		$ret = array();

		$args = args::getAll();

		// pre-defined
		foreach (self::$_formAry as $key => &$item) {

			unset($args[$key]);
			$validator = $item['validator'];
			$item['submit_value']	= trim( args::get($key, 'nothing', '') );

			foreach ($validator as $v)
			{
				// check empty first
				if ($v == 'required') {
					if ( $item['submit_value'] != '' ) continue;

					switch ($item['type']) {

						case 'text':
							$ret[] = array('id' => $key, 'msg' => _T('valid-required', array('%s' => $item['attr']['item-name']) ));
							break;

						case 'radio':
							$ret[] = array('id' => $key, 'msg' => _T('valid-req_radio'));
							break;

						case 'checkbox':
							$ret[] = array('id' => $key, 'msg' => _T('valid-req_checkbox'));
							break;
					}
				}
				else {
					// no need to valid
					if ($item['submit_value'] == '') continue;
				}

				// other type of valid
				switch ($v)
				{
					case 'email':

						if ( lib::checkEmail($item['submit_value']) ) continue;

						$ret[] = array('id' => $key, 'msg' => _T('valid-email',
							array('%s' => $item['attr']['item-name'])
						));
					break;

					case 'url':

						if ( filter_var($item['submit_value'], FILTER_VALIDATE_URL) ) continue;

						$ret[] = array('id' => $key, 'msg' => _T('valid-url',
							array('%s' => $item['attr']['item-name'])
						));
					break;

					case 'number':
						if ( is_numeric($item['submit_value']) ) continue;

						$ret[] = array('id' => $key, 'msg' => _T('valid-number',
							array('%s' => $item['attr']['item-name'])
						));
					break;

					case 'integer':
						if ( filter_var($item['submit_value'], FILTER_VALIDATE_INT)===0 || filter_var($item['submit_value'], FILTER_VALIDATE_INT) ) continue;

						$ret[] = array('id' => $key, 'msg' => _T('valid-integer',
							array('%s' => $item['attr']['item-name'])
						));
					break;

					case 'alpha':
						if ( preg_match('/[a-zA-Z]/i', $item['submit_value']) ) continue;

						$ret[] = array('id' => $key, 'msg' => _T('valid-alpha',
							array('%s' => $item['attr']['item-name'])
						));
					break;

					case 'alphaNum':
						if ( preg_match('/\w/i', $item['submit_value']) ) continue;

						$ret[] = array('id' => $key, 'msg' => _T('valid-alphaNum',
							array('%s' => $item['attr']['item-name'])
						));
					break;

					case 'date':
						$dd = explode('-', $item['submit_value']);

						if ( checkdate($dd[1], $dd[2], $dd[0]) ) continue;

						$ret[] = array('id' => $key, 'msg' => _T('valid-date',
							array('%s' => $item['attr']['item-name'])
						));
					break;

					case 'min':

						if ( $item['submit_value'] >= $item['attr']['min'] ) continue;

						$ret[] = array('id' => $key, 'msg' => _T('valid-min',
							array(
								'%s' => $item['attr']['item-name'],
								'%d' => $item['attr']['min']
							)
						));
					break;

					case 'max':

						if ( $item['submit_value'] <= $item['attr']['max'] ) continue;

						$ret[] = array('id' => $key, 'msg' => _T('valid-max',
							array(
								'%s' => $item['attr']['item-name'],
								'%d' => $item['attr']['max']
							)
						));
					break;

					case 'minLength':

						if ( strlen($item['submit_value']) >= $item['attr']['minLength'] ) continue;

						$ret[] = array('id' => $key, 'msg' => _T('valid-minLength',
							array(
								'%s' => $item['attr']['item-name'],
								'%d' => $item['attr']['minLength']
							)
						));
					break;

					case 'maxLength':

						if ( strlen($item['submit_value']) <= $item['attr']['maxLength'] ) continue;

						$ret[] = array('id' => $key, 'msg' => _T('valid-maxLength',
							array(
								'%s' => $item['attr']['item-name'],
								'%d' => $item['attr']['maxLength']
							)
						));
					break;

					default:
					break;
				}

			}
		}

		foreach ($args as $key => $val) self::$_formAry[$key]['submit_value'] = $val;

		return $ret;
	}


	static function ajaxReturn() {
		return json_encode(array('ret'=> array('status' => 'true', 'action' => self::$_onSuccess)));
	}

	private static $_onSuccess = array();
	static function onSuccess($action, $data='') {
		self::onEvent(self::$_onSuccess, $action, $data);
	}

	private static $_onCancel = array();
	static function onCancel($action, $data='') {
		self::onEvent(self::$_onCancel, $action, $data);
	}

	static function onEvent(&$event_var, $action, $data) {

		switch($action) {
			case 'reload':
				$event_var['reload'] = true;
				break;

			case 'alert':
				$event_var['alert'][] = $data;
				break;

			case 'redir':
				if (!$event_var['redir']) $event_var['redir'] = $data;
				break;

			case 'parentUrl':
				if (!$event_var['parentUrl']) $event_var['parentUrl'] = $data;
				break;

			case 'nothing':
				$event_var['nothing'] = true;
				break;
		}
	}


	/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//dynamic modal
	private static function addDynamicEditModalCore(){
		$ok = _T('ok');
		$cancel = _T('cancel');
		echo<<<html
<div id="dynamicEditModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="dynamicTitle" aria-hidden="true">
 	<div class="modal-header">
    	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">?</button>
    	<h3 id="dynamicTitle"></h3>
  	</div>
  	<div class="modal-body">
    	<iframe style='border:0; width:100%;'></iframe>
  	</div>
  	<div class="modal-footer">
	    <button class="btn" data-dismiss="modal" aria-hidden="true">{$cancel}</button>
	    <button id="dynamicEditModalSubmitButton" class="btn btn-primary">{$ok}</button>
  	</div>
</div>
html;

		$hostName = REAL_HOSTNAME;
		$script=<<<js
$("body").bind('formPanelReady', function(){
	$("a[data-toggle='modal'][data-role='dynamicAdd']").click(function(){
		var that = $(this),
			param = {
				'source'  : that.attr('data-source')
			};
		$("#dynamicTitle").html(that.attr('data-title'));
		$("#dynamicEditModal").attr('data-current', param.source);

		$("#dynamicEditModal iframe")
			.attr('src', '//{$hostName}' + that.attr('data-url'))
			.css('height', (typeof that.attr('data-height') === 'undefined') ? 'auto' :  that.attr('data-height') )
	});
});

$("#dynamicEditModalSubmitButton").bind('click', function(){
	$("#dynamicEditModal iframe")[0].contentWindow.fm_obj.submit();
});
function dynamicEditCallback(data){
	$("#" + $("#dynamicEditModal").attr('data-current')).trigger({'type':'dynamic.cb', 'ret': data.ret});
	$("#dynamicEditModal").removeAttr('data-current').modal("hide");
}
js;
		js::add($script);
	}

	private static function addDynamicEditModalJS($id, $script) {
		$s =<<<js
$("#{$id}").bind('dynamic.cb', function(data){
	(function(){
		{$script}
	}).call(this);
});
js;
		self::$_dynamicJS[] = $s;
	}
	//dynamic modal
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////



	/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//event
	private static function eventHandler($id, $k, $v) {
		// trigger
		if ($k === 'data-form-trigger' && is_array($v)) {
			list($when, $fire) = $v;
			$js=<<<js
$("#{$id}").bind('{$when}', function(){
	$('body').trigger({type: '{$fire}', 'ret': {source: this}});
});
js;
			js::add($js);
			$v = $fire;
		}

		// listener
		if ($k === 'data-form-listener' && is_array($v)) {
			list($when, $doSth) = $v;
			self::registerListener($id, $when, $doSth);
			$v = $when;
		}
		return $v;
	}

	private static function registerListener($id, $when, $doSth) {
		$s=<<<s
'$when': function(data) {
	$("[data-form-listener='{$when}']").trigger({type: '{$when}', 'ret': {source: data.ret.source}});
}
s;
		$c=<<<c
$("#{$id}").bind('{$when}', function(data){
	(function(){
		{$doSth}
	}).call(this);
	return false; // avoid infinity loop
});
c;
		self::$_eventSystem[$when] = $s; // avoid duplicate
		self::$_eventCustom[] = $c;
	}

	private static function renderEvent() {

		if (!self::$_eventSystem) return;

		// core js
		$js = array();
		foreach (self::$_eventSystem as $k => $v) {
			$js[] = $v;
		}

		$jsStr = join(",", $js);
		$s =<<<s
$("body").bind({
{$jsStr}
});
s;

		// custom js
		$c = join("", self::$_eventCustom);

		js::add($s);
		js::add($c);
	}
	//event
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////

































	static function progress($progress_msg = '', $progress_js = '') {

		if ($progress_msg == '') $progress_msg = 'Uploading, please wait';
		if ($progress_js == '')  $progress_js = "function clearProgressMsg() { try { $('#bar').hide(); } catch (e) {}; }";
		$icon_path = ICON_PATH;

		$script = <<<JS
		$(function(){
			$('body').append("<div id='bar'>&nbsp;<img src='{$icon_path}/wait.gif' align='absmiddle' /><b>&nbsp;&nbsp; {$progress_msg} ...&nbsp;</b></div>");
		});

		function showMsg() {

			var b = $(window);
			var x = $('#bar');

			var centerX = (b.width() / 2) - 100;
			var centerY = b.scrollTop() + (b.height() / 2) - 60;
			if (centerY < 0) centerY = b.height() / 2 - 10 ;

			x.css({position: 'absolute', left: centerX + 'px', top: centerY + 'px'});
			x.show();
		}

		{$progress_js}
JS;
		js::add($script);

	}

}