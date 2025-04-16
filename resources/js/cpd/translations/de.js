// Contains bpmn-js-bpmnlint translations
export default {
	'{errors} Errors, {warnings} Warnings': 'Fehler: {errors}, Warnungen: {warnings}',
	'No Issues': 'Keine Probleme',
	'Issues for child elements:': 'Probleme für untergeordnete Elemente:',
	'Toggle linting overlays': 'Linting-Overlays umschalten',
	'Element is missing label/name': 'Element hat kein Label/keinen Namen',
	'Label is not unique': 'Label ist nicht eindeutig',
	'Sequence flow is missing condition': 'Sequenzfluss fehlt eine Bedingung',
	'{type} is missing end event': '{type} hat kein Endereignis',
	'Start event is missing event definition': 'Startereignis hat keine Ereignisdefinition',
	'Incoming flows do not join': 'Eingehende Flüsse vereinen sich nicht',
	'Element is missing name': 'Element hat keinen Namen',
	'Element is unused': 'Element wird nicht verwendet',
	'Element name is not unique': 'Elementname ist nicht eindeutig',
	'Element has disallowed type <{type}>': 'Element hat nicht erlaubten Typ <{type}>',
	'Link event is missing name': 'Link-Ereignis hat keinen Namen',
	'Link ${isThrowEvent(event) ? "catch" : "throw" } event with name <${name}> missing in scope': 'Link-${isThrowEvent(event) ? "Catch" : "Throw"}-Ereignis mit Name <${name}> fehlt im Geltungsbereich',
	'Duplicate link throw event with name <${name}> in scope': 'Doppeltes Link-Throw-Ereignis mit Name <${name}> im Geltungsbereich',
	'Duplicate link catch event with name <${name}> in scope': 'Doppeltes Link-Catch-Ereignis mit Name <${name}> im Geltungsbereich',
	'Element is missing bpmndi': 'Element hat kein BPMN-Diagramm (BPMNDI)',
	'Element is not connected': 'Element ist nicht verbunden',
	'SequenceFlow is a duplicate': 'Sequenzfluss ist ein Duplikat',
	'Duplicate outgoing sequence flows': 'Doppelte ausgehende Sequenzflüsse',
	'Duplicate incoming sequence flows': 'Doppelte eingehende Sequenzflüsse',
	'Create new scratch file from selection': 'Neue Scratch-Datei aus Auswahl erstellen',
	'Element is an implicit end': 'Element ist ein implizites Ende',
	'Flow splits implicitly': 'Fluss teilt sich implizit',
	'Element is an implicit start': 'Element ist ein impliziter Start',
	'Element overlaps with other element': 'Element überlappt mit anderem Element',
	'Element is outside of parent boundary': 'Element befindet sich außerhalb der Eltern-Grenze',
	'{type} has multiple blank start events': '{type} hat mehrere leere Startereignisse',
	'Event has multiple event definitions': 'Ereignis hat mehrere Ereignisdefinitionen',
	'{type} is missing start event': '{type} hat kein Startereignis',
	'Start event must be blank': 'Startereignis muss leer sein',
	'Gateway is superfluous. It only has one source and target.': 'Gateway ist überflüssig. Es hat nur eine Quelle und ein Ziel.',
	'Termination is superfluous.': 'Beendigung ist überflüssig.',
	'Link event is missing link name': 'Link-Ereignis hat keinen Link-Namen',
	'Link catch event with link name <${ name }> missing in scope': 'Link-Catch-Ereignis mit Link-Namen <${ name }> fehlt im Geltungsbereich',
	'Link throw event with link name <${ name }> missing in scope': 'Link-Catch-Ereignis mit Link-Namen <${ name }> fehlt im Geltungsbereich'
};
