<?php

namespace CognitiveProcessDesigner;

use Exception;

class CpdElementFactory {
	/**
	 * @param array $elements
	 *
	 * @return CpdElement[]
	 * @throws Exception
	 */
	public function makeElements( array $elements ): array {
		return array_map( function ( $element ) {
			return $this->makeElement( $element );
		}, $elements );
	}

	/**
	 * @param array $element
	 *
	 * @return CpdElement
	 * @throws Exception
	 */
	public function makeElement( array $element ): CpdElement {
		return CpdElement::fromElementJson( $element );
	}
}
