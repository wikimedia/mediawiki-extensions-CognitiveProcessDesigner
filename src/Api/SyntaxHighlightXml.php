<?php

namespace CognitiveProcessDesigner\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
use DOMDocument;
use ExtensionRegistry;
use MediaWiki\Title\Title;
use Parser;
use ParserOptions;
use Wikimedia\ParamValidator\ParamValidator;

class SyntaxHighlightXml extends ApiBase {
	/**
	 * @var Parser
	 */
	private Parser $parser;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param Parser $parser
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		Parser $parser
	) {
		parent::__construct( $main, $action );
		$this->parser = $parser;
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$this->getResult()->addValue( null, 'highlightedXml', $this->highlightXml( $params['xml'] ) );
	}

	/**
	 * @param string $xml
	 *
	 * @return string
	 */
	private function highlightXml( string $xml ): string {
		$xml = trim( $xml, '\"' );
		$xml = str_replace( '\\"', '"', $xml );
		$xml = str_replace( '\n', "", $xml );

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML( $xml );
		$formattedXml = $dom->saveXML();

		if ( ExtensionRegistry::getInstance()->isLoaded( "SyntaxHighlight" ) ) {
			$xml = "<syntaxhighlight lang=\"xml\">$formattedXml</syntaxhighlight>";
		} else {
			$xml = "<pre>$xml</pre>";
		}

		return $this->parser->parse(
			$xml,
			Title::newMainPage(),
			ParserOptions::newFromUser( $this->getUser() )
		)->getText();
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return [
			'xml' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}
