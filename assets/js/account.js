/* 
 * Account module
 * ©SWD
 */

var uploader = new qq.FileUploader({
	element: document.getElementById('file-uploader'),
	action: 'index_ajax.php?act=general-uploadprofile',
	debug: false,
	multiple: false,
	allowedExtensions: ['jpg', 'jpeg', 'png'],
	uploadButtonText: "Upload profile picture",
	disableDefaultDropzone: true,
	onComplete: function(id, filename, response){
		if (response.success === true)
		{
			var d = new Date();
			$('input[name="data[image]"]').val(response.path);
			$('img.profile.h100').attr('src', response.path+"?"+d.getTime());
			$('img.profile.h30').attr('src', response.path+"?"+d.getTime());
		}
		else
		{
			$('.qq-upload-failed-text').html(response.error);
		}
	}
});
$(".account").validate({
	rules: {
		'data[fname]': { required: true },
		'data[mname]': { required: true },
		'data[lname]': { required: true },
		'data[username]': { required: true },
		// 'data[username]': "validateUsername",
		'data[city]': { required: true },
		'data[dob]': { required: true },
		'data[pob]': { required: true },
		'data[gender]': { required: true },
		'data[email]': { required: true, email: true },
		'data[password]': { required: true }
	},
	errorPlacement: function(error, element){
		// var name = $(element).attr('name');
		// if (name.indexOf('username') != -1 && $(element).val() != "")
		// {
		// 	$(element).tooltip({ title: "Same username is already existed", placement: 'right', trigger: 'manual' });
		// }
		// else{
		// 	$(element).tooltip({ title: $(error).text(), placement: 'right', trigger: 'manual' });
		// }
		$(element).tooltip({ title: $(error).text(), placement: 'right', trigger: 'manual' });
		$(element).tooltip('show');
	},
	success: function (label, element) {
		$(element).tooltip('hide'); 
	}
});
$(document).on('click', '.btn-cancel', function(e){
	e.preventDefault();
	if(confirm("Are you going to cancel this subscript account?"))
	{
		e.preventDefault();
		if ($(this).hasClass('disabled')) return false;
		$(this).attr("disabled", "disabled");
		$('.existing .modal-body').html(
			'<p class="text-center"><span class="spinner s40"></span></p>' +
			'<p class="text-center">We are cancelling your subscription request. Please wait...</p>'
		);
		$('.existing').show();
		request({
			data: {
				act: 'auth-cancelscript',
			},
			success: function(r){
				if (r.status)
				{
					window.location.href = global.siteURL + 'sign-out';
					// alert(r.message);
					// document.location.reload();
				}
				else{
					pp('Error', r.message);
					$('.btn-cancel').attr("disabled", false);
					$('.existing').hide();
				}
			}
		});
	}
});
// $.validator.addMethod("validateUsername", function(value) {
// 	var val = $('input[name="data[username]"]').val();
// 	if (val != "")
// 	{
// 		request({
// 			data: {
// 				act: 'Account-invite',
// 				username: val,
// 			},
// 			success: function(r){
// 				console.log(r);
// 				if (r.exist == true)
// 				{
// 					$('input[name="data[username]"]').tooltip({ title: "Same username is already existed", placement: 'right', trigger: 'manual' });
// 					$('input[name="data[username]"]').tooltip('show'); 
// 					$('input[name="data[username]"]').parent().addClass('error');
// 				}
// 			}
// 		});
// 		return true;
// 	}else{
// 		return false;
// 	}
// }, "");