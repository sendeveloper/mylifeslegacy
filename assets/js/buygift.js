/* 
 * ©SWD
 */

var handler = null;
var amount_arr = [99.99, 189.99, 274.99, 359.99, 444.99, 529.99, 614.99, 699.99];
var gift = {
	redirect: '',
	payload: null,
	pay: function(payload){
		$('.btn-checkout').attr("disabled", "disabled");
		Stripe.card.createToken({
			number: payload.cardnum,
			cvc: payload.cardcode,
			exp_month: payload.exp_month,
			exp_year: payload.exp_year
		}, stripeResponseHandler);
	},
	buyGift: function(type, amount,promocode,firstname,lastname,country,zip,email,phone,
						cardnum,exp_month,exp_year,cardcode,hearfrom,address1,address2,city){
		gift.payload = {
			'type': type,
			'amount': amount,
			'promocode': promocode,
			'firstname': firstname,
			'lastname': lastname,
			'country': country,
			'zip': zip,
			'email': email,
			'phone': phone,
			'cardnum': cardnum,
			'exp_month': exp_month,
			'exp_year': exp_year,
			'cardcode': cardcode,
			'hearfrom': hearfrom,
			'address1': address1,
			'address2': address2,
			'city': city,
			'existing': 'no'
		};
		request({
			data: {
				act: 'buygift-checkpayment',
				email: email,
				promocode: promocode
			},
			success: function(r){
				if (r.results.result.indexOf('Success') != -1)
				{
					gift.pay(gift.payload);
				}
				else
				{
					// if (r.results.result.indexOf('email') != -1)
					// {
					// 	if(confirm('Are you going to use the current email and the information?'))
					// 	{
					// 		gift.payload["existing"] = 'yes';
					// 		gift.pay(gift.payload);
					// 		return true;
					// 	}
					// 	else
					// 	{
					// 		reportError(r.results.result);
					// 	}
					// }
					// else
					// 	reportError(r.results.result);
					reportError(r.results.result);
					if (r.results.result.indexOf('email') != -1)
						$('input[name="x_email"]').addClass('error');
					else
						$('input[name="promocode"]').addClass('error');
				}
			}
		});
	}
};
$(document).ready(function(){
	Stripe.setPublishableKey(global['glob']["sp_key"]);
	handler = StripeCheckout.configure({
		key: global['glob']["sp_key"],
		image: 'assets/img/favicon.png',
		name: "My Life's Legacy",
		locale: 'auto'
	});
	$(document).on('click', '.btn-checkout', function(e){
		e.preventDefault();
		$('#payment-errors').text('').removeClass('alert alert-error');
		if ($(this).hasClass('disabled')) return false;
		
		if ($('#checkout_form').valid())
		{
			gift.buyGift(
				'form', 
				$('#checkout_form select[name="amount"]').val(),
				$('#checkout_form input[name="promocode"]').val(),
				$('#checkout_form input[name="x_first_name"]').val(),
				$('#checkout_form input[name="x_last_name"]').val(),
				$('#checkout_form select[name="x_country"]').val(),
				$('#checkout_form input[name="x_zip"]').val(),
				$('#checkout_form input[name="x_email"]').val(),
				$('#checkout_form input[name="x_phone"]').val(),

				$('#checkout_form input[name="x_card_num"]').val(),
				$('#checkout_form select[name="x_exp_date_month"]').val(),
				$('#checkout_form select[name="x_exp_date_year"]').val(),
				$('#checkout_form input[name="x_card_code"]').val(),
				$('#checkout_form select[name="hear_from"]').val(),

				$('#checkout_form input[name="x_address"]').val(),
				$('#checkout_form input[name="x_address2"]').val(),
				$('#checkout_form input[name="x_city"]').val()
			);
		}
	});

	$("#checkout_form").validate({
		rules: {
			amount: {required: true},
			promocode: {required: true, minlength: 5},
			x_first_name: {required: true},
			x_last_name: {required: true},
			x_country: {required: true},
			x_zip: {required: true},
			x_email: {required: true, email: true},
			x_phone: "validatePhone",
			x_card_num: "cardNumFunction",
			x_exp_date_month: {required: true},
			x_exp_date_year: "cardExpFunction",
			x_card_code: "cardCVVFunction",
			hear_from: {required: true},
			chkPolicy: {required: true}
		},
		messages: {
            "x_exp_date_month": {
                required: "The expiration month appears to be invalid."
            },
            "promocode": {
            	minlength: "Minimum length should be 5."
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
			else if ($(element).attr('id') == 'x_phone')
				$(element).tooltip({ title: 'Invalid phone number.', placement: 'right', trigger: 'manual' });
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
	$.validator.addMethod("validatePhone", function(value) {
		var a = value;
	    var filter = /^((\+[1-9]{1,4}[ \-]*)|(\([0-9]{2,3}\)[ \-]*)|([0-9]{2,4})[ \-]*)*?[0-9]{3,4}?[ \-]*[0-9]{3,4}?$/;
	    if (filter.test(a)) {
	        return true;
	    }
	    else {
	        return false;
	    }
	}, "Invalid phone number.");
});
function stripeResponseHandler(status, response) {
	// Check for an error:
	if (response.error) {
		reportError(response.error.message);
	} else { // No errors, submit the form:
		var f = $("#checkout_form");
		var token = response['id'];
		f.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
		f.find('input[name="existing"]').val(gift.payload.existing);
		var formData = f.serializeArray();
		request({
			data: {
				act: 'buygift-dopayment',
				q: formData
			},
			success: function(r){
				if (r.results.msg.indexOf('processed') != -1)
				{
					document.location.href="home";
				}
				else
				{
					reportError(r.results.msg);
					document.getElementById('amount').selectedIndex = "0";
					document.getElementById('cmbExpMonth').selectedIndex = "0";
					document.getElementById('cmbExpYear').selectedIndex = "0";
					$('.Payment input[type="text"]').val('');
					$('input[name="promocode"]').val();
				}
			}
		});
	}
}
function reportError(msg) {
	// Show the error in the form:
	$('#payment-errors').text(msg).addClass('alert alert-error');
	// re-enable the submit button:
	$('.btn-checkout').prop('disabled', false);
	return false;
}