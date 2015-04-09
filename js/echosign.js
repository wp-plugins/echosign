function show_upload_template() {
	document.getElementById('upload_echosign_template').style.display = '';
}

function hide_new_template() {
        document.getElementById('upload_echosign_template').style.display = 'none';
}

function deletetemporarly(id) {
	jQuery.ajax({
		type: 'POST',
		url: ajaxurl,
		async: false,
		data: {
			'action'       : 'deletetemplateastemporarly',
			'templateid' : id,
		},
		success:function(data)
		{
			//alert('Template trashed successfully!');
			window.location.reload();
		},
		error: function(errorThrown){
			console.log(errorThrown);
		}
	});
}

function deletepermanently(id) {
        jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                async: false,
                data: {
                        'action'       : 'deletetemplateaspermanently',
                        'templateid' : id,
                },
                success:function(data)
                {
                        //alert('Template deleted successfully!');
			window.location.reload();
                },
                error: function(errorThrown){
                        console.log(errorThrown);
                }
        });
}

function restoretemplate(id) {
        jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                async: false,
                data: {
                        'action'       : 'restoretemplates',
                        'templateid' : id,
                },
                success:function(data)
                {
                        //alert('Template restored successfully!');
			window.location.reload();
                },
                error: function(errorThrown){
                        console.log(errorThrown);
                }
        });
}
