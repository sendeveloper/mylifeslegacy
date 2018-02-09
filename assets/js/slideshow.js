/*
 * @Author: Slash Web Design
 * Slideshow/Image gallery module
*/

var 
	images = [],
	isAnimating = false,
	isRotating = false,
	slideshow = {
		
		current: 0,
		img: null,
		
		init: function(obj){

			var 
				parent = obj.parent(),
				index = parseInt(obj.attr('data-index'), 10),
				rotate = (global.glob.pag === 'timeline') ? '<span class="ico ico-rotate-left rotate" data-angle="90"></span><span class="ico ico-rotate-right rotate" data-angle="-90"></span>' : '';


			images = [];
			parent.find('div[rel="slideshow"]').each(function(){
				var index = parseInt($(this).attr('data-index'), 10);
				images[index] = {
					src: $(this).attr('data-url'),
					obj: $(this)
				};
			});
			
			$('body').append(
				'<div class="slideshow-holder">' +
					'<div class="overlay"></div>' +
					rotate +
					'<span class="ico ico-close"></span>' +
					'<span class="ico ico-keyboard-arrow-left"></span>' +
					'<span class="ico ico-keyboard-arrow-right"></span>' +
					'<img src="" onload="slideshow.loaded()" />' +
				'</div>'
			);
		
			this.img = $('.slideshow-holder img');
			this.img.css({
				'max-height': $(window).height() - 50,
				'max-width': $(window).width() - 50
			});
		
			this.loadImage(index);
			this.current = index;
		},
		
		loaded: function(){
			isAnimating = true;
			this.img
				.css('margin-top', ($(window).height() - $('.slideshow-holder img').height()) / 2)
				.animate({ opacity: 1 }, 250, function(){
					isAnimating = false;
				});
		},
		
		loadImage: function(index){
			if (this.img.attr('src') !== '')
			{
				isAnimating = true;
				this.img.animate({ opacity: 0 }, 250, function(){
					isAnimating = false;
					slideshow.changeSource(index);
				});
				return false;
			}
			this.changeSource(index);
		},
		
		changeSource: function(index){
			this.img.attr('src', images[index].src);
			
			$('.slideshow-holder .ico-keyboard-arrow-right').show();
			$('.slideshow-holder .ico-keyboard-arrow-left').show();
			if (index === images.length - 1) $('.slideshow-holder .ico-keyboard-arrow-right').hide();
			if (index === 0) $('.slideshow-holder .ico-keyboard-arrow-left').hide();
		},
		
		next: function(){
			this.current++;
			this.loadImage(this.current);
		},
		
		prev: function(){
			this.current--;
			this.loadImage(this.current);
		},

		close: function(){
			$('.slideshow-holder').remove();
			$('.modal-backdrop').remove();
		}
		
	};


$(document).on('click', '.slideshow-holder .ico-keyboard-arrow-left', function(){
	if (isAnimating === false) slideshow.prev();
});

$(document).on('click', '.slideshow-holder .ico-keyboard-arrow-right', function(){
	if (isAnimating === false) slideshow.next();
});

$(document).on('click', '.slideshow-holder .ico-close', function(){
	slideshow.close();
});

$(document).on('click', '.slideshow-holder .rotate', function(){
	var 
		angle = $(this).data('angle'),
		source = slideshow.img.attr('src');
	
	if (isRotating === false)
	{
		isRotating = true;
		slideshow.img.attr('src', 'assets/img/slideshow-loader.gif');

		request({
			data: {
				act: 'timeline-rotate',
				source: source,
				angle: angle
			},
			success: function(r){
				if (r.status === true)
				{
					slideshow.img.attr('src', r.source);
					images[slideshow.current].src = r.source;
				}
				isRotating = false;
			}
		});
	}
});

$(document).on('click', 'div[rel="slideshow"]', function(){
	slideshow.init($(this));
});