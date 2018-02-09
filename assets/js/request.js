/* 
 * ©SWD
 */

var uploader = new qq.FileUploader({
	element: document.getElementById('file-uploader'),
	action: 'index_ajax.php?act=general-upload',
	debug: false,
	multiple: false,
	//allowedExtensions: ['jpg', 'jpeg'],
	uploadButtonText: "Upload death certificate",
	disableDefaultDropzone: true,
	onComplete: function(id, filename, response){
		if (response.success === true)
		{
			$('#file').val(response.path);
			$('input[name="file"]').val(response.path);
		}
		else
		{
			$('.qq-upload-failed-text').html(response.error);
		}
	}
});

$(".request").validate({
	ignore: ':hidden:not("#file")',
	rules: {
		'yourname': {required: true},
		'youremail': {required: true},
		'yourrelation': {required: true},

		'files': { required: true },
		'firstname': {required: true},
		'lastname': {required: true},
		'dob': { required: true },
		'dod': { required: true },
		'username': { required: true },
		'motherfirst': { required: true },
		'motherlast': { required: true },
		'fatherfirst': { required: true },
		'fatherlast': { required: true },
	},
	errorPlacement: function(error, element){
		if (element[0].id === 'file')
		{
			element = $('.qq-upload-button');
		}
		$(element).tooltip({ title: $(error).text(), placement: 'right', trigger: 'manual' });
		$(element).tooltip('show');
	},
	success: function (label, element) { $(element).tooltip('hide'); }
});