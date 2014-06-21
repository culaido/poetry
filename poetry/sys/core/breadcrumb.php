<?php
class Breadcrumb {

	function __construct() {

	}

	public static function getPath($pageId) {
		$breadcrumb = array();
		list($type, $id) = lib::parsePageId($pageId);

		switch ($type) {

			case _PAGE::bulletin:
				$breadcrumb = prog::get('bulletin')->breadcrumb($id);
				break;
				
			case _PAGE::index:
				$breadcrumb = prog::get('index')->breadcrumb();
				break;
				
			case _PAGE::user:
				// not implement
				break;
			case _PAGE::folder:
				$breadcrumb = prog::get('folder')->breadcrumb($id);
				break;
			case _PAGE::course:
				$breadcrumb = prog::get('course')->breadcrumb($id);
				break;
			case _PAGE::exercise:
				$breadcrumb = prog::get('exercise')->breadcrumb($id);
				break;
			case _PAGE::content:
				$breadcrumb = prog::get('media')->breadcrumb($id);
				break;
			case _PAGE::faq:
				// not implement
				break;
			case _PAGE::poll:
				$breadcrumb = prog::get('mod_poll')->breadcrumb($id);
				break;
			case _PAGE::exam:
				$breadcrumb = prog::get('mod_exam')->breadcrumb($id);
				break;
			case _PAGE::upgrade:
				$breadcrumb = prog::get('upgrade')->breadcrumb($id);
				break;
			case _PAGE::km:
				$breadcrumb = prog::get('km')->breadcrumb($id);
				break;


			default:
				if (__DEBUG__)
					sys::exception('Unexcept PageId: ' . $pageId, 'Breadcrumb Unknow PageId Exception');
				break;
		}

		if (!empty($breadcrumb['parent'])) {
			$breadcrumb['path'] = array_merge(self::getPath($breadcrumb['parent']), $breadcrumb['path']);
		}

		return $breadcrumb['path'];
	}

}