<?php

Class mod_template Extends Module {

	const RETURN_OUT_BUFF	= TRUE;
	const DIRECT_OUTPUT		= FALSE;

	const THEME_INFO_FILE = 'theme.info';
	const THEME_HAS_CUSTOM_STYLE = 1;

	const SYSTEM_THEME = 1;
	const EXTERNAL_THEME = 0;

	public function prepare() {

		$this->App->DOC->set('page.theme', array('name' => $record['name'], 'path' => $record['path']));

		return;
	}

/* **** */
	public function prepareTpl($pageId) {
		$this->App->DOC->set('page.tpl', $this->tpl($pageId), TRUE);
	}

	private function tpl($pageId) {

		$tpl = array();

		return $tpl;
	}


	
	
	public function renderTemplate($file, $vars = array(), $b_outBuff = FALSE) {

		extract($vars);

		if ($b_outBuff)
		{
			ob_start();
		    require ($file);
 			return ob_get_clean();
		}

		// normal process
		require ($file);
		return '';
	}

	
	public function load_tpl($tplFileName, $vars = array(), $returnOpt = self::DIRECT_OUTPUT) {

		// INFO: This is to initialize theme data when the system is working with AJAX logic
		if (count($this->App->DOC->get('page.theme')) <= 0) self::prepare();

		$templatePath	= $this->App->DOC->get('page.tpl');
		$themePath		= $this->App->DOC->get('page.theme.path');

		
		$path = ''; $searchRoute = array();
		$tplType = strtok($tplFileName, '.');
		if ($templatePath[$tplType] != '')
		{
			// INFO: Database must store full path
			$path = "/{$templatePath[$tplType]}";
		}
		else
		{
			$searchPath = array($themePath, TEMPLATE_PATH);
			
			foreach ($searchPath as $tplPath) {
				if (file_exists("{$tplPath}/{$tplFileName}"))
				{
					$path = "{$tplPath}/{$tplFileName}";
					break;
				}
				else
					$searchRoute[] = $tplPath;
			}

			if (empty($path))
			{
				$searchRoute = implode("\n\t", $searchRoute);
				sys::exception("Template file {$tplFileName} cannot be found in {$searchRoute}.");
			}
		}

		return self::renderTemplate($path, $vars, $returnOpt);
	}

	public function set_tpl($tplType, $tplFileName, $tplPath = '') {

		if ($tplPath == '')
			$tplPath = SITE_TEMPLATE_PATH . "/$tplFileName";
		else
			$tplPath =  "$tplPath/$tplFileName";

		$this->App->DOC->set('page.tpl', array($tplType, $tplPath));

		return;
	}
















}