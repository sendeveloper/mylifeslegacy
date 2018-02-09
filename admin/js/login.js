/*
 * @Author: Slash Web Design
 * Access JS 
*/
var animationSpeed = 500;

$(document).on('blur', 'input', function(){
	if ($(this).val() === '')
	{
		$(this).addClass('error');
		return false;
	}
	else
	{
		$(this).removeClass('error');
	}	
});

$(document).on('keyup', 'input', function(e){
	if (e.keyCode === 13) $('.btn-info').click();
});

$(document).on('click', '.btn-info', function(){
	var $ok = true;
	
	$('input').each(function(){
		if ($(this).hasClass('error') === true || $(this).val() === '') $ok = false;
	});
	
	if ($ok === false)
	{
		pp('Admin Panel Access', 'Please fill in all fields with valid values');
		return false;
	}
	
	$('.btn-info').html('<span class="spinner s20"></span>').prop('disabled', true).addClass('disabled');
	request({
		data: {
			act: 'auth-login',
			user: $('input[name="user"]').val(),
			password: $('input[name="password"]').val()
		},
		success: function(r){
			$('.btn-info').html('Sign in').prop('disabled', false).removeClass('disabled');
			if (r.error !== 0)
			{
				pp('Admin Panel Access', r.message);
				return false;
			}
			window.location.href = window.location.href.replace('/sign-in', '');
		}
	});
});

$(document).ready(function(){
	renderBoxes();
});

$(window).resize(function(){
	renderBoxes();
});

function renderBoxes()
{
	var 
		$topIn = ($(window).height() - $('.access-box').outerHeight()) / 2,
		$heightIn = 'auto';
			
	if ($topIn < 10)
	{
		$topIn = 10;
		$heightIn = $(window).height() - 40;
	}
	
	$('.access-box')
		.css({
			'top': $topIn,
			'left': ($(window).width() - $('.access-box').outerWidth()) / 2,
			'height': $heightIn
		})
		.animate({ opacity: 1 }, animationSpeed, 'easeOutQuad');
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