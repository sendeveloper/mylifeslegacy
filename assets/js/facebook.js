/*
 * @Author: Slash Web Design
 * Facebook module
*/

function handleFacebook(success, failed){
	
	lg('handle facebook init');

	if (navigator.userAgent.match('CriOS'))
	{
	    window.open('https://www.facebook.com/dialog/oauth?client_id=264092377272374&redirect_uri='+ document.location.href +'&scope=email,public_profile', '', null);
	}
	
	FB.getLoginStatus(function(response){
		lg('getting login status from FB');
		lg(response);
		statusChangeCallback(response);
	}, true);
	
	function statusChangeCallback(response){
		lg('status callback fired');
		lg(response);
		
		if (response.error === 'access_denied') return false;
		
		if (response.status === 'connected')
		{
			FB.api("/me", "GET", {fields: 'first_name,last_name,id,email'}, function(r){
				if (r.email !== undefined && r.email !== '')
				{
					success(r);
				}
				else
				{
					FB.api("/" + r.id + "/permissions", "delete", function(response){});
					failed();
				}
			});
		}
		else
		{
			FB.login(statusChangeCallback, {scope: 'public_profile,email'});
		}
	}

}

window.fbAsyncInit = function(){
	FB.init({
	  appId      : '264092377272374',
	  cookie     : true,
	  xfbml      : false,
	  version    : 'v2.5'
	});
};

(function(d, s, id){
	 var js, fjs = d.getElementsByTagName(s)[0];
	 if (d.getElementById(id)) return;
	 js = d.createElement(s); js.id = id;
	 js.src = "//connect.facebook.net/es_LA/sdk.js";
	 fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));