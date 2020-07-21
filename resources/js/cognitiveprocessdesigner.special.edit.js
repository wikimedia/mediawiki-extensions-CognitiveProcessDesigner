window.onload = function() {
	window.importDiagram = false;
	document.getElementById('btn_bpmnIDLoad').addEventListener('click', function(){
		bpmnId = document.getElementById("bpmnID").value;
		annotate = [];
		loadSemanticForms('');
		window.importDiagram = true;
		$.ajax({
			url: mw.util.wikiScript('api'),
			data: {
				action: 'parse',
				page: bpmnId,
				prop: 'wikitext',
				format: 'json'
			},
			type: 'GET',
			success: function(data) {
				if (data && data.edit && data.edit.result === 'Success') {

				} else if (data && data.error) {
					alert( 'Error: API returned error code "' + data.error.code + '": ' + data.error.info );

				} else {
					data = data.parse.wikitext["*"];

					var string = data.match(/mw-collapsible-content">[\S\s]*<\/div><\/div>/g);
					xmlSerialization = string[0].replace("mw-collapsible-content\">","").replace("</div></div>","");

					var regex = /<bpmn:process id="(.*?)"/gi;
					var oldBpmnId = regex.exec(xmlSerialization);
					var newBpmnId = "bpmn_" + bpmnId.replace('bpmn_', '');
					xmlSerialization = xmlSerialization.replace( '="' + oldBpmnId + '"', '="' + newBpmnId + '"');
					if ( xmlSerialization === 'undefined' ) {
						container
							.removeClass('with-error')
							.addClass('with-diagram');
					} else {
						window.renderer.importXML(xmlSerialization, function(err) {

							if (err) {
								container
									.removeClass('with-diagram')
									.addClass('with-error');
								console.log(xmlSerialization);
								console.error(err);
							} else {
								container
									.removeClass('with-error')
									.addClass('with-diagram');
								window.importDiagram = false;
								exportArtifacts();
							}
						});
					}
					document.getElementById('initscreen').className = "io-dialog keybindings-dialog";
				}
			},
			error: function(xhr) {
				alert( 'Error: Request failed.' );
			}
		});
	});
};
