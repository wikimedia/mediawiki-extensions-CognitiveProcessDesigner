<?php

namespace CognitiveProcessDesigner\Process;

use CognitiveProcessDesigner\Exceptions\CpdSvgException;
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
}
