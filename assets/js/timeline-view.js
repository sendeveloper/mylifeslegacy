/*
 * ©SWD
 */

var app = {
	
	events: [],
	offset: 0.8,
	categoryId: 0,
	defaultId: 0,
	
	listFiles: function(e){
		var html = '', file = '';
		
		for (var i = 0; i < e.files.length; i++)
		{
			var 
				f = e.files[i],
				name = f.getFileName(),
				ext = f.getFileExtension();
		
			switch (ext)
			{
				case 'jpg':
				case 'jpeg':
				case 'png':
				case 'gif':
					file = '<span class="ico ico-image"></span>';
					break;
					
				case 'doc':
				case 'docx':
				case 'pdf':
					file = '<span class="ico ico-description"></span>';
					break;
				
				case 'mp3':
					file = '<span class="ico ico-headset"></span>';
					break;
				
				case 'mp4':
				case 'mov':
					file = '<span class="ico ico-movie"></span>';
					break;
			}
			html += '<div class="file"><div class="name">' + file + name + '</div><button class="btn btn-small btn-danger btn-delete-file" data-file="' + f + '" data-id="' + e.event_id + '"><span class="ico ico-close"></span></button></div>';
		}
		if (e.files.length === 0) html = 'No files have been uploaded yet';
		
		$('.file-holder').html(html);
	},
	
	getEvent: function(id){
		for (var i = 0; i < app.events.length; i++)
		{
			if (app.events[i].event_id === id) return app.events[i];
		}
		return null;
	},
	
	render: function(){
		$('.empty').remove();
		
		app.highlightCategory();
		
		if (app.events.length === 0)
		{
			$('<p class="empty">There are no events in this category, please select another</p>').insertBefore($('#cd-timeline'));
			$('#cd-timeline').hide();
			return false;
		}
		
		var html = '';
		
		for (var i = 0; i < app.events.length; i++)
		{
			html += app.renderEvent(app.events[i]);
		}
		
		$('#cd-timeline').html(html).show();
		
		app.hideBlocks();
	},
	
	renderEvent: function(e){
		var 
			description = (e.description !== '') ? '<p>' + e.description.parseNewLines() + '</p>' : '',
			content = app.renderContent(e.files);
		
		return  '<div class="cd-timeline-block">' +
					'<div class="cd-timeline-dot"></div>' +
					'<div class="cd-timeline-content">' +
						'<h2>' + e.name + '</h2>' +
						description +
						content +
						'<span class="cd-date">' + e.date.toDate() + '</span>' +
					'</div>' +
				'</div>';
	},
	
	renderContent: function(files){
		if (files.length === 0) return '';

		var 
			index = 0,
			images = '',
			documents = '',
			audios = '',
			videos = '';
	
		for (var i = 0; i < files.length; i++)
		{
			var 
				f = files[i],
				e = f.getFileExtension().toLowerCase(),
				n = f.getFileName();

			switch (e)
			{
				case 'jpg':
				case 'jpeg':
				case 'png':
				case 'gif': images += '<div style="background-image: url(\'' + f + '\');" rel="slideshow" data-url="' + f + '" data-index="' + index + '"></div>'; index++; break;
				

				case 'doc':
				case 'docx':
				case 'pdf': documents += '<a href="' + f + '" target="_blank"><span class="ico ico-description"></span>' + n + '</a>'; break;

				case 'mp3':	audios += '<audio src="' + f + '" controls></audio>'; break;

				case 'mov':
				case 'mp4': videos += '<video src="' + f + '" controls></video>'; break;
			}
		}
		
		if (images !== '') images = '<div class="images">' + images + '</div>';
		if (documents !== '') documents = '<div class="documents">' + documents + '</div>';
		
		return images + audios + videos + documents;
	},
	
	hideBlocks: function(){
		$('.cd-timeline-block').each(function(){
			( $(this).offset().top > $(window).scrollTop() + $(window).height() * app.offset ) && $(this).find('.cd-timeline-dot, .cd-timeline-content').addClass('is-hidden');
		});
	},
	
	showBlocks: function(){
		$('.cd-timeline-block').each(function(){
			( $(this).offset().top <= $(window).scrollTop() + $(window).height() * app.offset && $(this).find('.cd-timeline-dot').hasClass('is-hidden') ) && $(this).find('.cd-timeline-dot, .cd-timeline-content').removeClass('is-hidden').addClass('bounce-in');
		});
	},
	
	fetch: function(id){
		request({
			data: {
				act: 'TimelineView-fetchEvents',
				id: id,
				category_id: app.categoryId
			},
			success: function(r){
				app.events = r.events;
				app.render();
			}
		});
	},
	
	init: function(id){
		this.fetch(id);
	},
	
	showFilters: function(){
		if ($('.timeline-overlay').length === 0)
		{
			$('<div class="timeline-overlay"><div>').appendTo($('body'));
			$('.timeline-overlay').css('height', $(document).height());
		}
		$('.timeline-categories').show();
	},
	
	hideFilters: function(){
		$('.timeline-overlay').remove();
		$('.timeline-categories').hide();
	},
	
	highlightCategory: function(){
		$('.timeline-menu li').each(function(){
			$(this).removeClass('active');
		});
		
		$('.timeline-menu li[data-id="' + app.categoryId + '"]').addClass('active');
	}
};

$(document).on('click', '.protected .btn-success', function(){
	var password = $('.protected input[name="password"]').val();
	
	if (password === '')
	{
		pp('Error', 'Please enter password');
		return false;
	}
	
	request({
		data: {
			act: 'timelineview-access',
			id: profileId,
			password: password
		},
		success: function(r){
			if (r.status)
			{
				app.categoryId = $('.protected').data('id');
				app.init(profileId);
				$('.protected').modal('hide');
				return false;
			}
			
			pp('Error', r.message);
		}
	});
});

$(document).on('click', '.timeline-categories li', function(){
	app.hideFilters();
	
	if ($(this).data('protected') === 0)
	{
		app.categoryId = $(this).data('id');
		app.init(profileId);
		return false;
	}
	
	$('.protected').data('id', $(this).data('id'));
	$('.protected input[name="password"]').val('');
	$('.protected').modal('show');
});

$(document).on('click', '.timeline-menu li', function(){
	app.hideFilters();
	
	if ($(this).data('protected') === 0)
	{
		app.categoryId = $(this).data('id');
		app.init(profileId);
		return false;
	}
	
	$('.protected').data('id', $(this).data('id'));
	$('.protected input[name="password"]').val('');
	$('.protected').modal('show');
});

$(document).on('click', '.btn-filter', function(e){
	e.stopPropagation();
	app.showFilters();
});

$(document).on('click', 'body', function(){
	app.hideFilters();
});

$(document).ready(function(){
	
	app.init(profileId);
	
	$('.app-page').css('min-height', $(window).height() - 61);
	$('.timeline-categories').css('max-height', $(window).height() - 62);

});

$(window).on('scroll', function(){
	if (!window.requestAnimationFrame)
	{
		setTimeout(function(){ app.showBlocks(); }, 100);
	}
	else
	{
		window.requestAnimationFrame(function(){ app.showBlocks(); });
	}
});