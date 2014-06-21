/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	config.contentsLanguage = 'zh-tw';
	config.removePlugins = 'magicline,image2';

	config.extraPlugins = 'oembed';

	config.toolbar = [
        ['FontSize', 'Bold', 'Italic', 'Underline', 'Strike', 'Superscript', 'TextColor', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'RemoveFormat', 'Table', 'Image', 'Link', 'oembed', 'Source']
	];

	config.fontSize_sizes  = '12px/12px;13px/13px;14px/14px;16px/16px;18px/18px;24px/24px;36px/36px;48px/48px;72px/72px';
    config.font_names = 'Arial; Arial Black; Times New Roman; Verdana; 新細明體; 標楷體';

	config.enterMode = CKEDITOR.ENTER_DIV;
	config.pasteFromWordRemoveStyle = true;


	config.entities_additional = '#039';	/* [*] has two unicode #039 & #39, to avoid php error, normalized to #039 */

    config.keystrokes = [
        [CKEDITOR.CTRL + 90 /*Z*/, 'undo'],
        [CKEDITOR.CTRL + 89 /*Y*/, 'redo'],
        [CKEDITOR.CTRL + CKEDITOR.SHIFT + 90, 'redo'],
        [CKEDITOR.CTRL + 76 /*L*/, 'link'],
        [CKEDITOR.CTRL + 66 /*B*/, 'bold'],
        [CKEDITOR.CTRL + 73 /*I*/, 'italic']
    ];
	
	config.height = 360;			/* default height 180 */
};
