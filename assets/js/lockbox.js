/* 
 * LockBox module
 * ©SWD
 */

var app;

function Emails(){
	
	var self = this;
	var emails = [];
	
	this.delete = function(id){
		request({
			data: {
				act: 'lockbox-delete',
				email_id: id
			},
			success: function(r){
				self.emails = r.emails;
				self.render();

				toastr(0, r.message);
			}
		});
	},
	
	this.save = function(id, name, email){
		request({
			data: {
				act: 'lockbox-save',
				data: {
					email_id: id,
					name: name,
					email: email
				}
			},
			success: function(r){
				self.emails = r.emails;
				self.render();

				toastr(0, r.message);
				$('.lockbox').modal('hide');
			}
		});
	},
	
	this.render = function(){
		var html = '';
		
		this.emails.forEach(function(item){
			html += 
				'<div class="email">' + 
					item.name +
					'<p>' + item.email + '</p>' +
					'<div class="control">' + 
						'<button class="btn btn-success btn-edit" data-id="' + item.email_id + '"><span class="ico ico-create"></span></button>' +
						'<button class="btn btn-success btn-delete" data-id="' + item.email_id + '"><span class="ico ico-clear"></span></button>' +
					'</div>' +
				'</div>';
		});
		
		if (html === '') html = '<p class="empty">You have not added any email addresses yet</p>';
		
		$('.emails').html(html);
	},
	
	this.get = function(){
		request({
			data: {
				act: 'lockbox-get'
			},
			success: function(r){
				self.emails = r.emails;
				self.render();
			}
		});
	},
			
	this.getItem = function(id){
		var obj = false;
		
		this.emails.forEach(function(item){
			if (parseInt(item.email_id, 10) === id)
			{
				obj = item;
				return false;
			}
		});
		
		return obj;
	}
	
};

$(document).on('click', '.email .btn-edit', function(){
	var item = app.getItem($(this).data('id'));
	
	$('.lockbox').data('id', item.email_id);
	$('.lockbox input[name="name"]').val(item.name);
	$('.lockbox input[name="email"]').val(item.email);
	$('.lockbox .modal-body h2').html('Edit lock box access');
	$('.lockbox').modal('show');
});

$(document).on('click', '.email .btn-delete', function(){
	var id = $(this).data('id');
	
	pp('Delete email address', "Are you sure you want to remove this person's access from your lock box?", [
		{
			label: 'Delete',
			cls: ' btn-success',
			callback: function(){
				app.delete(id);
			}
		},
		{label: 'Cancel'}
	]);
});

$(document).on('click', '.lockbox .btn-success', function(){
	var 
		id = $('.lockbox').data('id') || 0,
		email = $('.lockbox input[name="email"]').val(),
		name = $('.lockbox input[name="name"]').val();
		
	if (email === '' || name === '')
	{
		pp('Error', 'Please fill in the name and email field');
		return false;
	}
	
	app.save(id, name, email);
});

$(document).on('click', '.btn-create', function(){
	$('.lockbox').data('id', 0);
	$('.lockbox input[name="name"]').val('');
	$('.lockbox input[name="email"]').val('');
	$('.lockbox .modal-body h2').html('Add a new person to your lock box');
	$('.lockbox').modal('show');
});

$(document).ready(function(){
	app = new Emails();
	app.get();
});