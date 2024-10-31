jQuery(document).ready(function($) {
	
	var albums = null;
	
	show_list();
	
	$('#pluginpicasaembed-auth').click(function(e) {
		$('#pluginpicasaembed-dialog-login-error').hide();
		show_dialog_login();
	});
	$('#pluginpicasaembed-refresh').click(function(e) {
		show_list();
	});
	$(':checkbox').click(function(e) {	
		var id = e.target.id;
		var elname = 'pluginpicasaembed-cb';
		
		if (id.substring(0, elname.length) == elname) {
			$(':checkbox').attr('checked', $(this).is(':checked'));		
		}
	});
	$('#pluginpicasaembed-configure').click(function(e) {
		var count = 0;
		var id = '';
		$('input:checkbox:checked').each(function(i) {
			if ($(this).attr('id') != 'pluginpicasaembed-cb1' 
			&& $(this).attr('id') != 'pluginpicasaembed-cb2') {
				id = $(this).val();
				count++;
			}
		});
		if (count < 1) {
			show_dialog_error(message_select_least);
		} else if (count > 1) {
			show_dialog_error(message_select_single);
		} else {
			var album = null;
			$.each(albums, function(i) {
				if (albums[i].id_album == id) {
					album = albums[i];
				}
			});
			$('#pluginpicasaembed-configure-album').text(album.title);
			$('#pluginpicasaembed-configure-width').val(album.width);
			$('#pluginpicasaembed-configure-height').val(album.height);
			$('#pluginpicasaembed-dialog-configure-error').hide();
			show_dialog_configure();
		}
	});
	$('#pluginpicasaembed-list tr').mouseenter(function(e) {
		$('pluginpicasaembed-thumbnail').hide();
		var id = $(this).attr('id').substring(6, $(this).attr('id').length);
		var album = null;
		$.each(albums, function(i) {
			album = albums[i];
			if (album.id_album == id) {
				$('#pluginpicasaembed-thumbnail-img').attr('src', album.thumbnail.url);
				$('#pluginpicasaembed-thumbnail-img').width(album.thumbnail.width);
				$('#pluginpicasaembed-thumbnail-img').height(album.thumbnail.height);
				$('#pluginpicasaembed-thumbnail').css({
																		left: e.pageX,
																		top: e.pageY
																		});
				$('#pluginpicasaembed-thumbnail-info').text(album.numphotos + ' photo(s)');
				$('#pluginpicasaembed-thumbnail').show();
				$('#pluginpicasaembed-thumbnail').css({
																opacity: 0.75
																});
			}
		});
	});
	$('#pluginpicasaembed-list tr').mousemove(function(e) {
		var id = $(this).attr('id').substring(6, $(this).attr('id').length);
		var album = null;
		$.each(albums, function(i) {
			album = albums[i];
			if (album.id_album == id) {
				$('#pluginpicasaembed-thumbnail-img').attr('src', album.thumbnail.url);
				$('#pluginpicasaembed-thumbnail-img').width(album.thumbnail.width);
				$('#pluginpicasaembed-thumbnail-img').height(album.thumbnail.height);
				
				$('#pluginpicasaembed-thumbnail').css({
																		left: e.pageX + 4 + 'px',
																		top: e.pageY + 4 + 'px'
																		});
				$('#pluginpicasaembed-thumbnail-info').text(album.numphotos + ' photo(s)');
				
			}
		});
	});
	$('#pluginpicasaembed-list tr').mouseout(function(e) {
		$('#pluginpicasaembed-thumbnail').fadeOut();
	});
	
	$('#pluginpicasaembed-dialog-error').dialog({
		closeOnEscape: false,
		position: ['center', 'middle'],
		modal: true,
		draggable: false,
		resizable: false, 
		closeText: 'X',
		autoOpen: false,
		buttons: {
			'ok': function() {
				$(this).dialog('close');
			}
		}
	});
	
	$('#pluginpicasaembed-dialog-notification').dialog({
		closeOnEscape: false,
		position: ['center', 'middle'],
		modal: true,
		draggable: false,
		resizable: false, 
		closeText: 'X',
		autoOpen: false,
		buttons: {
			'ok': function() {
				$(this).dialog('close');
			}
		}
	});
	$('#pluginpicasaembed-dialog-login').dialog({
		position: ['center', 'middle'],
		modal: true,
		draggable: false,
		resizable: false, 
		closeText: 'X',
		autoOpen: false,
		width: 350,
		height: 350,
		buttons: {
			'ok': function() {
				var countauth = 0;
				var result = null;
				while (count < 3) {
					result = auth();
					if (result[0] == true) {
						$(this).dialog('close');
						return;
					} else {
						$('#pluginpicasaembed-dialog-login-error-message').text('Identification invalide, essai: ' + (count + 1) + ' / 3');
						$('#pluginpicasaembed-dialog-login-error').show();
					}
					count++;
				}
				$('#pluginpicasaembed-dialog-login-error-message').text('Erreur Identification, veuillez ré-actualiser la page!');
				$('#pluginpicasaembed-dialog-login-error').show();
			},
			'cancel': function() {
				$(this).dialog('close');
			}
		}
	});
	$('#pluginpicasaembed-dialog-configure').dialog({
		position: ['center', 'middle'],
		modal: true,
		draggable: false,
		resizable: false, 
		closeText: 'X',
		autoOpen: false,
		width: 350,
		height: 350,
		buttons: {
			'ok': function() {
				var configuration = ({
					id_album: $('input:checkbox:checked').val(), 
					width: $('#pluginpicasaembed-configure-width').val(),
					height: $('#pluginpicasaembed-configure-height').val()
				});
				var result = configure(configuration);
				if (result[0] == false) {
					$('#pluginpicasaembed-dialog-configure-error-message').text(result[1].responseText);
					$('#pluginpicasaembed-dialog-configure-error').show();
				} else {
					$(this).dialog('close');
					show_list();
					show_dialog_notification(result[1].substring(0, result[1].length -1));
				}
			},
			'cancel': function() {
				$(this).dialog('close');
			}
		}
	});
	
	function show_list() {
		$('#pluginpicasaembed-list > *').remove();
		list();
		if (albums == null) {
			$('#pluginpicasaembed-list').append('<tr><td colspan="5">' + message_no_album + '</td></tr>');
		} else {
			var album = null;
			$.each(albums, function(i) {
				album = albums[i];
				$('#pluginpicasaembed-list').append('<tr id="album-' + album.id_album + '"></tr>');
				$('#album-' + album.id_album).append('<th class="check-column" scope="row"><input id="checkbox-' + album.id_album + '" class="administrator" type="checkbox" value="' + album.id_album + '" name="albums[]"/></th>');
				$('#album-' + album.id_album).append('<td class="title column-title">' + album.title + '</td>');
				$('#album-' + album.id_album).append('<td class="numphotos column-numphotos">' + album.numphotos + '</td>');
				$('#album-' + album.id_album).append('<td class="updated column-updated">' + album.updated + '</td>');
				$('#album-' + album.id_album).append('<td class="author column-author">' + album.author + '</td>');
				var label_access = album.access;
				if (album.access == 'private') {
					label_access = label_private;
				} else if (album.access == 'protected') {
					label_access = label_protected;
				} else if (album.access == 'public') {
					label_access = label_public;
				}
				$('#album-' + album.id_album).append('<td class="access column-access">' + label_access + '</td>');
				$('#album-' + album.id_album).append('<td class="shortcode column-shortcode">[pluginpicasaembed id="' + album.id_album + '"]</td>');
			});
		}
		
	}
	function show_dialog_configure() {
		$('#pluginpicasaembed-dialog-configure').parent().children().eq(2).children().eq(0).text(label_ok);
		$('#pluginpicasaembed-dialog-configure').parent().children().eq(2).children().eq(1).text(label_cancel);
		$('#pluginpicasaembed-dialog-configure').dialog('open');
	}
	function show_dialog_error(message) {
		$('#pluginpicasaembed-error-message').text(message);
		$('#pluginpicasaembed-dialog-error').parent().children().eq(2).children().eq(0).text(label_ok);
		$('#pluginpicasaembed-dialog-error').dialog('open');
	}
	function show_dialog_notification(message) {
		$('#pluginpicasaembed-notification-message').text(message);
		$('#pluginpicasaembed-dialog-notification').parent().children().eq(2).children().eq(0).text(label_ok);
		$('#pluginpicasaembed-dialog-notification').dialog('open');
	}
	function show_dialog_login() {
		$('#pluginpicasaembed-dialog-login').parent().children().eq(2).children().eq(0).text(label_ok);
		$('#pluginpicasaembed-dialog-login').parent().children().eq(2).children().eq(1).text(label_cancel);
		$('#pluginpicasaembed-dialog-login').dialog('open');
	}
	function configure(configuration) {
		var params = ({
			action: 'picasaembed_configure',
			id_album: configuration.id_album,
			width: configuration.width,
			height: configuration.height,
			cookie: encodeURIComponent(document.cookie)			
		});
		var result = exec_ajax(params);
		return result;
	}
	function auth() {
		var params = ({
			action: 'picasaembed_authenticate',
			email: $('#pluginpicasaembed-email').val(),
			password: $('#pluginpicasaembed-password').val(),
			cookie: encodeURIComponent(document.cookie)
		});
		var result = exec_ajax(params);
		return result;
	}
	function save() {
		var params = ({
			action: 'picasaembed_save',
			cookie: encodeURIComponent(document.cookie)
		});
		var result = exec_ajax(params);
		return result;
	}
	function list() {
		$('#pluginpicasaembed-dialog-login-error').hide();
		$('#pluginpicasaembed-list > *').remove();
		var params = ({
			action: 'picasaembed_list',
			cookie: encodeURIComponent(document.cookie)
		});
		var result = exec_ajax(params);
		if (result[0] == true) {
			albums = $.evalJSON(result[1].substring(0, result[1].length -1));
		} else {
			albums = null;
			if (result[1].responseText == 'AuthenticationRequired') {
				show_dialog_error(message_authentication_required);
			}
		}
	}
	function exec_ajax(params) {
		var result = [];
		$.ajax({
			async: false,
			type: 'POST',
			url: ajaxurl, 
			data: params,
			success: function(data) {
				result[0] = true;
				result[1] = data;
			},
			error: function(data) {
				result[0] = false;
				result[1] = data;
			}
		});
		return result;
	}
});