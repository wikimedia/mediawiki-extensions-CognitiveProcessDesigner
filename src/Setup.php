<?php

namespace CognitiveProcessDesigner;

class Setup {
	public static function callback() {
		mwsInitComponents();

		if ( !defined( 'CONTENT_SLOT_CPD_PROCESS_META' ) ) {
			define( 'CONTENT_SLOT_CPD_PROCESS_META', 'cpd-process-meta' );
		}
	}
}
