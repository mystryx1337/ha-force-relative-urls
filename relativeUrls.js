jQuery( document ).ready(function( $ ) {
	//$( "form.search-form" ).attr('action', '/');
	
	var url = "https://intranet.hef-rof.de";
	
    $('a').each(function() {
		if(typeof $(this).attr('href') !== 'undefined'){
			$(this).attr('href', $(this).attr('href').replace(url,""));
		}
    });
	
    $('img').each(function() {
		if(typeof $(this).attr('src') !== 'undefined'){
			$(this).attr('src', $(this).attr('src').replace(url,""));
		}
    });
	
    $('link').each(function() {
		if(typeof $(this).attr('href') !== 'undefined'){
			$(this).attr('href', $(this).attr('href').replace(url,""));
		}
    });
	
    $('form').each(function() {
		if(typeof $(this).attr('action') !== 'undefined'){
			$(this).attr('action', $(this).attr('action').replace(url,""));
		}
    });
	
    $('source').each(function() {
		if(typeof $(this).attr('src') !== 'undefined'){
			$(this).attr('src', $(this).attr('src').replace(url,""));
		}
    });
});

/*jQuery( document ).ready(function( $ ) {
	setTimeout(function () {
		for (var i = 0; i < tinymce.editors.length; i++) {
            tinymce.editors[i].onChange.add(function (ed, e) {
				// Update HTML view textarea (that is the one used to send the data to server).
				let content = tinymce.activeEditor.getContent()
								.replace(/font-family.+?;/g, '')
								.replace(/direction.+?;/g, '')
								.replace(/font-size.+?;/g, '')
								.replace(/(<[^>]+) style="\s*?"/i, '');
				tinymce.activeEditor.setContent(content);
			});
        }
	}, 1000);
});*/