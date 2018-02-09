$(document).ready(function(){
	Stripe.setPublishableKey(global['glob']["sp_key"]);
	handler = StripeCheckout.configure({
		key: global['glob']["sp_key"],
		image: 'assets/img/favicon.png',
		name: "My Life's Legacy",
		locale: 'auto'
	});
	function stripeResponseHandler(status, response) {
		// Check for an error:
		if (response.error) {
			reportError(response.error.message);
		} else { // No errors, submit the form:
			// var f = $("#checkout_form");
			// f.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
			// f.find('input[name="existing"]').val(gift.payload.existing);
			// var formData = f.serializeArray();
			var token = response['id'];
			var data = {
				card_num: $('#txtCardNo').val(),
				exp_date_year: $('#cmbExpYear').val(),
				exp_date_month: $('#cmbExpMonth').val(),
				card_code: $('#txtCVV').val(),
				fname: $('#fname').val(),
				lname: $('#lname').val(),
				username: $('#username').val(),
				email: $('#email').val(),
				password: $('#password').val(),
				person_id: 0,
				mname: '',
				maiden: '',
				token: token
			};
			var fullname = data.fname + ' ' + data.lname;
			request({
				data: {
					act: 'auth-backgroundCheck',
					username: data.username,
					fname: data.fname,
					lname: data.lname,
					promocode: ''
				},
				success: function(r){
					if (r.status === true)
					{
						generatePdf(fullname, data);
					}
					else
					{
						pp('Error', r.message);
						$('.btn-subscript').prop('disabled', false);
						$('.subprocess').hide();
					}
				}
			});
		}
	}
	function generatePdf(fullname, data){
		$.ajax({
			url: 'models/generate_pdf.php',
			data: {
				username: fullname
			},
			type: 'post',
			dataType: 'json',
			success: function(json){
				data['file_name'] = json.file_name;
				addSubscript(data);
			}
		});
	}
	function addSubscript(data){
		request({
			data: {
				act: 'auth-addsubscript',
				payload: data
			},
			success: function(r){
				if (r.status)
				{
					window.location.href = global.siteURL + 'account';
					// alert(r.message);
					// document.location.reload();
				}
				else{
					pp('Error', r.message);
					$('.btn-subscript').prop('disabled', false);
					$('.subprocess').hide();
				}
			}
		});
	}
	$(document).on('click', '.btn-subscript', function(e){
		e.preventDefault();
		if ($(this).hasClass('disabled')) return false;
		if ($('#subscription_form').valid())
		{
			$('.btn-subscript').attr("disabled", "disabled");
			$('.subprocess .modal-body').html(
				'<p class="text-center"><span class="spinner s40"></span></p>' +
				'<p class="text-center">We are processing your subscription request and setting up your account, please wait...</p>'
			);
			$('.subprocess').show();
			Stripe.card.createToken({
				number: $('#txtCardNo').val(),
				cvc: $('#txtCVV').val(),
				exp_month: $('#cmbExpMonth').val(),
				exp_year: $('#cmbExpYear').val()
			}, stripeResponseHandler);
		}
	});
	$("#subscription_form").validate({
		rules: {
			x_card_num: "cardNumFunction",
			x_exp_date_month: {required: true},
			x_exp_date_year: "cardExpFunction",
			x_card_code: "cardCVVFunction",
			chkPolicy: {required: true},
			fname: {required: true},
			lname: {required: true},
			username: {required: true},
			email: {required: true, email: true},
			password: {required: true},
		},
		messages: {
            "x_exp_date_month": {
                required: "The expiration month appears to be invalid."
            },
            "email": {
            	required: "Please enter a valid email address."
            }
		},
		errorPlacement: function(error, element){
			var other = 0;
			if ($(element).attr('id') == 'txtCardNo')
				$(element).tooltip({ title: 'Please enter a valid card number', placement: 'right', trigger: 'manual' });
			else if ($(element).attr('id') == 'txtCVV')
				$(element).tooltip({ title: 'The CVC number appears to be invalid.', placement: 'right', trigger: 'manual' });
			else if ($(element).attr('id') == 'cmbExpYear')
				$(element).tooltip({ title: 'The expiration year appears to be invalid.', placement: 'right', trigger: 'manual' });
			else
				$(element).tooltip({ title: $(error).text(), placement: 'right', trigger: 'manual' });
			$(element).tooltip('show');
		},
		success: function (label, element) {
			$(element).tooltip('hide'); 
		}
	});
	$.validator.addMethod("cardNumFunction", function(value) {
		return Stripe.card.validateCardNumber(value);
	}, "Please enter a valid card number"); 
	$.validator.addMethod("cardCVVFunction", function(value) {
		return Stripe.card.validateCVC(value);
	}, "The CVC number appears to be invalid.");
	$.validator.addMethod("cardExpFunction", function(value) {
		return Stripe.card.validateExpiry($('#cmbExpMonth').val(), value);
	}, "The expiration date appears to be invalid.");
});