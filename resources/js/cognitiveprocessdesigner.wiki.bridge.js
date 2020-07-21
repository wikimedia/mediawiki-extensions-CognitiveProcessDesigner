function loadSemanticForms(  apcontinueVal ) {
	$.ajax({
		url: mw.util.wikiScript('api'),
		data: {
			action: 'query',
			list: 'allpages',
			apnamespace: '106',
			format: 'json',
			apcontinue: apcontinueVal
		},
		type: 'GET',
		success: function(data) {
			if (data && data.edit && data.edit.result === 'Success') {

			} else if (data && data.error) {
				alert(data);
			} else {
				if ( apcontinueVal === '' ) {
					annotate = [];
				}
				var dataArray = data.query.allpages;
				for (var i = 0; i < dataArray.length; i++) {
					var labelEntry = dataArray[i].title.replace('Form:', '');
					var dataEntry = {
						label: labelEntry,
						id: dataArray[i].pageid,
						className: 'Form',
						target: {
							type: 'Form1'
						},
						action: {
							call: function(x, event, element) {
								var titlePage = element.label.replace(' ', '_');
								window.ext.popupform.handlePopupFormLink( mw.config.get( 'wgScriptPath' ) + '/index.php?title=Special:FormEdit/' + titlePage + '/' + window.currentElement.id, $('#formIframe'));
							}
						}
					};
					annotate.push(dataEntry);
				}
				if ( data && data.continue && data.continue.apcontinue ) {
					loadSemanticForms( data.continue.apcontinue );
				}
			}
		}
	});
}
var annotate = [];
var xmlSerialization = '';
//Globale Variable
var bpmnId = '';
var importMarker = false;
var firstRound = true;
// 0 --> false: Es findet kein Replace statt
// 1 --> true: Es findet ein Replace statt
// 2 --> Bei diesem Durchlauf Shape erstellen
var replace = 0;
var shapeToBeDeleted = '';
var loop = '';

function getEditToken() {
	return mw.user.tokens.get('editToken');
}

$('#create-bpmn').click(function() {
	annotate = [];
	loadSemanticForms('');
	bpmnId = document.getElementById('bpmnID').value;
	if (bpmnId.length > 0) {

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
				if (data && data.edit && data.edit.result == 'Success') {

				} else if (data && data.error) {
					//Seite existiert noch nicht
					createNewWikiDiagram();
					document.getElementById('initscreen').className = "io-dialog keybindings-dialog";
				} else {
					data = data.parse.wikitext["*"];
					//Sicherheitsabfrage
					document.getElementById('initscreen').className = "io-dialog keybindings-dialog";
					document.getElementById('sicherheitsabfrage').className = "io-dialog keybindings-dialog open";
				}
			}
		});
	}
});

$('#overwrite-diagram-yes').click(function(){
	createNewWikiDiagram();
	document.getElementById('initscreen').className = "io-dialog keybindings-dialog";
	document.getElementById('sicherheitsabfrage').className = "io-dialog keybindings-dialog";
});

$('#overwrite-diagram-no').click(function(){
	document.getElementById('sicherheitsabfrage').className = "io-dialog keybindings-dialog";
	document.getElementById('initscreen').className = "io-dialog keybindings-dialog open";
});

function createNewWikiDiagram() {
	content = "{{#set:Process\n";
	content = content + "|id=" + bpmnId + "\n";
	content = content + "|label=" + bpmnId + "\n";
	content = content + "|has_element=|+sep=," + "\n";
	content = content + "}}\n";

	content = content + "<div id=\"processXml\" class=\"toccolours mw-collapsible mw-collapsed\">The following code shows the XML Serialization of the Process:<div class=\"mw-collapsible-content\">" + xmlSerialization + "</div></div>"


	writeWikiContent(content, bpmnId);
}


function getShapeList(element, shapeList) {
	//First: Get all Children in one object
	for (var i = 0; i < element.children.length; i++) {
		if(element.children[i].constructor.name === 'Shape' && !( element.children[i].id in shapeList )) {
			shapeList.push(element.children[i].id);
			shapeList = getShapeList(element.children[i],shapeList);
		}
	}

	return shapeList;
}


function getShapeListDelete(element, shapeList, deleteElement) {
	//First: Get all Children in one object
	for (var i = 0; i < element.children.length; i++) {
		if(element.children[i].constructor.name === 'Shape' && element.children[i].id !== deleteElement && !( element.children[i].id in shapeList )) {
			shapeList.push(element.children[i].id);
			shapeList = getShapeList(element.children[i],shapeList);
		}
	}

	return shapeList;
}

function updateBPMNWikiPage(list, xmlSerialization) {
	$.ajax({
		url: mw.util.wikiScript('api'),
		data: {
			action: 'parse',
			page: bpmnId,
			prop: 'wikitext',
			format: 'json'
		},
		type: 'GET',
		success: function( data ) {
			if (data && data.edit && data.edit.result === 'Success') {

			} else if (data && data.error) {
				//alert( 'Error: API returned error code "' + data.error.code + '": ' + data.error.info );
			} else {
				/*<div id="processXml" class="toccolours mw-collapsible mw-collapsed">
				The following code shows the XML Serialization of the Process:
				<div class="mw-collapsible-content">XML</div></div>*/
				var data = data.parse.wikitext["*"];
				data = data.replace('[[Category:BPMN]]\n','');
				data = data.replace('[[Category:BPMN]]','');
				var content = "[[Category:BPMN]]\n";
				content = content + "{{#set:Process\n";
				content = content + "|id=" + bpmnId + "\n";
				content = content + "|label=" + bpmnId + "\n";
				content = content + "|has_element=";
				for (var i = 0; i < list.length; i++) {
					content = content + list[i] + ","
				}
				content = content + "|+sep=," + "\n";
				content = content.replace(',|+sep=,','|+sep=,');
				//Changed here from content = content + "}}\n";
				content = content + "}}";
				var string = data.match(/{{#set:Process[\S\s][^}}]*../);
				content = data.replace(string, content);
				string = data.match(/mw-collapsible-content">[\S\s]*<\/div><\/div>/g);
				content = content.replace(string, "mw-collapsible-content\">" + xmlSerialization);
				content = content + "</div></div>";

				writeWikiContent(content, bpmnId);
			}
		},
		error: function(xhr) {
			//alert( 'Error: Request failed.' );
		}
	});
}

function createWikiShape(element) {
	var shapeId = element.id;

	//Erstelle Seite
	var content = '[[Category:';
	switch (element.type) {
		case 'bpmn:Task':
			content = content + 'BPMN Task]]\n';
			break;
		case 'bpmn:ReceiveTask':
			content = content + 'BPMN ReceiveTask]]\n';
			break;
		case 'bpmn:SendTask':
			content = content + 'BPMN SendTask]]\n';
			break;
		case 'bpmn:ScriptTask':
			content = content + 'BPMN ScriptTask]]\n';
			break;
		case 'bpmn:UserTask':
			content = content + 'BPMN UserTask]]\n';
			break;
		case 'bpmn:ManualTask':
			content = content + 'BPMN ManualTask]]\n';
			break;
		case 'bpmn:BusinessRuleTask':
			content = content + 'BPMN BusinessRuleTask]]\n';
			break;
		case 'bpmn:ServiceTask':
			content = content + 'BPMN ServiceTask]]\n';
			break;
		case 'bpmn:CallActivity':
			content = content + 'BPMN CallActivity]]\n';
			break;
		case 'bpmn:SubProcess':
			content = content + 'BPMN SubProcess]]\n';
			break;
		case 'bpmn:AdHocSubProcess':
			content = content + 'BPMN AdHocSubProcess]]\n';
			break;
		case 'bpmn:Transaction':
			content = content + 'BPMN Transaction]]\n';
			break;
		case 'bpmn:Participant':
			//Ist ein Pool
			content = content + 'BPMN Participant]]\n';
			break;
		case 'bpmn:Lane':
			content = content + 'BPMN Lane]]\n';
			break;
		case 'bpmn:ExclusiveGateway':
			content = content + 'BPMN ExclusiveGateway]]\n';
			break;
		case 'bpmn:ComplexGateway':
			content = content + 'BPMN ComplexGateway]]\n';
			break;
		case 'bpmn:InclusiveGateway':
			content = content + 'BPMN InclusiveGateway]]\n';
			break;
		case 'bpmn:ParallelGateway':
			content = content + 'BPMN ParallelGateway]]\n';
			break;
		case 'bpmn:EventBasedGateway':
			content = content + 'BPMN EventBasedGateway]]\n';
			break;
		case 'bpmn:DataObjectReference':
			content = content + 'BPMN DataObjectReference]]\n';
			break;
		case 'bpmn:StartEvent':
			var specialType = element.businessObject.eventDefinitions;
			if (typeof specialType === 'undefined') {
				content = content + 'BPMN StartEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:MessageEventDefinition') {
				content = content + 'BPMN StartMessageEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:TimerEventDefinition') {
				content = content + 'BPMN StartTimerEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:ConditionalEventDefinition') {
				content = content + 'BPMN StartConditionalEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:SignalEventDefinition') {
				content = content + 'BPMN StartSignalEvent]]\n';
			} else {
				content = content + 'Unnknown StartEvent]]\n';
			}
			break;
		case 'bpmn:IntermediateCatchEvent':
			var specialType = element.businessObject.eventDefinitions;
			if (typeof specialType === 'undefined') {
				content = content + 'BPMN IntermediateCatchEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:MessageEventDefinition') {
				content = content + 'BPMN IntermediateCatchMessageEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:LinkEventDefinition') {
				content = content + 'BPMN IntermediateCatchLinkEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:TimerEventDefinition') {
				content = content + 'BPMN IntermediateCatchTimerEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:EscalationEventDefinition') {
				content = content + 'BPMN IntermediateCatchEscalationEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:ConditionalEventDefinition') {
				content = content + 'BPMN IntermediateCatchConditionalEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:SignalEventDefinition') {
				content = content + 'BPMN IntermediateCatchSignalEvent]]\n';
			} else {
				content = content + 'Unnknown IntermediateCatchEvent]]\n';
			}
			break;
		case 'bpmn:IntermediateThrowEvent':
			//checke jetzt ob schon ein Label vorhanden ist
			var specialType = element.businessObject.eventDefinitions;
			if (typeof specialType === 'undefined') {
				content = content + 'BPMN IntermediateThrowEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:MessageEventDefinition') {
				content = content + 'BPMN IntermediateThrowMessageEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:LinkEventDefinition') {
				content = content + 'BPMN IntermediateThrowLinkEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:SignalEventDefinition') {
				content = content + 'BPMN IntermediateThrowSignalEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:CompensateEventDefinition') {
				content = content + 'BPMN IntermediateThrowCompensateEvent]]\n';
			} else {
				content = content + 'Unnknown IntermediateThrowEvent]]\n';
			}
			break;
		case 'bpmn:EndEvent':
			var specialType = element.businessObject.eventDefinitions;
			if (typeof specialType === 'undefined') {
				content = content + 'BPMN EndEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:MessageEventDefinition') {
				content = content + 'BPMN EndMessageEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:EscalationEventDefinition') {
				content = content + 'BPMN EndEscalationEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:ErrorEventDefinition') {
				content = content + 'BPMN EndErrorEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:CancelEventDefinition') {
				content = content + 'BPMN EndCancelEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:CompensateEventDefinition') {
				content = content + 'BPMN EndCompensateEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:SignalEventDefinition') {
				content = content + 'BPMN EndSignalEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:TerminateEventDefinition') {
				content = content + 'BPMN EndTerminateEvent]]\n';
			} else {
				content = content + 'Unnknown EndEvent]]\n';
			}
			break;
		case 'bpmn:TextAnnotation':
			//Erstelle nicht die Seite, weil es ein Kommentar ist
			content = content + 'BPMN TextAnnotation]]\n';
			break;
		case 'bpmn:DataStoreReference':
			content = content + 'BPMN DataStoreReference]]\n';
			break;
		default:
			content = content + 'Unnknown Type]]\n';
	}

	//checke jetzt ob schon ein Marker vorhanden ist; Sollte aber nicht
	if (typeof element.businessObject.loopCharacteristics !== 'undefined') {
		// the variable is defined
		content = content + '[[Category:' + element.businessObject.loopCharacteristics.type.replace('bpmn:', '') + ']]\n';
	}


	//checke jetzt ob schon ein Label vorhanden ist
	content = content + "{{#set:Element\n";
	content = content + "|id=" + shapeId + "\n";


	//checke jetzt ob schon ein Label vorhanden ist
	var val = element.businessObject.name;
	if (typeof val !== 'undefined') {
		// the variable is defined
		content = content + '|label=' + val + '\n';
	}

	//Parent hinzufügen
	if (typeof element.parent !== 'undefined' && element.parent.id.indexOf('Process_1') != 0 && element.parent.type.indexOf('bpmn:Collaboration') != 0) {
		content = content + '|parent=' + element.parent.id + '\n';
	}

	//Children hinzufügen
	if (typeof element.children !== 'undefined' && element.children.length > 0) {
		content = content + "|children=";
		for (var i = 0; i < element.children.length; i++) {
			content = content + element.children[i].id + ',';
		}
		content = content + "|+sep=,\n";
		content = content.replace(',|+sep=,', '|+sep=,');
	}


	//Füge x und y koordinate und größe von dem Diagramm hinzu
	content = content + "|xBound=" + element.x + "\n";
	content = content + "|yBound=" + element.y + "\n";
	content = content + "|width=" + element.width + "\n";
	content = content + "|height=" + element.height + "\n";


	//Vorgänger und Nachfolger
	var tmp = element.outgoing;
	if (typeof tmp !== 'undefined' && element.outgoing.length > 0) {
		content = content + "|outgoing=";
		for (var i = 0; i < element.outgoing.length; i++) {
			content = content + element.outgoing[i].id + ',';
		}
		content = content + "|+sep=,\n";
		content = content.replace(',|+sep=,', '|+sep=,');

	}

	tmp = element.incoming;
	if (typeof tmp !== 'undefined' && element.incoming.length > 0) {
		content = content + "|incoming=";
		for (var i = 0; i < element.incoming.length; i++) {
			content = content + element.incoming[i].id + ',';
		}
		content = content + "|+sep=,\n";
		content = content.replace(',|+sep=,', '|+sep=,');
	}


	content = content + "}}\n\n";


	if (replace == 0) {
		//Es findet kein Replace statt, ganz normal ein Shape erstellen
		writeWikiContent(content, shapeId);
		//Update Lanes
		if (element.type.indexOf('bpmn:Lane') == 0) {
			if (firstRound) {
				firstRound = false;
			} else {
				updateShape(element.parent);
				firstRound = true;
			}

			for (var i = 0; i < element.parent.children.length; i++) {
				if (element.parent.children[i].id.indexOf(element.id) != 0 && element.parent.children[i].constructor.name == 'Shape') {
					updateShape(element.parent.children[i]);
				}
			}
		}

		var shapeList = [];
		while (typeof element.parent !== 'undefined') {
			element = element.parent;
		}
		updateBPMNWikiPage(getShapeList(element,shapeList));



	} else if (replace == 1) {
		//Es findet ein Replace statt, allerdings sind die incoming und outgoing knoten noch nicht bekannt
	} else if (replace == 2) {
		//Es findet ein Replace statt, jetzt sind die incoming und outgoing knoten bekannt
		//replace Old Content mit neuem

		writeWikiContent(content, shapeId);
	}
}



function addConnection(source, target, connection) {
		//Create first the New Page for the Connection
		var content = '[[Category:';

		switch (connection.type) {
			case 'bpmn:SequenceFlow':
				content = content + 'SequenceFlow]]\n';
				break;
			case 'bpmn:MessageFlow':
				content = content + 'MessageFlow]]\n';
				break;
			case 'bpmn:Association':
				content = content + 'Association]]\n';
				break;
			case 'bpmn:DataInputAssociation':
				content = content + 'DataInputAssociation]]\n';
				break;
			case 'bpmn:DataOutputAssociation':
				content = content + 'DataOutputAssociation]]\n';
				break;
			default:
				content = content + 'Unnknown Type]]\n';
		}
		content = content + '{{#set:Element\n|id=' + connection.id + '\n';
		content = content + '|sourceRef=' + source.id + '\n';
		content = content + '|targetRef=' + target.id + '\n';
		content = content + '}}\n';

		//Adde Waypoints
		for (var i = 0; i < connection.waypoints.length; i++) {
			content = content + '{{#subobject:WayPoint_' + i + "\n";
			content = content + '|label=' + connection.waypoints[i].x + "\n";
			content = content + '|x=' + connection.waypoints[i].x + "\n";
			content = content + '|y=' + connection.waypoints[i].y + "\n";
			content = content + "}}\n\n";
		}


		writeWikiContent(content, connection.id);

		//Update Source and Target
		updateShape(source);
		updateShape(target);

}

function writeWikiContent(content, wikipage) {
	//Schreibe den Content rein
	$.ajax({
		url: mw.util.wikiScript('api'),
		data: {
			format: 'json',
			action: 'edit',
			title: wikipage,
			text: content,
			token: getEditToken()
		},
		dataType: 'json',
		type: 'POST',
		success: function(data) {
			if (data && data.edit && data.edit.result == 'Success') {
				//alert ('Ich bin in der Methode mit der ID ' + shapeId);
			} else if (data && data.error) {
				//alert( 'Error: API returned error code "' + data.error.code + '": ' + data.error.info );
			} else {
				//alert( 'Error: Unknown result from API.' );
			}
		},
		error: function(xhr) {
			//alert( 'Error: Request failed.' );
		}
	});
}


function writeLabel(element) {
	if (element.id.indexOf('Flow') !== -1 && element.id.indexOf('_label') !== -1) {
		updateConnection(element.labelTarget);
	} else if (element.id.indexOf('Flow') !== -1) {
		updateConnection(element);
	} else if (element.id.indexOf('_label') !== -1) {
		updateShape(element.labelTarget);
	} else {
		updateShape(element);
	}

}

function replaceShapeFromWiki(oldShape, newShape) {
	//Create new Page
	replace = 2;
	createWikiShape(newShape);
	replace = 1;

	//Delete old Page
   // writeWikiContent('[[Category:Delete]]', oldShape.id);
}

function updateShape(element) {
	if (replace != 1 && shapeToBeDeleted != element.id) {

		var shapeId = element.id;
		var content = '';

		//checke jetzt ob schon ein Marker vorhanden ist
		if (typeof element.businessObject.loopCharacteristics !== 'undefined') {
			// the variable is defined
			content = content + '[[Category:' + loop + ']]\n';
		}
		//Erstelle Seite
		content = content + "{{#set:Element\n";

		content = content + "|id=" + shapeId + "\n";

		//Label hinzufügen
		var labelPosition = '';
		if (typeof element.businessObject.name !== 'undefined' && typeof element.label !== 'undefined') {
			content = content + '|label=' + element.businessObject.name + '\n';
			labelPosition = labelPosition + '{{#subobject:PositionLabel\n';
			labelPosition = labelPosition + '|x=' + element.label.x + "\n";
			labelPosition = labelPosition + '|y=' + element.label.y + "\n";
			labelPosition = labelPosition + '|width=' + element.label.width + "\n";
			labelPosition = labelPosition + '|height=' + element.label.height + "\n";
			labelPosition = labelPosition + "}}\n\n";
		} else if (typeof element.businessObject.name !== 'undefined' && typeof element.businessObject.di.bounds !== 'undefined') {
			content = content + '|label=' + element.businessObject.name + '\n';
			labelPosition = labelPosition + '{{#subobject:PositionLabel\n';
			labelPosition = labelPosition + '|x=' + element.businessObject.di.bounds.x + "\n";
			labelPosition = labelPosition + '|y=' + element.businessObject.di.bounds.y + "\n";
			labelPosition = labelPosition + '|width=' + element.businessObject.di.bounds.width + "\n";
			labelPosition = labelPosition + '|height=' + element.businessObject.di.bounds.height + "\n";
			labelPosition = labelPosition + "}}\n\n";
		}

		//Parent hinzufügen
		if (typeof element.parent !== 'undefined' && element.parent.id !== undefined && element.parent.id.indexOf('Process_') != 0 && element.parent.type.indexOf('bpmn:Collaboration') != 0) {
			content = content + '|parent=' + element.parent.id + '\n';
		}

		//Children hinzufügen
		if (typeof element.children !== 'undefined' && element.children.length > 0) {
			content = content + "|children=";
			for (var i = 0; i < element.children.length; i++) {
				if (element.children[i].constructor.name == 'Shape') {
				  content = content + element.children[i].id + ',';
				}
			}
			content = content + "|+sep=,\n";
			content = content.replace(',|+sep=,', '|+sep=,');
		}

		//Füge x und y koordinate und größe von dem Diagramm hinzu
		content = content + "|xBound=" + element.x + "\n";
		content = content + "|yBound=" + element.y + "\n";
		content = content + "|width=" + element.width + "\n";
		content = content + "|height=" + element.height + "\n";
 


		//Outgoing Links
		var tmp = element.outgoing;
		if (typeof tmp !== 'undefined' && element.outgoing.length > 0) {
			content = content + "|outgoing=";
			for (var i = 0; i < element.outgoing.length; i++) {
				content = content + element.outgoing[i].id + ',';
			}
			content = content + "|+sep=,\n";
			content = content.replace(',|+sep=,', '|+sep=,');

		}

		//Incoming Links
		tmp = element.incoming;
		if (typeof tmp !== 'undefined' && element.incoming.length > 0) {
			content = content + "|incoming=";
			for (var i = 0; i < element.incoming.length; i++) {
				content = content + element.incoming[i].id + ',';
			}
			content = content + "|+sep=,\n";
			content = content.replace(',|+sep=,', '|+sep=,');
		}

		//Checke if Comment, falls ja, füge kommentar hinzu
		if (element.type.indexOf('bpmn:TextAnnotation') == 0) {
			content = content + '|comment=' + element.businessObject.text + '\n';
		}
		content = content + "}}\n";
		content = content + labelPosition;

		//Replace Old Content mit neuem
		$.ajax({
			url: mw.util.wikiScript('api'),
			data: {
				action: 'parse',
				page: shapeId,
				prop: 'wikitext',
				format: 'json'
			},
			type: 'GET',
			success: function(data) {
				if (data && data.edit && data.edit.result == 'Success') {
					//    alert ('Ich bin in der Methode mit der ID ' + shapeId);
				} else if (data && data.error) {
					//    alert( 'Error: API returned error code "' + data.error.code + '": ' + data.error.info );
					//Hier gibt es die Seite noch
					writeWikiContent(content, shapeId);
				} else {
					var contentOld = data.parse.wikitext["*"];
					var string = contentOld.match(/{{#subobject:PositionLabel[\S\s][^}}]*../gm);
					if (string !== null) {
						for (var i = 0; i < string.length; i++) {
							contentOld = contentOld.replace(string[i], "");
						}
					}

					contentOld = replaceMarker(contentOld);
					var string = contentOld.match(/{{#set:Element[\S\s][^}}]*../);
					content = contentOld.replace(string, content);

					writeWikiContent(content, shapeId);
				}

			},
			error: function(xhr) {
				//     alert( 'Error: Request failed.' );
			}
		});
	}
}


function updateConnection(connection) {
		var connectionId = connection.id;
		var source = connection.businessObject.sourceRef;
		var target = connection.businessObject.targetRef;
		//Create first the New Page for the Connectionva

		var content = '{{#set:Element\n|id=' + connection.id + '\n';
		content = content + '|sourceRef=' + source.id + '\n';
		content = content + '|targetRef=' + target.id + '\n';


		//Füge Label hinzu, falls eins vorhanden ist
		var labelPosition = '';
		var label = connection.businessObject.name;
		if (typeof label !== 'undefined' && label != '') {
			// the variable is defined
			content = content + '|label=' + label + '\n';
			content = content + '}}\n';

			labelPosition = '{{#subobject:PositionLabel\n';
			labelPosition = labelPosition + '|x=' + connection.businessObject.di.label.bounds.x + "\n";
			labelPosition = labelPosition + '|y=' + connection.businessObject.di.label.bounds.y + "\n";
			labelPosition = labelPosition + '|width=' + connection.businessObject.di.label.bounds.width + "\n";
			labelPosition = labelPosition + '|height=' + connection.businessObject.di.label.bounds.height + "\n";
			labelPosition = labelPosition + "}}\n\n";
		} else {
			content = content + '}}\n';
		}

		content = content + labelPosition;

		//Adde Waypoints
		content = content + "\n";
		for (var i = 0; i < connection.waypoints.length; i++) {
			content = content + '{{#subobject:WayPoint_' + i + "\n";
			content = content + '|x=' + connection.waypoints[i].x + "\n";
			content = content + '|y=' + connection.waypoints[i].y + "\n";
			content = content + "}}\n";
		}

		//Replace Old Content mit neuem
		$.ajax({
			url: mw.util.wikiScript('api'),
			data: {
				action: 'parse',
				page: connectionId,
				prop: 'wikitext',
				format: 'json'
			},
			type: 'GET',
			success: function(data) {
				if (data && data.edit && data.edit.result == 'Success') {
					//    alert ('Ich bin in der Methode mit der ID ' + shapeId);
				} else if (data && data.error) {
					//    alert( 'Error: API returned error code "' + data.error.code + '": ' + data.error.info );
				} else {
					var contentOld = data.parse.wikitext["*"];
					var string = contentOld.match(/{{#subobject:WayPoint_[\S\s][^}}]*../gm);
					if (string !== null) {
						for (var i = 0; i < string.length; i++) {
							contentOld = contentOld.replace(string[i], "");
						}
					}
					string = contentOld.match(/{{#subobject:PositionLabel[\S\s][^}}]*../gm);
					if (string !== null) {
						for (var i = 0; i < string.length; i++) {
							contentOld = contentOld.replace(string[i], "");
						}
					}
					string = contentOld.match(/{{#set:Element[\S\s][^}}]*../);
					content = contentOld.replace(string, content);

					writeWikiContent(content, connectionId);
				}

			},
			error: function(xhr) {
				//     alert( 'Error: Request failed.' );
			}
		});
}



function moveShape(shape) {
	if (shape.id.indexOf('Flow') != -1 && shape.id.indexOf('_label') != -1) {
		updateConnection(shape.labelTarget);
	} else if (shape.id.indexOf('Flow') != -1) {
		updateConnection(element);
	} else if (shape.id.indexOf('_label') != -1) {
		updateShape(shape.labelTarget);
	} else {
		updateShape(shape);
	}

}


function replaceMarker(content) {
	var string = content.match(/\[\[[\S\s][^\]\]]*../gm);
	if (string != null) {
		for (var i = 0; i < string.length; i++) {
		if (string[i].indexOf('LoopMarker') > -1) {
			//Muss raus
			content = content.replace(string[i], '');
		} else if (string[i].indexOf('ParallelMarker') > -1) {
			content = content.replace(string[i], '');
		} else if (string[i].indexOf('SequentialMarker') > -1) {
			content = content.replace(string[i], '');
		} else if (string[i].indexOf('CompensationMarker') > -1) {
			content = content.replace(string[i], '');
		}

	}
	}


	return content;
}

function importWikiShape(element, shapeId) {

	//Erstelle Seite
	var content = '[[Category:';
	switch (element.bpmnElement.$type) {
		case 'bpmn:Task':
			content = content + 'BPMN Task]]\n';
			break;
		case 'bpmn:ReceiveTask':
			content = content + 'BPMN ReceiveTask]]\n';
			break;
		case 'bpmn:SendTask':
			content = content + 'BPMN SendTask]]\n';
			break;
		case 'bpmn:ScriptTask':
			content = content + 'BPMN ScriptTask]]\n';
			break;
		case 'bpmn:UserTask':
			content = content + 'BPMN UserTask]]\n';
			break;
		case 'bpmn:ManualTask':
			content = content + 'BPMN ManualTask]]\n';
			break;
		case 'bpmn:BusinessRuleTask':
			content = content + 'BPMN BusinessRuleTask]]\n';
			break;
		case 'bpmn:ServiceTask':
			content = content + 'BPMN ServiceTask]]\n';
			break;
		case 'bpmn:CallActivity':
			content = content + 'BPMN CallActivity]]\n';
			break;
		case 'bpmn:SubProcess':
			content = content + 'BPMN SubProcess]]\n';
			break;
		case 'bpmn:AdHocSubProcess':
			content = content + 'BPMN AdHocSubProcess]]\n';
			break;
		case 'bpmn:Transaction':
			content = content + 'BPMN Transaction]]\n';
			break;
		case 'bpmn:Participant':
			//Ist ein Pool
			content = content + 'BPMN Participant]]\n';
			break;
		case 'bpmn:Lane':
			content = content + 'BPMN Lane]]\n';
			break;
		case 'bpmn:ExclusiveGateway':
			content = content + 'BPMN ExclusiveGateway]]\n';
			break;
		case 'bpmn:ComplexGateway':
			content = content + 'BPMN ComplexGateway]]\n';
			break;
		case 'bpmn:InclusiveGateway':
			content = content + 'BPMN InclusiveGateway]]\n';
			break;
		case 'bpmn:ParallelGateway':
			content = content + 'BPMN ParallelGateway]]\n';
			break;
		case 'bpmn:EventBasedGateway':
			content = content + 'BPMN EventBasedGateway]]\n';
			break;
		case 'bpmn:DataObjectReference':
			content = content + 'BPMN DataObjectReference]]\n';
			break;
		case 'bpmn:StartEvent':
			var specialType = element.bpmnElement.eventDefinitions;
			if (typeof specialType === 'undefined') {
				content = content + 'BPMN StartEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:MessageEventDefinition') {
				content = content + 'BPMN StartMessageEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:TimerEventDefinition') {
				content = content + 'BPMN StartTimerEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:ConditionalEventDefinition') {
				content = content + 'BPMN StartConditionalEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:SignalEventDefinition') {
				content = content + 'BPMN StartSignalEvent]]\n';
			} else {
				content = content + 'Unnknown StartEvent]]\n';
			}
			break;
		case 'bpmn:IntermediateCatchEvent':
			var specialType = element.bpmnElement.eventDefinitions;
			if (typeof specialType === 'undefined') {
				content = content + 'BPMN IntermediateCatchEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:MessageEventDefinition') {
				content = content + 'BPMN IntermediateCatchMessageEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:LinkEventDefinition') {
				content = content + 'BPMN IntermediateCatchLinkEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:TimerEventDefinition') {
				content = content + 'BPMN IntermediateCatchTimerEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:EscalationEventDefinition') {
				content = content + 'BPMN IntermediateCatchEscalationEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:ConditionalEventDefinition') {
				content = content + 'BPMN IntermediateCatchConditionalEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:SignalEventDefinition') {
				content = content + 'BPMN IntermediateCatchSignalEvent]]\n';
			} else {
				content = content + 'Unnknown IntermediateCatchEvent]]\n';
			}
			break;
		case 'bpmn:IntermediateThrowEvent':
			//checke jetzt ob schon ein Label vorhanden ist
			var specialType = element.bpmnElement.eventDefinitions;
			if (typeof specialType === 'undefined') {
				content = content + 'BPMN IntermediateThrowEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:MessageEventDefinition') {
				content = content + 'BPMN IntermediateThrowMessageEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:LinkEventDefinition') {
				content = content + 'BPMN IntermediateThrowLinkEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:SignalEventDefinition') {
				content = content + 'BPMN IntermediateThrowSignalEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:CompensateEventDefinition') {
				content = content + 'BPMN IntermediateThrowCompensateEvent]]\n';
			} else {
				content = content + 'Unnknown IntermediateThrowEvent]]\n';
			}
			break;
		case 'bpmn:EndEvent':
			var specialType = element.bpmnElement.eventDefinitions;
			if (typeof specialType === 'undefined') {
				content = content + 'BPMN EndEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:MessageEventDefinition') {
				content = content + 'BPMN EndMessageEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:EscalationEventDefinition') {
				content = content + 'BPMN EndEscalationEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:ErrorEventDefinition') {
				content = content + 'BPMN EndErrorEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:CancelEventDefinition') {
				content = content + 'BPMN EndCancelEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:CompensateEventDefinition') {
				content = content + 'BPMN EndCompensateEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:SignalEventDefinition') {
				content = content + 'BPMN EndSignalEvent]]\n';
			} else if (specialType[0].$type == 'bpmn:TerminateEventDefinition') {
				content = content + 'BPMN EndTerminateEvent]]\n';
			} else {
				content = content + 'Unnknown EndEvent]]\n';
			}
			break;
		case 'bpmn:TextAnnotation':
			//Erstelle nicht die Seite, weil es ein Kommentar ist
			content = content + 'BPMN TextAnnotation]]\n';
			break;
		case 'bpmn:DataStoreReference':
			content = content + 'BPMN DataStoreReference]]\n';
			break;
		default:
			content = content + 'Unnknown Type]]\n';
	}

	//checke jetzt ob schon ein Marker vorhanden ist; Sollte aber nicht
	if (typeof element.bpmnElement.loopCharacteristics !== 'undefined') {
		// the variable is defined
		content = content + '[[Category:' + element.bpmnElement.loopCharacteristics.type.replace('bpmn:', '') + ']]\n';
	}


	//checke jetzt ob schon ein Label vorhanden ist
	content = content + "{{#set:Element\n";
	content = content + "|id=" + shapeId + "\n";


	//checke jetzt ob schon ein Label vorhanden ist
	var labelPosition = '';
	if (typeof element.bpmnElement.name !== 'undefined' && typeof element.label !== 'undefined') {
		content = content + '|label=' + element.bpmnElement.name + '\n';
		labelPosition = labelPosition + '{{#subobject:PositionLabel\n';
		labelPosition = labelPosition + '|x=' + element.label.x + "\n";
		labelPosition = labelPosition + '|y=' + element.label.y + "\n";
		labelPosition = labelPosition + '|width=' + element.label.width + "\n";
		labelPosition = labelPosition + '|height=' + element.label.height + "\n";
		labelPosition = labelPosition + "}}\n\n";
	} else if (typeof element.bpmnElement.name !== 'undefined' && typeof element.bounds !== 'undefined') {
			content = content + '|label=' + element.bpmnElement.name + '\n';
			labelPosition = labelPosition + '{{#subobject:PositionLabel\n';
			labelPosition = labelPosition + '|x=' + element.bounds.x + "\n";
			labelPosition = labelPosition + '|y=' + element.bounds.y + "\n";
			labelPosition = labelPosition + '|width=' + element.bounds.width + "\n";
			labelPosition = labelPosition + '|height=' + element.bounds.height + "\n";
			labelPosition = labelPosition + "}}\n\n";
		}

	//Parent hinzufügen
		if (typeof element.$parent.bpmnElement !== 'undefined' && element.$parent.bpmnElement.id.indexOf('Process_1') != 0 && element.$parent.bpmnElement.$type.indexOf('bpmn:Collaboration') != 0) {
			content = content + '|parent=' + element.$parent.bpmnElement.id + '\n';
		}


	//Füge x und y koordinate und größe von dem Diagramm hinzu
	content = content + "|xBound=" + element.bounds.x + "\n";
	content = content + "|yBound=" + element.bounds.y + "\n";
	content = content + "|width=" + element.bounds.width + "\n";
	content = content + "|height=" + element.bounds.height + "\n";

	var addSep = false;
	//Vorgänger und Nachfolger
	var tmp = element.bpmnElement.outgoing;
	if (typeof tmp !== 'undefined' && element.bpmnElement.outgoing.length > 0) {
		content = content + "|outgoing=";
		for (var i = 0; i < element.bpmnElement.outgoing.length; i++) {
			content = content + element.bpmnElement.outgoing[i].id + ',';
		}
		addSep = true;
	}

	//dataOutputAssociations
	if (typeof element.bpmnElement.dataOutputAssociations !== 'undefined') {
		for (var i = 0; i < element.bpmnElement.dataOutputAssociations.length; i++) {
			content = content + element.bpmnElement.dataOutputAssociations[i].id + ',';
		}
		addSep = true;
	}

	if (addSep) {
		content = content + "|+sep=,\n";
		content = content.replace(',|+sep=,', '|+sep=,');
	}

	addSep = false;
	tmp = element.bpmnElement.incoming;
	if (typeof tmp !== 'undefined' && element.bpmnElement.incoming.length > 0) {
		content = content + "|incoming=";
		for (var i = 0; i < element.bpmnElement.incoming.length; i++) {
			content = content + element.bpmnElement.incoming[i].id + ',';
		}
		addSep = true;
	}

	//dataInputAssociations
	if (typeof element.bpmnElement.dataInputAssociations !== 'undefined') {
		for (var i = 0; i < element.bpmnElement.dataInputAssociations.length; i++) {
			content = content + element.bpmnElement.dataInputAssociations[i].id + ',';
		}
		addSep = true;
	}

	if (addSep) {
		content = content + "|+sep=,\n";
		content = content.replace(',|+sep=,', '|+sep=,');
	}


	//Checke if Comment, falls ja, füge kommentar hinzu
	if (element.bpmnElement.$type.indexOf('bpmn:TextAnnotation') == 0) {
		content = content + '|comment=' + element.bpmnElement.text + '\n';
	}


	content = content + "}}\n";
	content = content + labelPosition;

	writeWikiContent(content, shapeId);

}    


function importConnection(connection) {
		//Create first the New Page for the Connection

		var content = '[[Category:';

		switch (connection.bpmnElement.$type) {
			case 'bpmn:SequenceFlow':
				content = content + 'SequenceFlow]]\n';
				break;
			case 'bpmn:MessageFlow':
				content = content + 'MessageFlow]]\n';
				break;
			case 'bpmn:Association':
				content = content + 'Association]]\n';
				break;
			case 'bpmn:DataInputAssociation':
				content = content + 'DataInputAssociation]]\n';
				break;
			case 'bpmn:DataOutputAssociation':
				content = content + 'DataOutputAssociation]]\n';
				break;
			default:
				content = content + 'Unnknown Type]]\n';
		}
		content = content + '{{#set:Element\n|id=' + connection.bpmnElement.id + '\n';
		content = content + '|sourceRef=' + connection.bpmnElement.sourceRef.id + '\n';
		content = content + '|targetRef=' + connection.bpmnElement.targetRef.id + '\n';
		content = content + '}}\n';

		//Füge Label hinzu, falls eins vorhanden ist
		var labelPosition = '';
		var label = connection.bpmnElement.name;
		if (typeof label !== 'undefined' && label != '') {
			// the variable is defined
			content = content + '|label=' + label + '\n';

			labelPosition = '{{#subobject:PositionLabel\n';
			labelPosition = labelPosition + '|x=' + connection.label.bounds.x + "\n";
			labelPosition = labelPosition + '|y=' + connection.label.bounds.y + "\n";
			labelPosition = labelPosition + '|width=' + connection.label.bounds.width + "\n";
			labelPosition = labelPosition + '|height=' + connection.label.bounds.height + "\n";
			labelPosition = labelPosition + "}}\n\n";
		}

		content = content + labelPosition;

		//Adde Waypoints
		for (var i = 0; i < connection.waypoint.length; i++) {
			content = content + '{{#subobject:WayPoint_' + i + "\n";
			content = content + '|x=' + connection.waypoint[i].x + "\n";
			content = content + '|y=' + connection.waypoint[i].y + "\n";
			content = content + "}}\n\n";
		}


		writeWikiContent(content, connection.bpmnElement.id);

}
