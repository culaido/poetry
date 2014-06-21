<?php
Class layoutMgr {

	const POS_FRONT = 0;
	const POS_REAR  = 1;
	
	private static $pageId;
	const JS_PREFIX = '_lm_';

	public static function getPageId() {
		return self::$pageId;
	}

	public static function setPageId($pageId) {
		self::$pageId = $pageId;
	}

	public static function get() {

		list($page, $id) = lib::parsePageId(self::$pageId);
		
		$Mods    = db::select('SELECT * FROM layout WHERE (pageId = ? OR pageId = ?) AND disabled=0 ORDER BY pageId ASC, box ASC, pos ASC, sn ASC', array(self::$pageId, $page));
		
		$filteredLayout = array('base' => array(), 'ext' => array());
		while (($row = $Mods->fetch()) !== FALSE)
		{
			if ($row['pageId'] === self::$pageId)
				$filteredLayout['ext'][] = $row;
			else
				$filteredLayout['base'][] = $row;
		}

		$pageModules = (empty($filteredLayout['ext'])) ? $filteredLayout['base'] : $filteredLayout['ext'];

		$extMods = array();
		foreach ($pageModules as $mod) {
			$pos = ($mod['pos'] == layoutMgr::POS_FRONT) ? 'front' : 'rear';

			$actionAry = array('action' => array($mod['action'] => array(
														'blockId'		=> $mod['id'],
														'pageId'		=> self::$pageId,
														'title'			=> $mod['title'],
														'args' 			=> json_decode($mod['args'], TRUE)
													  )),
							   'conf'	=>	json_decode($mod['conf'], TRUE)
							  );

			$extMods[ $mod['box'] ] [$pos] [] [ $mod['module'] ] = $actionAry;
		}
		
		return $extMods;
	}

	public static function getAddModuleItem() {

		// TODO: enabled in the future
		//if (profile::$role != _SYS_ROLE::SYSOP) return;
		if ( !Privilege::role( array(_SYS_ROLE::SYSOP) )) return;

		global $m;
		$js_prefix = self::JS_PREFIX;
		self::addModuleScript();

		return array('text' => $m['addModule'], 'link' => "javascript:{$js_prefix}custLayout(this);");
	}

	public static function getToolboxFunc($blockId, $func=array('edit' => 'editModule', 'move' => 'move', 'del' => 'delete'))
	{
		global $m;
		$js_prefix = self::JS_PREFIX;

		foreach($func as $f => $v)
		{
			$auth = lib::makeAuth( array(self::$pageId, $blockId, $f) );
			$text = isset($m[$v])?$m[$v]:$v;

			switch($f)
			{
				case 'move':

					$arrFunc['move'] = array('auth' => $auth);
					break;

				case 'del':
					$arrFunc['dropMenu'][$f] = array(
						'text'	=> $text,
						'func'	=> "{$js_prefix}delModule",
						'param'  => array($blockId, $auth)
					);
					break;

				default:
					$arrFunc['dropMenu'][$f] = array(
						'text'	=> $text,
						'func'	=> "{$js_prefix}execModule",
						'param' => array($text, $blockId, $f, $auth)
					);
					break;
			}

		}

		return $arrFunc;
	}

	public static function getModuleToolbox($blockId, $func = array()) {

		global $m;
		static $scriptAdded;

		$js_prefix = self::JS_PREFIX;

		foreach($func as $n => $v)
			$arrFunc[$n] = $v;

		return $arrFunc;
	}

	private function addModuleScript() {
		static $scriptAdded;

		if ($scriptAdded) return;
		$scriptAdded = TRUE;
		///////////////////////////////////

		global $m;
		$pageId = self::$pageId;

		$auth = lib::makeAuth($pageId);

		js::addlib(JS_PATH . '/lib.js');

		$js_prefix = self::JS_PREFIX;
		$module_enabled = (<<<TXT
		{"module-enabled": [ {"mbox": ["front","rear"]}, {"xbox1": ["rear"]}, {"xbox2": ["rear"]} ]}
TXT
);
        $script =<<<JS

			tmpblockid = 0;
			var g_lastBox = '';
			var g_lastBoxCssBorder = '';
			var g_lastBoxCssMinHeight = '';
			function {$js_prefix}reocveryLastBox() {
				if (g_lastBox != '')
				{
					$('#'+g_lastBox).css('border', g_lastBoxCssBorder);
					$('#'+g_lastBox).css('min-height', g_lastBoxCssMinHeight);
				}
			}
			function {$js_prefix}delModule(blockId, auth) {
				if (!confirm('{$m['cfDeletePS']}')) return;

				$.post('/ajax/sys.pages.layout/layout.delModules', _param={pageId: '{$pageId}', blockId: blockId, auth: auth}, function(obj) {
					$('#blockId_' + obj.ret.blockId).remove();
				}, 'json');
			}

			function {$js_prefix}execModule(text, blockId, action, auth) {
				fs.ui.JModal(text, '/layout/editmod/' + blockId + '/?pageId={$pageId}&action=' + action + '&auth=' + auth, 820, 500);

			}
            function {$js_prefix}custLayout(el) {
				if ($("#panel").length > 0) {
					$("#panel").focus();
					$("#panel").show();
					$('[role="placeable"]').disableSelection().sortable('enable');

					return;
				}
				$.post('/ajax/sys.pages.layout/layout.selectModule', _param={pageId:'{$pageId}', auth:'{$auth}'}, function(obj){
					var _panel = $('<div id="panel" style="background: #fff; z-index: 1000"></div>');
					_panel.css({position: 'fixed', top : 100, left : 900});
					_panel.html(obj.ret.panel);

					$('body').append(_panel);

					$('#panel').draggable({refreshPositions: true});

					$('[role="placeable"]').sortable('enable');

					$('[role="newModule"]').draggable({
						connectToSortable	: "[role='placeable']",
						refreshPositions	: true,
						helper		: 'clone',
						appendTo	: 'body',
						scrollSensitivity: 40,
						tolerance	: 'pointer',
						start		:  function(event, ui){
							ui.helper.addClass('fs-start-drag');

							$('[role="placeable"]').each(function(idx, item){
								if (($(item).height()*$(item).width()) < 500) {
									$(item).addClass("fs-zone-empty");
								}
							});

							$('[role="placeable"]').sortable('refreshPositions');
						},
						stop		: function(event, ui){
							$('[role="placeable"]').each(function(idx, item){
									$(item).removeClass("fs-zone-empty");
							});

						}

					});

					$('#selectClose').click(function(){ $('#panel').hide();  });
					{$js_prefix}showModuleSelectbox({$module_enabled});

				}, 'json');
			}

			function {$js_prefix}applyTemplate(auth) {
				if (fs.ui.popup)
					fs.ui.closeJPopup();
				else {
					fs.ui.JModal('{$m['applyTemplate']}', '/layout/applyTpl/{$pageId}' + '/?auth=' + auth, 820, 500);
				}
			}

			function {$js_prefix}addModule(box, module, pblockId) {

				if (box===''  || typeof box === "undefined") { alert('{$m['chooseBoxFirst']}');  $('select#boxSelector').focus(); return; }
				if (module==='' || typeof module === "undefined") { alert('{$m['chooseModuleFirst']}'); $('input[name=module]:checked').focus(); return; }

				fs.ui.closeJPopup();
				$('#selectClose').click();

				fs.ui.JModal('{$m['addModule']}', '/layout/add/{$pageId}/?auth={$auth}&module=' + module + '&box=' + box + '&pblockId=' + pblockId, 800, 500);
			}

			function {$js_prefix}showModuleSelectbox(_mod) {
                for (var _idx in _mod) {
                    for (var _key in _mod[_idx]) {

                        var _box = _key;
                        var _pos = _mod[_idx][_key];
                        var _tmp = $('<div></div>').attr({
                            role     : 'placeable',
                            position : _pos,
                            info     :_box + '-' + _pos
                        });

                        _tmp.html(_box + '-' + _pos);
                        (_pos == 'front') ? $('#' + _box).prepend(_tmp) : $('#' + _box).append (_tmp);
					}
                }
            }

			$(document).ready(function(){

				$('[role="placeable"]').sortable({
					refreshPositions: true,
					cursorAt	: {top: 20, left: 50},
					items   	: '[role="module"]',
					placeholder	: 'fs-placeholder',
					forcePlaceholderSize: true,
					opacity 	: .8,
					tolerance	: 'pointer',
					cursor  	: 'move',
					dropOnEmpty: true,
					connectWith	: '[role="placeable"]',
					update  	: function(event, ui){
						if (this !== ui.item.parent()[0]) return;

						if (ui.item.attr('role') == 'newModule') {
							{$js_prefix}addModule($(this).attr('id'), ui.item.attr('rel'), 0);
							ui.item.remove();
							return;
						}

						ui.item.css('opacity', .3).animate({'opacity': 1});

						var box		 = ui.item.parent('[role="placeable"]').attr('id');
						var pblockId = (ui.item.prev().length > 0) ? ui.item.prev().attr('id') : null;

						var auth	 = ui.item.attr('auth');
						var blockId	 = (typeof ui.item.attr('id')==='undefined')?0:ui.item.attr('id').split('_');

						if (pblockId) {
							pblockId = pblockId.split('_');
							pblockId = pblockId[1];
						}

						$.post('/ajax/sys.pages.layout/layout.moveModule', _param={box: box, blockId: blockId[1], auth: auth, pageId:'{$pageId}', pblockId:pblockId},
							function(obj) {
								if (obj.ret.status != 'true') {
									alert(obj.ret.msg);

									$('[role="placeable"]').sortable('cancel');
									return;
								}

						}, 'json');
					},

					stop: function(){
						$('[role="placeable"]').removeClass("fs-zone-empty");
					},

					start:  function(event, ui){
						ui.item.addClass('fs-start-drag');
						$('[role="placeable"]').each(function(idx, item){
							if (($(item).height()*$(item).width()) < 500) {
								$(item).addClass("fs-zone-empty");
							}
						});

						$('[role="placeable"]').sortable('refreshPositions');

						ui.item.css({width: '', height: ''});
					},
					deactivate: function(event, ui){
						ui.item.removeClass('fs-start-drag');
					}
				}).sortable('disable');

				$('[role="move"]').on({
					mouseenter : function(e){ $('[role="placeable"]').disableSelection().sortable({disabled: false }); }
				});

				$(document).mouseup(function(e){ $('[role="placeable"]').enableSelection().sortable({ disabled: true }); });
			});
JS;
		js::add($script);
	}

	public static function deletePage($pageId)
	{
		$items = db::select('SELECT * FROM layout WHERE pageId = ? ', array($pageId));

		while ($layout = $items->fetch()) {
			require_once(MODULE_PATH . "/{$layout['module']}/{$layout['module']}.php");

			call_user_func(array($layout['module'], 'cfg_delete'), $layout['id'], $layout['args']);
		}

		db::query('DELETE FROM layout WHERE pageId = ? ', array($pageId));
	}

	public static function merge($mods, $extMods)
	{
		//sys::print_var($mods, 'mods');
		//sys::print_var($extMods, 'extMods');

		// TODO: need to process if module-enabled is set.
		$module_enabled = $mods['module-enabled'];

		$Modules = array();
		// proc inline modules
		if (is_array($mods))
		{
			foreach($mods as $box => $boxMods)
			{
				if ($box=='module-enabled') continue;

				//$Modules[$box]['inline'] = is_array($boxMods)?$boxMods:array();

				if( is_array($boxMods) )
				{

					foreach($boxMods as $Mods)
					{
						// INFO: Prevent the case such as "xbox":[{}] <- NULL-Contented Object
						if(count($Mods) <= 0) continue;
					
						$moduleName = key($Mods);
						$conf = '';
						if(array_key_exists('action', $Mods[$moduleName]))
						{
							$subAction = $Mods[$moduleName]['action'];
							$conf = $Mods[$moduleName]['conf'];

							$mod_action = key($subAction);
							$mod_param = $subAction[$mod_action];
						}
						else
						{
							foreach($Mods as $action)
							{
								$mod_action = key($action);
								$mod_param = $action[$mod_action];
							}
						}

						$Modules[$box]['inline'][][$moduleName][$mod_action] =	array('blockId'		=> '',
																					  'pageId'		=> self::$pageId,
																					  'title'		=> '',
																					  'args' 		=> $mod_param,
																					  // INFO: inline's conf has been decoded by default
																					  'conf'		=> is_array($conf) ? $conf : json_decode($conf, TRUE));
					}
				}
				else
					$Modules[$box]['inline'] = array();

			}
		}

		if (is_array($extMods))
		{
			foreach($extMods as $box => $boxMods)
			{
				//sys::print_var($boxMods);
				$Modules[$box]['front'] = is_array($boxMods['front'])?$boxMods['front']:array();
				$Modules[$box]['rear']  = is_array($boxMods['rear'])?$boxMods['rear']:array();
			}
		}

		//sys::print_var($Modules);
		return $Modules;
	}
	
	
	
	
	public static function panel( $id, $method=array('edit' => array('width' => 800, 'height' => 500) , 'delete' => array() ) ){
	
		global $m;
		
		if (!$id) {
			return;
		}
		
		$pageId = self::$pageId;

		$panel = '';
		$layoutUrl = array();
		
		$owner = lib::getOwner($pageId);

		$layoutUrl = array('edit' => '/layout', 'delete' => '/ajax/sys.pages.layout/layout.delModule/');

		foreach ($method as $f=>$v) {

			$idx = ( is_array($v) ) ? $f : $v;
			
			switch ($idx) {
			
				case 'edit':
					// 'edit' => array('text' => 'title', 'hint' => 'hint', 'width' => 750, 'height' => 500)
					$text	= "<img border=0 src='" . ICON_PATH . "/edit-gray.png' />";
					$hint 	= lib::htmlChars($v['hint']);
					$width  = 750;
					$height = 500;
					
					if ( is_array($v) ) {
						if ( isset($v['text']) )	$text	= $v['text'];
						if ( isset($v['hint']) )	$hint	= lib::htmlChars($v['hint']);
						if ( isset($v['width']) )	$width	= $v['width'];
						if ( isset($v['height']) )	$height	= $v['height'];
					}

					$panel .= "<a title='{$hint}' href='javascript:editModule({$id}, \"{$pageId}\", {$width}, {$height})' >{$text}</a>";
				
				break;

				case 'delete':
					// 'delete' => array()

					$auth = lib::ajaxAuth($layoutUrl['delete'], array($id) );
					$panel .= "<a title='{$m['delete']}' href='javascript:delModule({$id}, \"{$auth}\")'><img border=0 src='" . ICON_PATH . "/cross-gray2.png' /></a>";
				break;
	
				default:
					// TO DO custom mgr
				break;
			}
		}
		return "<div class='panel'>{$panel}</div>";
	}
	
	
	public static function DefaultConf()
	{
		return array('edit'	=> TRUE, 'move'	=> TRUE, 'delete'=> TRUE);
	}
	
	public static function procCfg($config)
	{
		$defaultConf = self::DefaultConf();

		if(!is_array($config)) return $defaultConf;

		foreach($defaultConf as $key => $value)
		{
			if(array_key_exists($key, $config)) continue;
			$config[$key] = $value;
		}

		return $config;
	}

	
	public static function moveTo($pageId, $srcBlockId, $dstBox, $dstBlockId, $pos=1) {

		$dst_mod = db::fetch("SELECT sn, pos FROM layout WHERE id = ?", array($dstBlockId));

		$sn = (!$dst_mod) ? 5 : ($dst_mod['sn'] + 5);
		db::query('UPDATE layout SET sn = :sn, box=:box, pos=:pos WHERE id=:blockId',
			array(':sn'	 	 => $sn,
				  ':blockId' => $srcBlockId,
				  ':pos'	 => $pos,
				  ':box'	 => $dstBox ));

				  
		$qry = <<<SQL
			UPDATE layout as L,
				(SELECT id, (@rownum := @rownum + 10) as rownum
					FROM layout, (SELECT @rownum :=0) as R
					WHERE pageId = :pageId AND box = :box AND pos = :pos ORDER BY sn) as T
			SET L.sn = T.rownum
			WHERE L.id=T.id
SQL;
		// reorganize src, dst box
		db::query($qry, array(':pageId'=>$pageId, ':box'=> $dstBox, ':pos'=>$pos));				  
	}


	public static function loadDynamicLayoutModules($pageId, $dbRaw = FALSE)
	{
		$moduleFetcher = db::select("SELECT * FROM layout WHERE pageId=:pageId AND pos='1' ORDER BY box, sn",
									 array(':pageId' => $pageId));

		$layout = array();
		while($module = $moduleFetcher->fetch())
		{
			if(!$dbRaw)
			{
				if(!is_array($layout[$module['box']])) $layout[$module['box']] = array();
				$layout[$module['box']][] = array($module['module'] => array('action' => array($module['action'] => json_decode($module['args'], TRUE)),
																			 'conf' => json_decode($module['conf'], TRUE),
																			 'dbId'	=> $module['id']));
			}
			else
			{
				$layout[] = $module;
			}
		}

		return $layout;
	}
	

	public static function applyTemplate($pageId, $curTplId = 0, $newTplId = 1)
	{
		if($curTplId==$newTplId) return;

		// 1. load current settings.
		if($curTplId!=0)
		{
			$cur_tpl = db::select('SELECT * FROM layout WHERE pageId = ?', array($pageId));

			$cur_extMods = array();
			while ($obj = $cur_tpl->fetch()) {
				$cur_extMods[] = array(	'pageId' => $pageId,
										'module' => $obj['module'],
										'title'	 => $obj['title'],
										'action' => $obj['action'],
										'box'	 => $obj['box'],
										'pos'	 => $obj['pos'],
										'sn'	 => $obj['sn'],
										'args'	 => $obj['args'] );
			}

			// replace old settings into `layout_bak'
			db::query('REPLACE INTO layout_bak SET pageId = :pageId, templateID = :templateID, data = :data',
				array(':pageId' => $pageId, ':templateID' => $curTplId, ':data' => json_encode($cur_extMods) ) );
		}

		// 2. load old template
		$old_tpl = db::fetch('SELECT * FROM layout_bak WHERE pageId = :pageId AND templateID = :templateID',
			array(':pageId' => $pageId, ':templateID' => $newTplId) );

		// 3. if the old template exists
		if ( $old_tpl ) {
			$values = json_decode($old_tpl['data'], TRUE);
		}

		if(count($values)===0) {
			global $m;
			$obj = db::fetch('SELECT ext_mods FROM layout_tpl WHERE id = ?', array($newTplId));

			$json = addcslashes($obj['ext_mods'], '\"' );

			eval("\$data = \"{$json}\";");
			$layoutData = json_decode($data, TRUE);

			if(count($layoutData) > 0) {
				foreach($layoutData as $box => $Mods)
				{
					$sn = 0;
					foreach($Mods as $mod)
					{
						$sn += 10;
						$name = key($mod);

						$values[] = array(
									'pageId'	=> $pageId,
									'module'	=> $name,
									'title'		=> $mod[$name]['title'],
									'action'	=> $mod[$name]['action'],
									'box'		=> $box,
									'pos'		=> $mod[$name]['pos'],
									'sn'		=> $sn,
									'args'		=> $mod[$name]['args']);
					}
				}
			}
		}
		
		// 4. remove the current settings in layout
		db::query('DELETE FROM layout WHERE pageId = ?', array($pageId));

		// 5. insert the new settings
		db::multiQuery('layout', $values);
	}
	
	public static function loadConfig($pageId)
	{
		$config = db::fetch('SELECT * FROM layout_cfg WHERE pageId=?', array($pageId));
		if (!$config)		
		{
			$config['value'] = '{"init":"0","templateId":"0"}';

			db::query('INSERT INTO layout_cfg SET pageId=:pageId, value=:value',
					   array('pageId'	=> $pageId,
					   		 'value'	=> $config['value']));
		}

		return json_decode($config['value'], TRUE);
	}
	
// NOTE: Remember to check the template of group....
	public static function loadTemplates($page, $userId = -1, $pageType = 'type', $userType = 'u')
	{
		if($userId == 0) $userType = _OWNER_TYPE::user;
		if ($pageType == 'type')
		{
			$query = $userId < 0 ? 'SELECT * FROM `layout_tpl` WHERE `page` = :page AND `userType` = :userType ORDER BY `id` ':
								   'SELECT * FROM `layout_tpl` WHERE `page` = :page AND `userType` = :userType AND `userId` = :userId ORDER BY `id` ';


			// INFO: In order to keep the coherence of the default pages,
			// INFO: the system will loads the default layouts ordered by created sequence
			$pageTemplates = db::select($query.($userId == 0 ? ' ASC' : 'DESC'),
										array(':page'	=> $page,
											  ':userId'	=> $userId,
											  ':userType' => $userType));
		}
		elseif ($pageType == 'id')
		{
			$pageTemplates = db::select('SELECT * FROM `layout_tpl` WHERE `id` = :page',
										array('page'	=> $page));
		}

		$templates = array();
		while($template = $pageTemplates->fetch())
		{
			$templates[] = array('id'		=> $template['id'],
								 'userId'	=> $template['userId'],
								 'userType'	=> $template['userType'],
								 'page'		=> $template['page'],
								 'title'	=> $template['title'],
								 'note'		=> $template['note'],
								 'layout'	=> json_decode($template['layout'], TRUE));
		}

		return $templates;
	}
	
	public static function createTemplate($pageStructure, $templateName, $owner, $page)
	{
		$jsonLayout = json_encode($pageStructure);

		$result =   db::query('INSERT INTO `layout_tpl`(`userId`, `userType`, `page`, `title`, `note`, `layout`)
												VALUES (:userId, :userType, :page, :title, :note, :layout)',
												array(
													 ':userId'		=> $owner['id'],
													 ':userType'	=> $owner['type'],
													 ':page'		=> $page,
													 ':title'		=> $templateName,
													 ':note'		=> '',
													 ':layout'		=> $jsonLayout
												));

		if($result <= 0) return FALSE;
		return TRUE;
	}
	
	
	
	public static function transferLayout($source, $target)
	{
		$boxSN = array();

		$srcAry = array('theme' => array(), 'custom' => array());
		foreach($source as $box => $modules)
		{
			if($box == 'mbox') continue;
			foreach($modules as $module)
			{
				foreach($module as $name => $content)
				{
					if(substr($name, 0, 4) != 'mod_') continue;

					$identifier = $content['conf']['id'];
					if($identifier)
					{
						$srcAry['theme'][$identifier]['module'] = $module;
						$srcAry['theme'][$identifier]['box'] = $box;
						$srcAry['theme'][$identifier]['sn'] = 0;
					}
					else
					{
						$srcAry['custom'][] = array('module' 	=> $module,
													'box' 		=> $box,
													'sn'		=> 0);
					}
				}
			}
		}

		$tarAry = array('theme' => array(), 'custom' => array());
		foreach($target as $box => $modules)
		{
			$boxSN[$box] = 10;

			if($box == 'mbox') continue;
			foreach($modules as $module)
			{
				foreach($module as $name => $content)
				{
					if(substr($name, 0, 4) != 'mod_') continue;

					$identifier = $content['conf']['id'];
					if($identifier)
					{
						$tarAry['theme'][$identifier]['module'] = $module;
						$tarAry['theme'][$identifier]['box'] = $box;
						$tarAry['theme'][$identifier]['sn'] = $boxSN[$box];
					}
					else
					{
						$tarAry['custom'][] = array('module' 	=> $module,
													'box' 		=> $box,
													'sn'		=> $boxSN[$box]);
					}

					$boxSN[$box] += 10;
				}
			}
		}

		$matchResult = array('modified' => array(), 'inserted' => array(), 'remained' => array());

		foreach($tarAry['theme'] as $key => $moduleBuffer)
		{
			if (array_key_exists($key, $srcAry['theme']))
			{
				$filtered = self::replaceLayoutModule($srcAry['theme'][$key], $tarAry['theme'][$key]);
				$filtered['filtered']['box'] = $moduleBuffer['box'];
				$filtered['filtered']['sn'] = $moduleBuffer['sn'];

				$matchResult[$filtered['zone']][] = $filtered['filtered'];
			}
			else
			{
				$selected = $tarAry['theme'][$key];
				$name = key($selected['module']);
				if(!$selected['module'][$name]['conf'])
					$selected['module'][$name]['conf']  = array();

				$matchResult['inserted'][] = $selected;
			}
		}

		foreach($tarAry['custom'] as $moduleBuffer)
			$matchResult['inserted'][] = $moduleBuffer;


		// NOTE: The code segments below doesn't care about the relative sn of the unexpected custom modules
		// NOTE: Hence, the order among these deletable modules may be wrong....
		foreach($srcAry['theme'] as $key => $moduleBuffer)
		{
			if (!array_key_exists($key, $tarAry['theme']))
			{
				$selected = $srcAry['theme'][$key];
				$name = key($selected['module']);
				if($selected['module'][$name]['conf'])
					$selected['module'][$name]['conf']['delete'] = TRUE;
				else
					$selected['module'][$name]['conf']  = array('delete' => TRUE);

				$selected['sn'] = $boxSN[$selected['box']];
				$boxSN[$selected['box']] += 10;

				$matchResult['remained'][] = $selected;
			}
		}

		foreach($srcAry['custom'] as $moduleBuffer)
		{
			$name = key($moduleBuffer['module']);
			if($moduleBuffer['module'][$name]['conf'])
				$moduleBuffer['module'][$name]['conf']['delete'] = TRUE;
			else
				$moduleBuffer['module'][$name]['conf']  = array('delete' => TRUE);

			$moduleBuffer['sn'] = $boxSN[$moduleBuffer['box']];
			$boxSN[$moduleBuffer['box']] += 10;

			$matchResult['remained'][] = $moduleBuffer;
		}

		return $matchResult;
	}
	
	
	public static function modifyLayoutModules($pageId, $modifyList, $disabled = false)
	{
		$baseQuery = 'UPDATE `layout` SET `disabled`=:disabled, `pageId`=:pageId, `box`=:box, `args`=:args, `conf`=:conf, `sn`=:sn WHERE `id`=:id';

		foreach($modifyList as $moduleBuffer)
		{
			$box	= $moduleBuffer['box'];
			$module = key($moduleBuffer['module']);
			$action = key($moduleBuffer['module'][$module]['action']);
			$args	= $moduleBuffer['module'][$module]['action'][$action];
			$conf	= $moduleBuffer['module'][$module]['conf'];
			$id		= $moduleBuffer['module'][$module]['dbId'];
			$sn		= $moduleBuffer['sn'] ? $moduleBuffer['sn'] : 10;

			db::query($baseQuery,
					  array(':pageId'	=>	$pageId,
					  		':box'		=>	$box,
							':args'		=>	json_encode($args),
							':conf'		=>	$conf ? json_encode($conf) : '',
							':id'		=>	$id,
							':sn'		=>	$sn,
							':disabled'	=>	!$disabled ? 0 : 1
					  		)
			);
		}
	}
	
	


	public static function saveConfig($pageId, $config)
	{
		db::query('UPDATE layout_cfg SET value=:value WHERE pageId=:pageId', 
			array('value'=>json_encode($config), 'pageId'=>$pageId));
	}

	
	

	public static function loadDynamicLayoutModule($moduleId)
	{
		$moduleFetcher = db::select("SELECT * FROM layout WHERE id=:moduleId AND pos='1' ORDER BY box, sn",
									array(':moduleId' => $moduleId));

		if($module = $moduleFetcher->fetch())
			return $module;
		else
			return NULL;
	}

	

	public static function deleteTemplate($templateId)
	{
		$result = db::query('DELETE FROM `layout_tpl` WHERE `id`=?', array($templateId));

		if($result <= 0) return FALSE;
		return TRUE;
	}



	private static function replaceLayoutModule($source, $target)
	{
		$nameSrc = key($source['module']);
		$nameTar = key($target['module']);

		$zone = 'modified';

		switch($nameSrc)
		{
			case 'mod_custHTML':
				$filtered = $source;
				$filtered['module'][$nameSrc]['conf'] = $target['module'][$nameTar]['conf'];
				break;
			default:
				$filtered = $target;
				break;
		}

		$filtered['module'][$nameTar]['dbId'] = $source['module'][$nameSrc]['dbId'];

		return array('zone' => $zone, 'filtered' => $filtered);
	}

	public static function insertLayoutModules($pageId, $insertList, $disabled = false)
	{
		global $m;

		$baseQuery = 'INSERT INTO layout SET disabled=:disabled, pageId=:pageId, box=:box, module=:module, action=:action,
											 title=:title, args=:args, conf=:conf, sn=:sn, pos=1';

		foreach($insertList as $moduleBuffer)
		{
			$box	= $moduleBuffer['box'];
			$module = key($moduleBuffer['module']);
			$action = key($moduleBuffer['module'][$module]['action']);
			$title	= $m[$module];
			$args	= $moduleBuffer['module'][$module]['action'][$action];
			$conf	= $moduleBuffer['module'][$module]['conf'];
			$sn		= $moduleBuffer['sn'] ? $moduleBuffer['sn'] : 10;

			db::query($baseQuery,
					  array(':pageId'	=>	$pageId,
							':box'		=>	$box,
							':module'	=>	$module,
							':action'	=>	$action,
							':title'	=>	$title,
							':args'		=>	json_encode($args),
							':conf'		=>	$conf ? json_encode($conf) : '',
							':sn'		=>	$sn,
							':disabled'	=> !$disabled ? 0 : 1
					  )
			);
		}
	}


}