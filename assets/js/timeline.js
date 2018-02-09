/*
 * Timeline module
 * ©SWD
 */
var stop = false;
var app = {
	
	events: [],
	offset: 0.8,
	categoryId: 0,
	defaultId: 0,
	sortOrder: 1,
	deleteEvent: function(eventId){
		pp('Delete timeline event', 'Are you sure you want to delete this event from your timeline? All files attached to it will also be deleted and you will not be able to undo this.', [
			{
				label: 'Yes',
				cls: ' btn-success',
				callback: function(){
					request({
						data: {
							act: 'timeline-delete',
							event_id: eventId,
							current_length: (app.events.length-1),
							sort_order: app.sortOrder,
							category_id: app.categoryId
						},
						success: function(r){
							$.each(r.categories, function(index, value){
								var obj = $('.timeline-menu li[data-id="' + index + '"]');
								var obj2 = $('.timeline-categories li[data-id="' + index + '"]');
								if (obj.length)
								{
									obj.attr('data-count', value);
									obj.find('span').html(value);
								}
								if(obj2.length)
								{
									obj2.attr('data-count', value);
									obj2.find('span').html(value);
								}
							});
							toastr(0, r.message);
							app.events = r.events;
							app.render();
						}
					});
				}
			},
			{label: 'Cancel', cls: '', callback: function(){}}
		]);
	},
	
	editEvent: function(id){
		var e = app.getEvent(id);
		
		$('.event').attr('data-id', id);
		$('.event h2').html('Edit event on your timeline');
		$('.event input[name="name"]').val(e.name);
		$('.event select[name="category_id"]').val(e.category_id);
		$('.event textarea[name="description"]').val(e.description);
		$('.event input[name="date"]').val(e.date.toDate());
		$('.event input[name="date"]').attr('oldValue',e.date.toDate());
		$('.event input[name="files"]').val('');
		$('.qq-upload-list').html('');
		
		app.listFiles(e);
		
		$('.event').modal('show');
	},
	
	saveEvent: function(){
		var 
			eventId = $('.event').attr('data-id'),
			name = $('.event input[name="name"]').val(),
			description = $('.event textarea[name="description"]').val(),
			date = $('.event input[name="date"]').val(),
			categoryId = $('.event select[name="category_id"]').val(),
			files = $('.event input[name="files"]').val(),
			uploading = $('.qq-upload-button').attr('upload_status');
	
		if (name === '')
		{
			pp('Save timeline event', 'Please fill in the name field');
			return false;
		}
	
		if (description === '')
		{
			pp('Save timeline event', 'Please fill in the description field');
			return false;
		}
	
		if (date === '')
		{
			pp('Save timeline event', 'Please fill in the date field, if you are not sure then estimate.');
			return false;
		}
		if (uploading == "uploading")
		{
			pp('File is not done uploading…');
			return false;	
		}
		$.each($('.file-holder video'), function(key, obj){
			if (!obj.paused)
				obj.pause();
		})
		$('.btn-success.btn-save').addClass('disabled').html('<span class="spinner s20"></span>');
		request({
			data: {
				act: 'timeline-save',
				event_id: eventId,
				category_id: app.categoryId,
				current_length: (app.events.length+1),
				sort_order: app.sortOrder,
				data: {
					name: name,
					description: description,
					category_id: categoryId,
					date: date,
					files: files
				}
			},
			success: function(r){
				$('.btn-success.btn-save').removeClass('disabled').html('Sign in');
				$.each(r.categories, function(index, value){
					var obj = $('.timeline-menu li[data-id="' + index + '"]');
					var obj2 = $('.timeline-categories li[data-id="' + index + '"]');
					if (obj.length)
					{
						obj.attr('data-count', value);
						obj.find('span').html(value);
					}
					if(obj2.length)
					{
						obj2.attr('data-count', value);
						obj2.find('span').html(value);
					}
				});
				toastr(0, r.message);
				console.log(r.events);
				$('.event').modal('hide');
				app.events = r.events;
				app.render();
			}
		});
	},
	
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
			$('<p class="empty">You have no events in this category yet, click "+" in the bottom corner to add one.</p>').insertBefore($('#cd-timeline'));
			$('#cd-timeline').hide();
			return false;
		}
		
		var html = '';
		for (var i = 0; i < app.events.length; i++)
		{
			html += app.renderEvent(app.events[i]);
		}
		// if (app.sortOrder == 1)
		// {
		// 	for (var i = 0; i < app.events.length; i++)
		// 	{
		// 		html += app.renderEvent(app.events[i]);
		// 	}
		// }
		// else
		// {
		// 	for (var i = app.events.length - 1; i >=0 ; i--)
		// 	{
		// 		html += app.renderEvent(app.events[i]);
		// 	}
		// }
		
		$('#cd-timeline').html(html).show();
		
		var isMobile = false; //initiate as false
		// device detection
		if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent) 
	    || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0,4))) isMobile = true;
		if (isMobile == true)
		{
			$('.audio_container').css('overflow','hidden');
			$('.audio_container').css('width',($('.cd-timeline-content').first().width()) + "px");
			$('.cd-timeline-content audio').css('width', ($('.cd-timeline-content').first().width()) + "px");
			$('.cd-timeline-content audio').css('height', "40px");

			var time = 15;
            var scale = 1;

            var video_obj = null;

            var videos = document.getElementById('video');
            if (videos != null)
            {
            	document.getElementById('video').addEventListener('loadedmetadata', function() {
	                this.currentTime = time;
	                video_obj = this;

	            }, false);

	            document.getElementById('video').addEventListener('loadeddata', function() {
	                var video = document.getElementById('video');
	                var canvas = document.createElement("canvas");
	                canvas.width = video.videoWidth * scale;
	                canvas.height = $(this).height();
	                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

	                var img = document.createElement("img");
	                img.src = canvas.toDataURL();
	                $(this).attr('poster', img.src);
	                video_obj.currentTime = 0;

	            }, false);
            }
		}

		app.hideBlocks();
	},
	subrender: function(start_from){
		
		var html = '';
		for (var i = start_from; i < app.events.length; i++)
		{
			html += app.renderEvent(app.events[i]);
		}
		// if (app.sortOrder == 1)
		// {
		// 	for (var i = start_from; i < app.events.length; i++)
		// 	{
		// 		html += app.renderEvent(app.events[i]);
		// 	}
		// }
		// else
		// {
		// 	for (var i = app.events.length - 1 - start_from; i >=0 ; i--)
		// 	{
		// 		html += app.renderEvent(app.events[i]);
		// 	}
		// }
		
		$('#cd-timeline').append(html).show();
		
		var isMobile = false; //initiate as false
		// device detection
		if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent) 
	    || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0,4))) isMobile = true;
		if (isMobile == true)
		{
			$('.audio_container').css('overflow','hidden');
			$('.audio_container').css('width',($('.cd-timeline-content').first().width()) + "px");
			$('.cd-timeline-content audio').css('width', ($('.cd-timeline-content').first().width()) + "px");
			$('.cd-timeline-content audio').css('height', "40px");

			var time = 15;
            var scale = 1;

            var video_obj = null;

            document.getElementById('video').addEventListener('loadedmetadata', function() {
                this.currentTime = time;
                video_obj = this;

            }, false);

            document.getElementById('video').addEventListener('loadeddata', function() {
                var video = document.getElementById('video');
                var canvas = document.createElement("canvas");
                canvas.width = video.videoWidth * scale;
                canvas.height = $(this).height();
                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

                var img = document.createElement("img");
                img.src = canvas.toDataURL();
                $(this).attr('poster', img.src);
                video_obj.currentTime = 0;

            }, false);
		}

		app.hideBlocks();
	},
	renderEvent: function(e){
		var maxLength = 300;
		var description = (e.description !== '') ? '<p>' + e.description.parseNewLines() + '</p>' : '',
			content = app.renderContent(e.files);
		if (description.length > maxLength)
		{
			description = (e.description !== '') ? '<p class="cd-timeline-text">' + e.description.substring(0, maxLength).parseNewLines() + '...</p>' : '',
			description += "<p class='see_more'><span>See more</span></p>";
			description += (e.description !== '') ? '<p class="temp_hidden">' + e.description.parseNewLines() + '</p>' : '';
		}
		return  '<div class="cd-timeline-block">' +
					'<div class="cd-timeline-dot"></div>' +
					'<div class="cd-timeline-content">' +
						'<div class="dropdown">' + 
							'<span class="ico ico-keyboard-arrow-down dropdown-toggle" data-toggle="dropdown"></span>' +
							'<ul class="dropdown-menu pull-right">' +
								'<li data-id="' + e.event_id + '" data-action="edit">Edit</li>' + 
								'<li data-id="' + e.event_id + '" data-action="delete">Delete</li>' + 
							'</ul>' +
						'</div>' +
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

				case 'mp3':	audios += '<div class="audio_container"><audio src="' + f + '" controls></audio></div>'; break;

				case 'mov':
				case 'mp4': videos += '<video src="' + f + '" controls></video>'; break;
			}
		}
		
		if (images !== '') images = '<div class="images">' + images + '</div>';
		if (documents !== '') documents = '<div class="documents">' + documents + '</div>';
		
		return images + audios + videos + documents;
	},
	
	resetModal: function(){
		var today = new Date();
		
		$('.event').attr('data-id', '0');
		$('.event h2').html('Create a new event on your timeline');
		$('.event input[name="name"]').val('');
		$('.event select[name="category_id"]').val(app.categoryId);
		$('.event textarea[name="description"]').val('');
		$('.event input[name="date"]').val((today.getMonth() + 1) + '/' + today.getDate() + '/' + today.getFullYear());
		$('.event input[name="files"]').val('');
		$('.file-holder').html('');

		$('#file-uploader .qq-upload-button').removeAttr("disabled");
		$('.btn-record').removeAttr('disabled');
		$('.btn-record').html("Record");
		$('.btn-record').attr('video','record');
		$('.video-holder').html("");
		$('.qq-upload-list').html('');
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
	
	fetch: function(){
		request({
			data: {
				act: 'timeline-fetchEvents',
				category_id: app.categoryId,
				sort_order: app.sortOrder
			},
			success: function(r){
				$.each(r.categories, function(index, value){
					var obj = $('.timeline-menu li[data-id="' + index + '"]');
					var obj2 = $('.timeline-categories li[data-id="' + index + '"]');
					if (obj.length)
					{
						obj.attr('data-count', value);
						obj.find('span').html(value);
					}
					if(obj2.length)
					{
						obj2.attr('data-count', value);
						obj2.find('span').html(value);
					}
				});
				app.events = r.events;
				app.render();
			}
		});
	},
	
	init: function(){
		this.fetch();
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
$(document).on('scroll', window, function(event){
	if (stop == false)
	{
		var scrollPos = $(window).scrollTop() + $(window).height();
		var docHeight = $(document).height();
		var loadMin = 200;
		if (loadMin + scrollPos > docHeight)
		{
			// load data;
			stop = true;
			request({
				data: {
					act: 'timeline-fetchMoreEvents',
					category_id: app.categoryId,
					sort_order: app.sortOrder,
					app_length: app.events.length
				},
				success: function(r){
					var cur_length = app.events.length;
					$.each(r.events, function(index, value){
						app.events.push(value);
					});
					app.subrender(cur_length);

					stop = false;
				}
			});
		}
	}
	// console.log($(document).height() + "," + $(window).height() + "," + $(window).scrollTop());
});

var recordAudio, recordVideo;
var recordingTime = 0, limitTime = 180;	// 3 minutes
var timeInterval = 100;
// function PostBlob(audioBlob, videoBlob, fileName) {
function PostBlob(videoBlob, fileName) {
    var formData = new FormData();
    formData.append('act', 'timeline-record');
    formData.append('filename', fileName);
    // formData.append('audioblob', audioBlob);
    formData.append('videoblob', videoBlob, fileName+'.webm');
    pp('Save timeline event', 'File is uploading to the server. Please wait for a while.');
    $.ajax({
    	url: 'record-video.php',
        type: 'POST',
        data: formData,
        async: true,
        processData: false,
        cache: false,
		contentType: false,
        success: function(result){
        	// document.querySelector('.video-holder h5').innerHTML = json.message;
        	try{
        		var json = JSON.parse(result);
        		if (json.result.indexOf('success') != -1)
	        	{
	        		var preview = document.getElementById("preview_video"), arr;
	        		var preview_url = "uploads/temp/";
			        preview.src = preview_url + fileName + '.mp4';
			        preview.play();
		        	preview.muted = false;
		        	$(preview).attr('poster', '');
		        	$('.file-holder video').removeAttr('id');

		        	arr = ($('input[name="files"]').val() !== '') ? $('input[name="files"]').val().split(';') : [];
					arr.push(preview_url + fileName + '.mp4');
					$('input[name="files"]').val(arr.join(';'));
					$(preview).after('<input style="float:left; width:93px;" class="delete-uploaded-file" type="button" value="Delete" data-url="' + preview_url + fileName + '.mp4' + '" />');
					// console.log(arr.join(';'));
	        	}
	        	else
	        		$('.file-holder video').removeAttr('id');
	        	pp('Save timeline event', json.message);
	        	setTimeout(function(){
	        		$('#notify-modal').modal('toggle');
	        		$('.event .modal-body').scrollTop($('.file-holder').height() + 300);
	        	}, 5000);
        	}
        	catch(err){
        		pp('Save timeline event', "Server error!!");
        		setTimeout(function(){
	        		$('#notify-modal').modal('toggle');
	        		$('.event .modal-body').scrollTop($('.file-holder').height() + 300);
	        	}, 5000);
        		$('.file-holder video').removeAttr('id');	
        	}
		}
	});
}

$(document).on('click', '.btn-record', function(){
	if ($(this).attr('video') != 'stop')
	{
		$('.file-holder').append('<video id="preview_video" controls style="border: 1px solid rgb(15, 158, 238); max-width: 100%; vertical-align: top; width: 100%;"></video>');
		$(this).html("Stop");
		$(this).attr('video','stop');

		var preview = document.getElementById("preview_video");
		var isFirefox = !!navigator.mozGetUserMedia;
		
		if(navigator.getUserMedia==window.undefined){
			pp('Video recording not supported', 'Please use a browser that supports video recording.');
		}

		!window.stream && navigator.getUserMedia({
            audio: true,
            video: true
        }, function(stream) {
            window.stream = stream;
            onstream();
        }, function(error) {
            alert(JSON.stringify(error, null, '\t'));
        });

        window.stream && onstream();

        function onstream() {
            preview.src = window.URL.createObjectURL(stream);
            // preview.srcObject = stream;
            preview.play();
            preview.muted = true;
			
			recordVideo = RecordRTC(stream, {
				type:'video'
            });
			
			recordVideo.startRecording();
			
/*
            recordAudio = RecordRTC(stream, {
                // bufferSize: 16384,
                onAudioProcessStarted: function() {
                    if (!isFirefox) {
                        recordVideo.startRecording();
                        setTimeout(function(){
                        	$('.event .modal-body').scrollTop($('.file-holder').height() + 300);
                        }, 1000);
                    }
                }
            });*/
/* 
			recordAudio = RecordRTC(stream, {
				type: 'audio'
			});
			
			recordAudio.onStateChanged =function(state) {
					if(state=='recording'){
						recordVideo.startRecording();
					}
				};
			
			
			var videoOnlyStream = new MediaStream();
			videoOnlyStream.addTrack(stream.getVideoTracks()[0]);
            recordVideo = RecordRTC(videoOnlyStream, {
				type:'video'
				// ,
				// previewStream: function(pstream) {
					// preview.src = window.URL.createObjectURL(pstream);
					// preview.play();
					// preview.muted = true;
				// }
            });

            recordAudio.startRecording(); */
			/* 
			recordVideo.initRecorder(function() {
				recordAudio.initRecorder(function() {
					// Both recorders are ready to record things accurately
					recordAudio.startRecording();
					recordVideo.startRecording();
					// preview.src = recordVideo.toURL();
					// preview.play();
					// preview.muted = true;
					// setTimeout(function(){
						// $('.event .modal-body').scrollTop($('.file-holder').height() + 300);
					// }, 1000);
					setTimeout(function(){
						var internal = recordVideo.getInternalRecorder(); // SECOND STEP
						var arrayOfBlobs = internal.getArrayOfBlobs(); // THIRD STEP

						// FOURTH STEP
						var blob = new Blob(arrayOfBlobs, {
						type: 'video/webm'
						});

						preview.src = recordVideo.toURL(); // LAST STEP
						preview.muted = true;
						preview.play();
					}, 1000);
				});
			});
 */
            stop.disabled = false;
        }

        recordingTime = 0;
        $('#style1').removeAttr("disabled");
        $('#file-uploader .qq-upload-button').attr('disabled','disabled');
        setTimeout(calcTime, timeInterval);
	}else{
		$(this).html("Record");
		$(this).attr('video','record');
		$('#style1').attr("disabled", "disabled");
		$('#file-uploader .qq-upload-button').removeAttr("disabled");
		recordingTime = 0;

		var preview = document.getElementById("preview_video");
		var isFirefox = !!navigator.mozGetUserMedia;
		preview.src = '';
		// preview.removeAttribute('srcObject');
        preview.poster = 'assets/img/ajax-loader.gif';

        fileName = Math.round(Math.random() * 99999999) + 99999999;

        // if (!isFirefox) {
            // recordAudio.stopRecording(function(url) {
                // document.querySelector('.video-holder h5').innerHTML = 'Got audio-blob. Getting video-blob...';
                recordVideo.stopRecording(function(url2) {
					preview.src = recordVideo.toURL(); // LAST STEP
                    // document.querySelector('.video-holder h5').innerHTML = 'Uploading to server...';
                    // PostBlob(recordAudio.getBlob(), recordVideo.getBlob(), fileName);
                    PostBlob(recordVideo.getBlob(), fileName);
                });
            // });
        // }
	}
});
function calcTime(){
	if ($('#preview_video').length > 0 && $('.event').css('display') != 'none')
	{
		var obj = $('#preview_video').get(0);
		if (obj.paused === false)
		{
			recordingTime ++;
			obj.play();
		}
		else
			obj.pause();
		if ($(".btn-record").attr('video') == 'stop'){
			var i=Math.floor((recordingTime*timeInterval)/(60*1000))
			$(".btn-record").html("Stop ["+i+":"+("0"+Math.floor((recordingTime*timeInterval)/1000-i*60)).slice(-2)+"]");
		}
		if (recordingTime * timeInterval > limitTime * 1000)
		{
			$(".btn-record").click();
			alert("You already reached the time limit (" + limitTime + " seconds).");
		}
		else
			setTimeout(calcTime, timeInterval);
	}
}
$(document).on('click', '.see_more span', function(){
	var parent = $(this).parent().parent();
	parent.find('.cd-timeline-text').html(parent.find('.temp_hidden').html());
	$(this).parent().remove();
});
$(document).on('change','.sort_order', function(){
	var sort_order = $(this).val();
	app.sortOrder = sort_order;
	app.init();
});
$(document).on('click', '.timeline-categories li', function(){
	app.hideFilters();
	app.categoryId = $(this).attr('data-id');
	app.init();
});

$(document).on('click', '.timeline-menu li', function(){
	app.hideFilters();
	app.categoryId = $(this).attr('data-id');
	app.init();
});

$(document).on('click', '.btn-filter', function(e){
	e.stopPropagation();
	app.showFilters();
});

$(document).on('click', 'body', function(){
	app.hideFilters();
});

$(document).on('click', '.event .btn-delete-file', function(){
	var
		eventId = $(this).attr('data-id'),
		file = $(this).attr('data-file'),
		btn = $(this);
		
	pp('Delete files', 'Are you sure you want to delete this file? You will not be able to undo this.', [
		{
			label: 'Yes',
			cls: ' btn-success',
			callback: function(){
				request({
					data: {
						act: 'timeline-deleteFile',
						event_id: eventId,
						category_id: app.categoryId,
						sort_order: app.sortOrder,
						file: file
					},
					success: function(r){
						$.each(r.categories, function(index, value){
							var obj = $('.timeline-menu li[data-id="' + index + '"]');
							var obj2 = $('.timeline-categories li[data-id="' + index + '"]');
							if (obj.length)
							{
								obj.attr('data-count', value);
								obj.find('span').html(value);
							}
							if(obj2.length)
							{
								obj2.attr('data-count', value);
								obj2.find('span').html(value);
							}
						});
						toastr(0, r.message);
						btn.parent().remove();
						app.events = r.events;

						app.render();
					}
				});
			}
		},
		{label: 'Cancel', cls: '', callback: function(){}}
	]);
});

$(document).on('focus', '.event textarea', function(){
	$(this).css('height', '210px');
});

$(document).on('blur', '.event textarea', function(){
	$(this).css('height', '30px');
});

$(document).on('click', '.cd-timeline-content .dropdown-menu li', function(){
	switch ($(this).attr('data-action'))
	{
		case 'edit':
			app.editEvent($(this).attr('data-id'));
			break;
			
		case 'delete':
			app.deleteEvent($(this).attr('data-id'));
			break;
	}
});
$(document).on('click', '.event .btn-save', function(){
	app.saveEvent();
});

$(document).on('click', '.btn-add', function(){
	app.resetModal();
	$('.event').modal('show');
});

$(document).ready(function(){
	
	app.defaultId = $('.timeline-categories').attr('data-default');
	app.categoryId = $('.timeline-categories').attr('data-default');
	app.init();
	
	$('.app-page').css('min-height', $(window).height() - 61);
	$('.timeline-categories').css('max-height', $(window).height() - 62);

	var isMobile = false; //initiate as false
	// device detection
	if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent) 
    || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0,4))) isMobile = true;
	if (isMobile != true)
	{
		$('.btn-record').removeAttr('disabled');
		$('.btn-record').show();
	}
});

$(window).on('resize', function(){
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
function getAddedFileContent(file)
{
	var 
		index = 0,
		images = '',
		documents = '',
		audios = '',
		videos = '';
	var 
		f = file,
		e = f.getFileExtension().toLowerCase(),
		n = f.getFileName();
	switch (e)
	{
		case 'jpg':
		case 'jpeg':
		case 'png':
		case 'gif': images += '<div style="background-image: url(\'' + f + '\'); background-size: 100% 100%;" rel="slideshow" data-url="' + f + '" data-index="' + index + '"></div>'; index++; break;
		

		case 'doc':
		case 'docx':
		case 'pdf': documents += '<a href="' + f + '" target="_blank"><span class="ico ico-description"></span>' + n + '</a>'; break;

		case 'mp3':	audios += '<audio src="' + f + '" controls></audio>'; break;

		case 'mov':
		case 'mp4': videos += '<video src="' + f + '" controls></video>'; break;
	}
	
	if (images !== '') images = '<div class="images">' + images + '</div>';
	if (documents !== '') documents = '<div class="documents">' + documents + '</div>';
	return images + audios + videos + documents+'<input style="float:left; width:93px;" class="delete-uploaded-file" type="button" value="Delete" data-url="' + f + '" />';
}
var uploader = new qq.FileUploader({
	element: document.getElementById('file-uploader'),
	action: 'index_ajax.php?act=general-upload',
	debug: false,
	multiple: true,
	//allowedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'pdf', 'mp3', 'mp4'],
	uploadButtonText: "Upload files",
	disableDefaultDropzone: true,
	onSubmit: function(id, fName){
		$('.btn-record').attr('disabled','disabled');
		$('.video-holder').html("");
	},
	onCancel: function(id, fName){
		$('.btn-record').removeAttr('disabled');
	},
	onComplete: function(id, filename, response){
		$('.btn-record').removeAttr('disabled');
		if (response.success === true)
		{
			var arr = ($('input[name="files"]').val() !== '') ? $('input[name="files"]').val().split(';') : [];
			arr.push(response.path);
			$('input[name="files"]').val(arr.join(';'));
			$('.file-holder').append(getAddedFileContent(response.path));
		}
		else
		{
			$('.qq-upload-failed-text').html(response.error + ' : ' + response.status);
		}
	},
	onError: function(a, b, c){
		pp('Error', a + ' : ' + b + ' : ' + c);
	}
});



$(document).on('click', '.delete-uploaded-file', function(){
	if(confirm("Are you sure you wish to delete the item above?")){
		var url=$(this).data('url');
		var arr = ($('input[name="files"]').val() !== '') ? $('input[name="files"]').val().split(';') : [];
		var i=arr.indexOf(url);
		if(i>-1){
			arr.splice(i, 1);
			$('input[name="files"]').val(arr.join(';'));
		}
		$(this).prev().remove();
		$(this).remove();
	}
});