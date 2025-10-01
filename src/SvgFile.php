<?php

namespace CognitiveProcessDesigner;

use CognitiveProcessDesigner\Exceptions\CpdSvgException;
use DOMDocument;
use DOMXPath;
use File;
use MediaHandler;
use MediaWiki\Message\Message;
use MediaWiki\Parser\Sanitizer;
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
			if ( $node->tagName === 'style' && $this->isHostileCSS( $node->nodeValue ) ) {
				throw new CpdSvgException( 'Unsafe SVG: contains hostile CSS in a style element' );
			}

			foreach ( iterator_to_array( $node->attributes ) as $attr ) {
				$attrName = strtolower( $attr->nodeName );
				$attrValue = strtolower( $attr->nodeValue );

				// Block all event handlers starting with 'on'
				if ( str_starts_with( $attrName, 'on' ) ) {
					throw new CpdSvgException( "Unsafe SVG: contains forbidden attribute '$attrName'" );
				}

				// Block dangerous style
				if ( $attrName === 'style' ) {
					// Reject if it contains javascript:, expression() or hostile CSS
					if (
						$this->isHostileCSS( $attrValue ) ||
						preg_match( '#javascript:|expression\(|data:#i', $attrValue )
					) {
						throw new CpdSvgException( "Unsafe SVG: contains unsafe CSS in 'style'" );
					}
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

	private function isHostileCSS( string $value ): bool {
		$value = Sanitizer::normalizeCss( $value );

		// Taken from UploadBase::checkCssFragment, which unfortunately is private.

		# Forbid external stylesheets, for both reliability and to protect viewer's privacy
		if ( stripos( $value, '@import' ) !== false ) {
			return true;
		}

		# We allow @font-face to embed fonts with data: urls, so we snip the string
		# 'url' out so that this case won't match when we check for urls below
		$pattern = '!(@font-face\s*{[^}]*src:)url(\("data:;base64,)!im';
		$value = preg_replace( $pattern, '$1$2', $value );

		# Check for remote and executable CSS. Unlike in Sanitizer::checkCss, the CSS
		# properties filter and accelerator don't seem to be useful for xss in SVG files.
		# Expression and -o-link don't seem to work either, but filtering them here in case.
		# Additionally, we catch remote urls like url("http:..., url('http:..., url(http:...,
		# but not local ones such as url("#..., url('#..., url(#....
		if ( preg_match( '!expression
				| -o-link\s*:
				| -o-link-source\s*:
				| -o-replace\s*:!imx', $value ) ) {
			return true;
		}

		if ( preg_match_all(
				"!(\s*(url|image|image-set)\s*\(\s*[\"']?\s*[^#]+.*?\))!sim",
				$value,
				$matches
			) !== 0
		) {
			# TODO: redo this in one regex. Until then, url("#whatever") matches the first
			foreach ( $matches[1] as $match ) {
				if ( !preg_match( "!\s*(url|image|image-set)\s*\(\s*(#|'#|\"#)!im", $match ) ) {
					return true;
				}
			}
		}

		return (bool)preg_match( '/[\000-\010\013\016-\037\177]/', $value );
	}
}
