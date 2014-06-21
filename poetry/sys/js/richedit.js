var editorStyle	= new Array();
var editorName;
CKEDITOR.env.isCompatible = true; 

function createEditor(name, width, height)
{
	$(function(){
		CKEDITOR.replace(name);
	});
}

function insertEditorValue(str, name) {
	CKEDITOR.instances[name].insertHtml(str);
}

function replaceEditor(str, name) {
	CKEDITOR.instances[name].setData(str);
}


function destroyEditor(name) {
	CKEDITOR.instances[name].destroy();
}

function _strip_tag(str)
{
    str = str.replace(/(<\/?[^>]+>)/gi, '');
    str = str.replace('&nbsp;', '');
    str = str.replace(' ', '');

    return str;
}

function htmlspecialchars(string, quote_style, charset, double_encode)
{
    var optTemp = 0,
        i = 0,
        noquotes = false;
    if (typeof quote_style === 'undefined' || quote_style === null) {
        quote_style = 2;
    }
    string = string.toString();
    if (double_encode !== false) { // Put this first to avoid double-encoding
        string = string.replace(/&/g, '&amp;');
    }
    string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');

    var OPTS = {
        'ENT_NOQUOTES': 0,
        'ENT_HTML_QUOTE_SINGLE': 1,
        'ENT_HTML_QUOTE_DOUBLE': 2,
        'ENT_COMPAT': 2,
        'ENT_QUOTES': 3,
        'ENT_IGNORE': 4
    };
    if (quote_style === 0) {
        noquotes = true;
    }
    if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
        quote_style = [].concat(quote_style);
        for (i = 0; i < quote_style.length; i++) {
            // Resolve string input to bitwise e.g. 'ENT_IGNORE' becomes 4
            if (OPTS[quote_style[i]] === 0) {
                noquotes = true;
            }
            else if (OPTS[quote_style[i]]) {
                optTemp = optTemp | OPTS[quote_style[i]];
            }
        }
        quote_style = optTemp;
    }
    if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
        string = string.replace(/'/g, '&#039;');
    }
    if (!noquotes) {
        string = string.replace(/"/g, '&quot;');
    }

    return string;
}

function setEditorFocus(name) {
	var editor = CKEDITOR.instances[name];
	if (editor) editor.focus();
}

function setEditable(name, value, _ext) {
	CKEDITOR.replace(name, _ext);
}

function getEditorValue(name) {
	var _editorContent = CKEDITOR.instances[name].getData();

	if (_editorContent.match(/<img/gi)	|| _editorContent.match(/<embed/gi) || _editorContent.match(/<object/gi) || _editorContent.match(/<iframe/gi)) {
		return _editorContent;
	}

	return (_strip_tag(_editorContent) == "") ? "" : _editorContent;
}


