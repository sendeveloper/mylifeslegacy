/*
 * @Author: Slash Web Design
 * App handler
*/

$(document).ready(function(){
	app.init();
});

$(window).resize(function(){
	app.resize();
});

$(document).on('click', '.btn-approve', function(){
	var 
		btn = $(this),
		username = btn.data('username');
	
	btn.html('<span class="spinner"></span>');
	request({
		data: {
			act: 'requests-approve',
			request_id: app.page.id,
			username: username
		},
		success: function(r){
			toastr(0, r.message);
			app.loadPage();
		}
	});
});

$(document).on('click', '.btn-decline', function(){
	request({
		data: {
			act: 'requests-decline',
			request_id: app.page.id
		},
		success: function(r){
			toastr(0, r.message);
			app.loadPage();
		}
	});
});

$(document).on('click', '.stars .ico', function(){
	$(this).parent().attr('data-value', $(this).index() + 1);
	$('input[name="rating"]').val($(this).index() + 1);
});

$(document).on('click', '.debug-holder .controller', function(){
	if ($('.debug-holder').height() === 300)
	{
		//close
		$('.debug-holder').animate({height: 10}, 250);
	}
	else
	{
		//open
		$('.debug-holder').animate({height: 300}, 250);
	}
});

$(document).on('click', '.menu-handle', function(){
	($('.menu').position().left === 0) ? app.hideMenu() : app.showMenu();
});

$(document).on('click', '.menu a[data-toggle="sub-menu"]', function(e){
	e.preventDefault();
	var subMenu = $(this).next('.sub-menu');
	
	if (subMenu.hasClass('open'))
	{
		subMenu.removeClass('open');
	}
	else
	{
		subMenu.addClass('open');		
	}
	app.initMenu();
});

$(document).on('click', '.menu ul li a', function(e){
	e.preventDefault();
	
	if (typeof $(this).attr('data-list') === 'undefined') return false;
	
	if ($(this).attr('data-list') === 'sign-out')
	{
		window.location.href = 'sign-out';
		return false;
	}
	
	app.page.name = $(this).attr('data-list'),
	app.page.act = 'getList';
	app.page.id = '';
	app.page.param.offset = 0;
	app.page.param.filter = '';
	app.page.param.sort = '';
	app.page.param.order = '';
	app.loadPage();
	app.hideMenuIfMobile();
});

$(document).on('click', 'input[type="checkbox"].master', function(){
	$(this).parent().parent().parent().parent().find('input[type="checkbox"].slave').prop('checked', $(this).is(':checked'));
});

$(document).on('click', '.pagination ul li a', function(e){
	e.preventDefault();
	app.page.param.offset = $(this).attr('data-offset');
	app.loadPage();
});

$(document).on('keyup', '.box .toolbar input[name="filter-term"]', function(e){
	if (e.keyCode === 13)
	{
		app.page.param.offset = 0;
		app.page.param.filter = $(this).val();
		app.loadPage();
	}
});

$(document).on('click', '.table-holder .ico-unfold-more', function(){
	app.page.param.sort = $(this).attr('data-sort');
	app.page.param.order = $(this).attr('data-order');
	app.loadPage();
});

$(document).on('click', '.box .toolbar .btn-delete', function(){
	var ids = [];
	
	$('input.slave').each(function(){
		if ($(this).is(':checked')) ids.push($(this).attr('data-id'));
	});
	
	if (ids.length === 0)
	{
		pp('Delete selected items', 'Please select at least one item from the list');
		return false;
	}

	pp('Are you sure?', 'Deleting selected items is permanent, you will not be able to undo this action', [
		{
			label: 'Delete',
			cls: ' btn-hl',
			callback: function(){
				app.showLoader('.app');
				request({
					data: {
						act: app.page.name + '-delete',
						ids: ids
					},
					success: function(r){
						toastr(r.error, r.message);
						app.hideLoader('.app');
						app.page.param.offset = 0;
						app.page.param.filter = '';
						app.loadPage();
					}
				});
			}
		},
		{
			label: 'Cancel',
			callback: function(){}
		}
	]);	
});

$(document).on('click', '.box .toolbar .btn-create', function(){
	app.page.id = '';
	app.page.act = 'getItem';
	app.loadPage();
});

$(document).on('click', '.box .toolbar .btn-back-to-list', function(){
	app.page.id = '';
	app.page.act = 'getList';
	app.page.offset = 0;
	app.page.filter = '';
	app.page.sort = '';
	app.page.order = '';
	app.loadPage();
});

$(document).on('click', '.table-holder a[data-toggle="item"]', function(e){
	e.preventDefault();
	app.page.id = $(this).attr('data-id');
	app.page.act = 'getItem';
	app.loadPage();
});
		
$(document).on('click', '.ico-close-page', function(){
	app.hidePage();
});

$(document).on('submit', '.must-process', function(){
	var rules = [];
	$('.must-process input, .must-process select, .must-process textarea').each(function(){
		if ($(this).attr('data-required') === 'true')
		{
			rules[$(this).attr('name')] = { required: true };
		}
	});

	$('.must-process').validate({rules: rules,errorPlacement:function(error,element){$(element).tooltip({title:$(error).text(),placement:'left',trigger:'manual'});$(element).tooltip('show');},success:function(l,element){$(element).tooltip('hide');}});
	
	if ($('.must-process').valid() === true)
	{
		app.submitPage();
	}
});

var app = {
	
	animationSpeed: 250,
	page: {
		name: '',
		act: '',
		id: '',
		param: {
			offset: 0,
			filter: '',
			sort: '',
			order: ''
		}
	},
	uploader: null,
	charts: [],
	
	loadPage: function(){
		app.showLoader('.app');
		app.unbindPlugins();
		request({
			data: {
				act: app.page.name + '-' + app.page.act,
				id: app.page.id,
				param: app.page.param
			},
			dataType: 'html',
			success: function(r){
				$('.app').html(r);
				app.bindPlugins();
				app.hideLoader('.app');
			}
		});
	},
	
	showLoader: function(selector){
		$(selector + ' > *').stop().animate({opacity: 0}, 500);
		$(selector).append('<div class="loader"></div>');
  	},
	
	hideLoader: function(selector){
		$(selector + ' > *').stop().animate({opacity: 1}, 500,	function(){	$('.loader').remove(); });
	},
	
	init: function(){
		$('.menu a[data-list="dashboard"]').click();
		app.resize();
	},
	
	resize: function(){
	},
	
	hideMenuIfMobile: function(){
		if ($(window).width() <= 720)
		{
			lg('mobile detected, hiding menu');
			app.hideMenu();
		}
	},
	
	showMenu: function(){
		$('.menu').removeClass('collapsed');
		$('.app').removeClass('enlarged');
		$('.header').removeClass('enlarged');
	},
	
	hideMenu: function(){
		$('.menu').addClass('collapsed');
		$('.app').addClass('enlarged');
		$('.header').addClass('enlarged');
	},
	
	submitPage: function(){
		app.showLoader('.app');
		
		if (app.page.name === 'statements')
		{
			stmData = 'type=' + encodeURIComponent($('select[name="type"]').val());
			if ($('select[name="district_id"]').is(':visible')) stmData += '&district_id=' + encodeURIComponent($('select[name="district_id"]').val());
			stmData += '&text=' + encodeURIComponent($('textarea[name="text"]').val());
			if ($('.parent-label').is(':visible')) stmData += '&parent_id=' + encodeURIComponent($('input[name="parent_id"]').val());

			if ($('.sq-single').is(':visible')) stmData += '&single=' + encodeURIComponent($('input[name="single"]').val());
			if ($('.sq-multi').is(':visible'))
			{
				$('input[name="option[]"]').each(function(){
					stmData += '&option[]=' + encodeURIComponent($(this).val());
				});
				$('input[name="points[]"]').each(function(){
					stmData += '&points[]=' + encodeURIComponent($(this).val());
				});
			}
			if ($('.sq-range').is(':visible'))
			{
				$('input[name="range_from[]"]').each(function(){
					stmData += '&range_from[]=' + encodeURIComponent($(this).val());
				});
				$('input[name="range_to[]"]').each(function(){
					stmData += '&range_to[]=' + encodeURIComponent($(this).val());
				});
				$('input[name="points[]"]').each(function(){
					stmData += '&points[]=' + encodeURIComponent($(this).val());
				});
			}
			
			stmData += '&sort_order=' + encodeURIComponent($('input[name="sort_order"]').val());
		}
		
		request({
			data: {
				act: app.page.name + '-save',
				id: app.page.id,
				data: (app.page.name === 'statements') ? stmData : $('.must-process').serialize()
			},
			success: function(r){
				app.hideLoader('.app');
				toastr(r.error, r.message);
				app.page.id = r.id;
				app.page.act = 'getItem';
				app.loadPage();
				
				if (r.tree !== 'undefined') global.tree = r.tree;
				if (r.districts !== 'undefined') global.districts = r.districts;
			}
		});
	},
	
	unbindPlugins: function(){
		for(name in CKEDITOR.instances)
		{
			CKEDITOR.instances[name].destroy(true);
		}
	},
	
	bindPlugins: function(){
		$('.editor').ckeditor();
		$('.hasTooltip').tooltip();
		$('.dp').datepicker({ startView: 2 });
		$('.blur').each(function(){
			var blurred = '';
			
			for (var i = 0; i < $(this).text().length; i++) { blurred += '•'; }
			
			$(this)
				.attr('data-value', $(this).text())
				.text(blurred);
		});

		if ($('#file-uploader').length > 0)
		{
			this.uploader = new qq.FileUploader({
				element: document.getElementById('file-uploader'),
				multiple: true,
				action: '../callback.php?act=general-upload',
				debug: false,
				uploadButtonText: "Upload image",
				disableDefaultDropzone: true,
				onComplete: function(id, filename, response){
					if (response.success === true)
					{
						$('input[name="image"]').val(response.path);
					}
					else
					{
						$('.qq-upload-failed-text').html(response.error);
					}
				}
			});
		}		

		if ($('#chart_user').length > 0)
		{
			var chartOptions = {
					//responsive: true,
					animation: false,
					scaleType: "date",
					useUtc: true,
					scaleShowVerticalLines: false,
					//tooltipTemplate: "<%=datasetLabel%>: <%=valueLabel%>",
					//multiTooltipTemplate: "<%=datasetLabel%>: <%=valueLabel%>",
					scaleDateFormat: "mmm d"
				};
				
			for (var i = 0; i < app.charts.length; i++)
			{
				app.charts[i].destroy();
			}
				
			app.charts[0] = new Chart(document.getElementById("chart_user").getContext("2d")).Scatter(app.processDatasets(dataUser), chartOptions);
		}
	},
	
	processDatasets: function($datasets){
		for (var i = 0; i < $datasets.length; i++)
		{
			for (var j = 0; j < $datasets[i].data.length; j++)
			{
				$datasets[i].data[j].x = new Date($datasets[i].data[j].x);
			}
		}
		return $datasets;
	}
	
};

String.prototype.strLimit = function(limit) {
	lg(limit);
	var lim = (limit !== undefined) ? limit : 128;
	if (this.length <= lim) return this;
	return this.substr(0, lim) + '...';
};

String.prototype.capitalize = function() {
	return this.charAt(0).toUpperCase() + this.slice(1);
};

String.prototype.formatDate = function() {
	var date = new Date(parseInt(this)  * 1000);
	return date.toDateString();
};

Number.prototype.formatMoney = function(decPlaces, thouSeparator, decSeparator) {
    var n = this,
    decPlaces = isNaN(decPlaces = Math.abs(decPlaces)) ? 2 : decPlaces,
    decSeparator = decSeparator === undefined ? "." : decSeparator,
    thouSeparator = thouSeparator === undefined ? "," : thouSeparator,
    sign = n < 0 ? "-" : "",
    i = parseInt(n = Math.abs(+n || 0).toFixed(decPlaces)) + "",
    j = (j = i.length) > 3 ? j % 3 : 0;
    return sign + (j ? i.substr(0, j) + thouSeparator : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thouSeparator) + (decPlaces ? decSeparator + Math.abs(n - i).toFixed(decPlaces).slice(2) : "");
};

Number.prototype.formatTime = function(s, m, h) {
	var seconds = this;
	var ret = "";

	if (typeof h === 'undefined') h = true;
	if (typeof m === 'undefined') m = true;
	if (typeof s === 'undefined') s = true;

	if (seconds > 3600)
	{
		if (h === true) ret += Math.floor(seconds / 3600) + ' hours ';
		seconds -= Math.floor(seconds / 3600) * 3600;
	}
	if (seconds > 60)
	{
		if (m === true) ret += Math.floor(seconds / 60) + ' minutes ';
		seconds -= Math.floor(seconds / 60) * 60;
	}
	if (s === true) ret += seconds + ' seconds';
	return ret;
};

function toastr(cls, text)
{
	var ico = '', index = $('.toastr').length + 1;
	
	switch (cls)
	{
		case 0: ico = 'ico-check'; break;
		case 1: ico = 'ico-close'; break;
		case 2: ico = 'ico-info-outline'; break;
	}
	
	$('body').append('<div class="toastr" data-index="' + index + '" data-type="' + cls + '"><span class="ico ' + ico + '"></span><div>' + text + '</div></div>');
	toastrHandler($('.toastr[data-index="' + index + '"]'));
}

function toastrHandler(obj)
{
	obj.animate(
		{ opacity: 1 },
		350,
		function(){
			setTimeout(function(){
				obj.animate(
					{ opacity: 0 },
					250,
					function(){
						obj.remove();
						$('.toastr').each(function(){
							var index = parseInt($(this).attr('data-index'), 10);
							$(this).attr('data-index', index - 1);
						});
					}
				);
			}, 3000);
		}
	);	
}

function pp(title, message, buttons)
{
	var
		static = (typeof buttons === 'undefined') ? '' : ' data-backdrop="static"',
		close  = (typeof buttons === 'undefined') ? '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' : '';

	//check for previous modal and adjust z-index;
	if ($('.modal-backdrop').length > 0)
	{
		$('.modal-backdrop').css('z-index', $('.modal-backdrop').css('z-index') - 10);
	}
	if ($('.modal').length > 0)
	{
		$('.modal').css('z-index', $('.modal').css('z-index') - 10);
	}

	$('body').append(
		'<div id="notify-modal" class="modal hide"' + static + '>' +
			'<div class="modal-header">' + close + '<h3></h3></div>' +
			'<div class="modal-body"></div>' +
			'<div class="modal-divider"></div>' +
			'<div class="modal-footer"></div>' +
		'</div>'
	);

	if (typeof buttons !== 'undefined')
	{
		for (var i = 0; i < buttons.length; i++)
		{
			var btn = buttons[i];
			$('#notify-modal > .modal-footer').append('<button class="btn btn-' + i + btn.cls + '">' + btn.label + '</button>');

			$('#notify-modal > .modal-footer .btn-' + i).click(btn.callback);
			$('#notify-modal > .modal-footer .btn').click(function(){ $('#notify-modal').modal('toggle'); });
		}
	}
	else
	{
		$('#notify-modal .modal-divider, #notify-modal .modal-footer').remove();
	}

    $('#notify-modal').on('hidden', function(){
		$(this).remove();

		//check for previous modal and reset z-index;
		if ($('.modal-backdrop').length > 0)
		{
			$('.modal-backdrop').css('z-index', parseInt($('.modal-backdrop').css('z-index'), 10) + 10);
		}
		if ($('.modal').length > 0)
		{
			$('.modal').css('z-index', parseInt($('.modal').css('z-index'), 10) + 10);
		}
	});
	$('#notify-modal > .modal-header > h3').html(title);
	$('#notify-modal > .modal-body').html(message);
	$('#notify-modal').modal('toggle');
}

function request(o)
{
	$.ajax({
		url: 'callback.php',
		data: o.data,
		type: 'post',
		dataType: (typeof o.dataType === 'undefined') ? 'json' : o.dataType,
		success: (typeof o.success === 'undefined') ? function(){} : o.success,
		error: function(a, b, c){
			lg(a); lg(b); lg(c);
		}
	});
}

function lg(o)
{
	if (console) console.log(o);
}

function randVal(min, max)
{
    return Math.round(Math.random() * (max - min) + min);
}

function saveKV(key, value)
{
	var date = new Date();
	date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));
	var expires = "; expires=" + date.toGMTString();
	document.cookie = key + "=" + value + expires+"; path=/";
}

function getKV(key)
{
	var nameEQ = key + "=";
	var ca = document.cookie.split(';');
	for (var i = 0; i < ca.length; i++)
	{
		var c = ca[i];
		while (c.charAt(0) === ' ') c = c.substring(1, c.length);
		if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
	}
	return null;
}

function removeKV(key)
{
	saveKV(key, "", -1);
}

var Base64 = {
	_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
	encode : function (input) {
		if (input === "") return "";
		if (input === null) return "";

		var output = "";
		var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		var i = 0;

		input = Base64._utf8_encode(input);

		while (i < input.length) {

			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);

			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;

			if (isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if (isNaN(chr3)) {
				enc4 = 64;
			}

			output = output +
			this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
			this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
		}
	    return output;
	},
	decode : function (input) {
		if (input === "") return "";
		if (input === null) return "";

		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;

		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

		while (i < input.length) {

			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));

			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;

			output = output + String.fromCharCode(chr1);

			if (enc3 !== 64) {
				output = output + String.fromCharCode(chr2);
			}
			if (enc4 !== 64) {
				output = output + String.fromCharCode(chr3);
			}

		}

		output = Base64._utf8_decode(output);

		return output;

	},
	_utf8_encode : function (string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";

		for (var n = 0; n < string.length; n++) {

			var c = string.charCodeAt(n);

			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}

		}
		return utftext;
	},
	_utf8_decode : function (utftext) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;

		while ( i < utftext.length ) {

			c = utftext.charCodeAt(i);

			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			}
			else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}

		}
		return string;
	}
};