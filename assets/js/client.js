/*
 * @Author: Slash Web Design
 * Login module
*/
var handler = null;

$(document).ready(function(){
	Stripe.setPublishableKey(global['glob']["sp_key"]);
	handler = StripeCheckout.configure({
		key: global['glob']["sp_key"],
		// key: 'pk_test_zkdfjTyjTFBzDOkiUNuF2sIp',
		//key: '',
		image: 'assets/img/favicon.png',
		name: "My Life's Legacy",
		locale: 'auto'
	});
});

var client = {
	
	redirect: '',
	payload: null,
	
	pay: function(payload){
		var body = $('.signup .modal-body').clone();
		if (payload.promo == 0)
		{
			if (payload.promocode == '')
			{
				handler.open({
					description: 'One time membership fee',
					amount: parseFloat(global.glob.fee) * 100,
					email: payload.email,
					currency: 'USD',
					token: function(token){
						var fullname = payload.fname + ' ' + payload.lname;
						$.ajax({
							url: 'models/generate_pdf.php',
							data: {
								username: fullname
							},
							type: 'post',
							dataType: 'json',
							success: function(json){
								payload["file_name"] = json.file_name;
								request({
									data: {
										act: 'auth-charge',
										amount: parseFloat(global.glob.fee) * 100,
										token: token.id,
										payload: payload
									},
									success: function(r){
										if (r.status === false)
										{
											$('.signup').append(body);
											pp('Sign up', r.message);
											return false;
										}
										window.location.href = (client.redirect !== '') ? Base64.decode(client.redirect) : global.siteURL + 'account';
									}
								});
							},
							error: function(a, b, c){
								lg(a); lg(b); lg(c);
							}
						});
						$('.signup .modal-body').html(
							'<p class="text-center"><span class="spinner s40"></span></p>' +
							'<p class="text-center">We are processing your payment and setting up your account, please wait...</p>'
						);
					}
				});
			}
			else
			{
				pp('Error', 'You can\'t use the current promocode');
			}
		}
		else
		{
			request({
				data: {
					act: 'auth-bypromo',
					payload: payload
				},
				success: function(r){
					if (r.status === false)
					{
						$('.signup').append(body);
						pp('Sign up', r.message);
						return false;
					}

					window.location.href = (client.redirect !== '') ? Base64.decode(client.redirect) : global.siteURL + 'account';
				}
			});
			$('.signup .modal-body').html(
				'<p class="text-center"><span class="spinner s40"></span></p>' +
				'<p class="text-center">We are using the promocode to set up your account, please wait...</p>'
			);
		}
	},
	
	initSignIn: function(r){
		$('.access.signup, #notify-modal').modal('hide');
		$('.access.signin').modal('show');
		
		if (r !== undefined) client.redirect = r;
	},
	
	initSignUp: function(){
		$('.access.signin, #notify-modal').modal('hide');
		$('.access.signup').modal('show');
	},

    initvideoArea: function(){
		$('.videoarea.videoarea, #notify-modal').modal('hide');
		$('.videoarea.videoarea').modal('show');
	},

	signIn: function(type, username, password){
		$('.access.signin .btn-success').addClass('disabled').html('<span class="spinner s20"></span>');
		request({
			data: {
				act: 'auth-signIn',
				type: type,
				username: username,
				password: password
			},
			success: function(r){
				$('.access.signin .btn-success').removeClass('disabled').html('Sign in');
				if (r.status === false)
				{
					pp('Sign in', r.message);
					return false;
				}

				window.location.href = (client.redirect !== '') ? Base64.decode(client.redirect) : global.siteURL + 'timeline';
			}
		});
	},
	
	signUp: function(type, fname, lname, mname, father, maiden, username, email, password, promocode){
		request({
			data: {
				act: 'auth-backgroundCheck',
				username: username,
				fname: fname,
				lname: lname,
				promocode: promocode
				// mname: mname,
				// father: father,
				// maiden: maiden
			},
			success: function(r){
				if (r.status === true)
				{
					if (r.exists !== false)
					{
						//check for previous modal and adjust z-index;
						if ($('.modal-backdrop').length > 0)
						{
							$('.modal-backdrop').css('z-index', $('.modal-backdrop').css('z-index') - 10);
						}
						if ($('.modal').length > 0)
						{
							$('.modal:not(.existing)').css('z-index', $('.modal').css('z-index') - 10);
						}
						
						client.payload = {
							'type': type,
							'fname': fname,
							'lname': lname,
							'mname': mname,
							'father': father,
							'maiden': maiden,
							'username': username,
							'email': email,
							'password': password,
							'promo': r.promo,
							'promocode': promocode,
							'person_id': 0
						};
						client.buildOptions(r.exists);
						$('.existing').modal('show');
					}
					else
					{
						client.pay({
							'type': type,
							'fname': fname,
							'lname': lname,
							'mname': mname,
							'father': father,
							'maiden': maiden,
							'username': username,
							'email': email,
							'password': password,
							'promo': r.promo,
							'promocode': promocode,
							'person_id': 0
						});
				   }
				}
				else
				{
					pp('Error', r.message);
				}
			}
		});
	},
	
	relationship: function(r, d){
		return (d === '') ? r : d;
	},
	
	buildOptions: function(items){
		var html = '<h3>We found someone with the same name as yours</h3><p>If this is you then please click on the name</p><ul>';

		for (var i = 0; i < items.length; i++)
		{
			var p = items[i], rel = '', dob = '', pob = '';

			p.name = (p.mname !== '') ? p.fname + ' ' + p.mname + ' ' + p.lname : p.fname + ' ' + p.lname;
			dob = (p.dob !== '') ? '<div>Date of birth: ' + p.dob + '</div>' : '';
			pob = (p.pob !== '') ? '<div>Place of birth: ' + p.pob + '</div>' : '';

			for (var j = 0; j < p.relatives.length; j++)
			{
				if (rel !== '') rel += ', ';
				rel += '<span>' +  p.relatives[j].fname + ' ' + p.relatives[j].lname + ' (' + client.relationship(p.relatives[j].r, p.relatives[j].description) + ')</span>';
			}

			html += 
				'<li data-id="' + p.person_id + '">' + 
					'<div class="name">' + p.name + dob + pob + '</div>' +
					'<div class="relatives">Relatives: ' + rel + '</div>' +
				'</li>';
		}
		
		html += '<li data-id="0"><div class="name">It\'s not me</div></li>'
		html += '</ul>';
		
		$('.existing .modal-body').html(html);
	}
};

$(document).on('click', '.existing ul li', function(){
	client.payload.person_id = $(this).attr('data-id');
	client.pay(client.payload);
	$('.existing').modal('hide');
});

$(document).on('click', '.btn-email', function(){
	$('.access.signup .step1').hide();
	$('.access.signup .step2').show();
	client.resize();
});

$(document).on('click', '.btn-facebook', function(e){
	e.preventDefault();
	
	if ($('.access.signin').is(':visible'))
	{
		// sign in
		handleFacebook(
			function(response){
				lg(response);
				client.signIn('social', response.email, '');
			},
			function(){
				pp('Sign in with Facebook', 'You chose not to share your email address, without it we are not able to sign you in.');
			}
		);
	}
	else 
	{
		// sign up
		handleFacebook(
			function(response){
				lg(response);
				client.signUp('social',	response.first_name, response.last_name, '', '', '', response.email, response.email, '');
			},
			function(){
				pp('Register with Facebook', 'You chose not to share your email address, without it we are not able to complete your registration.');
			}
		);
	}
});
$(document).on('change', 'input[name="have_code"]', function(e){
	if (this.checked) {
        $('.signup input[name="promocode"]').prop('disabled', false);
    } else {
        $('.signup input[name="promocode"]').attr("disabled", "disabled");
    }
});
$(document).on('keyup', '.signin input', function(e){
	if (e.keyCode === 13)
	{
		if ($('.signin form').valid())
		{
			client.signIn('form', $('.signin input[name="username"]').val(), $('.signin input[name="password"]').val());
		}
	}
});

$(document).on('keyup', '.signup input', function(e){
	if (e.keyCode === 13)
	{
		if ($('.signup form').valid())
		{
			client.signUp('form', $('.signup input[name="fname"]').val(), $('.signup input[name="lname"]').val(), $('.signup input[name="username"]').val(), $('.signup input[name="email"]').val(), $('.signup input[name="password"]').val());
			// client.signUp('form', '', '', $('.signup input[name="username"]').val(), $('.signup input[name="email"]').val(), $('.signup input[name="password"]').val());
		}
	}
});

$(document).on('click', '.signin .btn-success', function(e){
	e.preventDefault();
	
	if ($(this).hasClass('disabled')) return false;
	
	if ($('.signin form').valid())
	{
		client.signIn('form', $('.signin input[name="username"]').val(), $('.signin input[name="password"]').val());
	}
});

$(document).on('click', '.signup .btn-success', function(e){
	e.preventDefault();
	
	if ($(this).hasClass('disabled')) return false;
	
	if ($('.signup form').valid())
	{
		var promocode = "";
		if ($('input[name="have_code"]').is(':checked'))
			promocode = $('.signup input[name="promocode"]').val();
		client.signUp(
			'form', 
			$('.signup input[name="fname"]').val(),
			$('.signup input[name="lname"]').val(),
			'', '', '',
			// $('.signup input[name="mname"]').val(),
			// $('.signup input[name="father"]').val(),
			// $('.signup input[name="maiden"]').val(),
			$('.signup input[name="username"]').val(),
			$('.signup input[name="email"]').val(),
			$('.signup input[name="password"]').val(),
			promocode
		);
	}
});

$(document).on('click', 'a[href="sign-up"]', function(e){
	e.preventDefault();
	client.initSignUp();
});

$(document).on('click', 'a[href="sign-in"]', function(e){
	e.preventDefault();
	client.initSignIn();
});

$(document).on('click', 'a[href="video-area"]', function(e){
	e.preventDefault();
	client.initvideoArea();
});

$(document).on('click', '.forgot .btn', function(){
	
	if ($(this).hasClass('disabled')) return false;
	
	if ($('.forgot input[name="pass-email"]').val() === '')
	{
		pp('Reset my Zamdot password', 'Please fill in your email address');
		return false;
	}
	
	$('.forgot .btn').html('<span class="spinner s20"></span>').addClass('disabled');
	request({
		data: {
			act: 'auth-forgot',
			email: $('.forgot input[name="pass-email"]').val()
		},
		success: function(r){
			$('.forgot .btn').html('Reset password').removeClass('disabled');
			$('.access.forgot').modal('toggle');
			pp('New password', r.message);
		}
	});
});

$(document).on('click', 'a[href="forgot"]', function(e){
	e.preventDefault();
	$('.access.signin').modal('hide');
	$('.access.forgot').modal('show');
});

validateForm('.signup form', {
	fname: {required: true},
	lname: {required: true},
	// mname: {required: true},
	// father: {required: true},
	// maiden: {required: true},
	username: {required: true},
	email: {required: true, email: true},
	password: {required: true},
	terms: {required: true}
}, 'bottom');

validateForm('.signin form', {
	username: {required: true},
	password: {required: true}
}, 'bottom');

$(window).on('popstate', function() {
	handler.close();
});