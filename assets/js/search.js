/* 
 * ©SWD
 */

$(document).ready(function(){
	
	var q = $('input[name="search"]').attr('data-search');
	
	if (q !== '')
	{
		$('input[name="search"]').attr('data-search', '').val(q);
		$('.btn-search').click();
	}
	
});

$(document).on('click', '.btn-search', function(){
	var q = [], longq;
	var name, fathername, maiden, city, day;
	name = $('input[name="name"]').val();
	fathername = $('input[name="fathername"]').val();
	maiden = $('input[name="maiden"]').val();
	city = $('input[name="currentcity"]').val();
	day = $('input[name="birthday"]').val();
	alias = $('input[name="alias"]').val();
	q.push({name: name});
	q.push({fathername: fathername});
	q.push({maiden: maiden});
	q.push({city: city});
	q.push({day: day});
	q.push({alias: alias});
	longq = '?name='+name+'&fathername='+fathername+'&maiden='+maiden+'&city='+city+'&day='+day+'&alias='+alias;
	if (name == '' && fathername == '' && maiden == '' && city == '' && day == '' && alias == '')
	{

	}
	else
	{
		window.history.pushState("object or string", "Search people - MLL", "search/"+longq);
		request({
			data: {
				act: 'general-search',
				q: q
			},
			success: function(r){
				render(r.results);
			}
		});
	}
});

$(document).on('keyup', 'input[name="name"]', function(e){
	if (e.keyCode === 13) $('input[name="fathername"]').focus();
});
$(document).on('keyup', 'input[name="fathername"]', function(e){
	if (e.keyCode === 13) $('input[name="maiden"]').focus();
});
$(document).on('keyup', 'input[name="maiden"]', function(e){
	if (e.keyCode === 13) $('input[name="birthday"]').focus();
});
$(document).on('keyup', 'input[name="birthday"]', function(e){
	if (e.keyCode === 13) $('input[name="currentcity"]').focus();
});
$(document).on('keyup', 'input[name="currentcity"]', function(e){
	if (e.keyCode === 13) $('input[name="alias"]').focus();
});
$(document).on('keyup', 'input[name="alias"]', function(e){
	if (e.keyCode === 13) $('.btn-search').click();
});
$(document).on('click', '.result', function(){
	/*
	if (global.user === null)
	{
		pp('View profile', '<a href="sign-up">Sign up</a> on My Life\'s Legacy to view profiles and create your own or <a href="sign-in">sign in</a> if you already have an account');
		return false;
	}
	*/
	window.location.href = $(this).attr('data-url');
});

function render(results){
	var html = '';
	
	for (var i = 0; i < results.length; i++)
	{
		var 
			r = results[i],
			icon = '',
			middle = (r.mname !== '') ? ' ' + r.mname + ' ' : ' ',
			nick = (r.nickname !== '') ? '<span>(' + r.nickname + ')</span>' : '',
			parents = (r.parents !== '') ? '<div class="parents">' + r.parents + '</div>' : '',
			city = (r.pob === '') ? 'Unknown' : r.pob;
	
		if (r.alive === '0')
		{
			icon = '<img class="deceased" src="assets/img/logo.black.png" title="Deceased" />';
		}
		else if (r.me === '1')
		{
			icon = '<img class="member" src="assets/img/logo.png" title="Member" />';
		}
		var d = new Date();
		html +=
			'<div class="result" data-url="' + r.url + '">' +
				'<div class="holder">' + 
					icon +
					'<img src="' + r.image + '?' + d.getTime() + '" class="profile h100" />' + 
					'<h2>' + r.fname + middle + r.lname + nick + '</h2>' +
					parents +
				'</div>' +
				'<p><span class="ico ico-location-on"></span>' + city + '</p>' +
			'</div>'
		;
	}
	
	if (html === '') html = '<p class="none">No results found</p>';
	
	$('.results').html(html);
}