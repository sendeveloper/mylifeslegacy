/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	config.format_tags = 'p;h1;h2;h3;pre';
	config.enterMode = CKEDITOR.ENTER_BR;
	config.allowedContent = true;
	config.width = '100%';
	config.toolbarGroups = [
		{ name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
		{ name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
		{ name: 'links' },
		{ name: 'insert' },
		{ name: 'tools' },
		{ name: 'document',	   groups: [ 'mode'] },
		{ name: 'others' },
		'/',
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		{ name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
		{ name: 'styles' },
		{ name: 'colors' }
	];
	config.filebrowserBrowseUrl = 'filemanager/browse.php?type=files';
	config.filebrowserImageBrowseUrl = 'filemanager/browse.php?type=images';
	config.filebrowserFlashBrowseUrl = 'filemanager/browse.php?type=flash';
	config.filebrowserUploadUrl = 'filemanager/upload.php?type=files';
	config.filebrowserImageUploadUrl = 'filemanager/upload.php?type=images';
	config.filebrowserFlashUploadUrl = 'filemanager/upload.php?type=flash';
};