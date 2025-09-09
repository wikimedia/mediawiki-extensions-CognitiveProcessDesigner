<?php

namespace CognitiveProcessDesigner;

use CognitiveProcessDesigner\Exceptions\CpdSvgException;
use DOMDocument;
use DOMXPath;
use File;
use MediaHandler;
use MediaWiki\Message\Message;
use MediaWiki\Specials\SpecialUpload;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MWFileProps;
use RepoGroup;
use Wikimedia\AtEase\AtEase;
use Wikimedia\FileBackend\FSFile\TempFSFile;
use Wikimedia\Mime\MimeAnalyzer;

class SvgFile {

	/**
	 * @param MimeAnalyzer $mimeAnalyzer
	 * @param RepoGroup $repoGroup
	 */
	public function __construct(
		private readonly MimeAnalyzer $mimeAnalyzer,
		private readonly RepoGroup $repoGroup
	) {
	}

	/**
	 * @param Title $svgFile
	 * @param string $svg
	 * @param User $user
	 *
	 * @return File
	 * @throws CpdSvgException
	 */
	public function save( Title $svgFile, string $svg, User $user ): File {
		$this->validateXml( $svg );
		$filename = $svgFile->getDBkey();
		$tempFilePath = TempFSFile::getUsableTempDirectory() . '/' . $filename;
		if ( !file_put_contents( $tempFilePath, $svg ) ) {
			throw new CpdSvgException( 'Could not save SVG file' );
		}

		$repo = $this->repoGroup->getLocalRepo();
		$repoFile = $repo->newFile( $svgFile );
		if ( !$repoFile ) {
			throw new CpdSvgException( 'Could not create file object' );
		}

		/*
		 * The following code is almost a direct copy of
		 * <mediawiki>/maintenance/importImages.php
		 */
		$mwProps = new MWFileProps( $this->mimeAnalyzer );
		$props = $mwProps->getPropsFromPath( $tempFilePath, true );

		$flags = 0;
		$publishOptions = [];
		$handler = MediaHandler::getHandler( $props['mime'] );
		if ( $handler ) {
			if ( is_array( $props['metadata'] ) ) {
				$metadata = $props['metadata'];
			} else {
				$metadata = AtEase::quietCall( 'unserialize', $props['metadata'] );
			}

			$publishOptions['headers'] = $handler->getContentHeaders( $metadata );
		} else {
			$publishOptions['headers'] = [];
		}

		$status = $repoFile->publish( $tempFilePath, $flags, $publishOptions );

		if ( !$status->isOK() ) {
			throw new CpdSvgException(
				Message::newFromKey( 'cpd-error-message-publish-svg-file', $status->getMessages()[0]['message'] )
			);
		}

		$commentText = SpecialUpload::getInitialPageText();
		$status = $repoFile->recordUpload3( $status->value, '', $commentText, $user, $props );

		if ( !$status->isOK() ) {
			$errors = $status->getMessages();
			if ( count( $errors ) === 1 && $errors[0]->getKey() === 'fileexists-no-change' ) {
				// "Allowed" error
				return $repoFile;
			}
			$msgText = array_map( static function ( $error ) {
				return Message::newFromSpecifier( $error )->text();
			}, $errors );
			throw new CpdSvgException(
				Message::newFromKey( 'cpd-error-message-publish-svg-file', implode( ', ', $msgText ) )
			);
		}

		return $repoFile;
	}

	/**
	 * Checks the SVG XML for validity and safety.
	 *
	 * @param string $svg
	 *
	 * @return void
	 * @throws CpdSvgException
	 */
	private function validateXml( string $svg ): void {
		if ( empty( $svg ) ) {
			return;
		}

		if ( !str_contains( $svg, 'http://www.w3.org/2000/svg' ) ) {
			throw new CpdSvgException( "Invalid SVG: missing SVG namespace" );
		}

		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$loaded = $dom->loadXML( $svg, LIBXML_NONET );
		libxml_clear_errors();

		if ( !$loaded ) {
			throw new CpdSvgException( "Invalid SVG: could not be parsed as XML" );
		}

		$xpath = new DOMXPath( $dom );
		$xpath->registerNamespace( 'svg', 'http://www.w3.org/2000/svg' );

		// Forbidden elements
		$forbiddenElements = [
			'script',
			'iframe',
			'embed',
			'object',
			'foreignObject',
			'frameset',
			'frame',
			'meta'
		];
		foreach ( $forbiddenElements as $tag ) {
			if ( $xpath->query( '//svg:' . $tag )->length > 0 ) {
				throw new CpdSvgException( "Unsafe SVG: contains forbidden element <$tag>" );
			}
		}

		// Forbidden attributes
		foreach ( $xpath->query( '//*' ) as $node ) {
			foreach ( iterator_to_array( $node->attributes ) as $attr ) {
				$attrName = strtolower( $attr->nodeName );
				$attrValue = strtolower( $attr->nodeValue );

				// Block all event handlers starting with 'on'
				if ( str_starts_with( $attrName, 'on' ) || in_array( $attrName, [ 'style' ] ) ) {
					throw new CpdSvgException( "Unsafe SVG: contains forbidden attribute '$attrName'" );
				}

				// Block dangerous URLs
				if (
					in_array(
						$attrName,
						[
							'href',
							'xlink:href'
						]
					)
				) {
					if ( preg_match( '#^\s*(javascript|data)\s*:#i', $attrValue ) ) {
						throw new CpdSvgException( "Unsafe SVG: contains unsafe URL in '$attrName'" );
					}
				}
			}
		}
	}
}
