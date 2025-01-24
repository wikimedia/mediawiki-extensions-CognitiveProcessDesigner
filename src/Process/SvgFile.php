<?php

namespace CognitiveProcessDesigner\Process;

use CognitiveProcessDesigner\Exceptions\CpdSvgException;
use MediaHandler;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Message;
use MimeAnalyzer;
use MWFileProps;
use RepoGroup;
use SpecialUpload;
use TempFSFile;
use Wikimedia\AtEase\AtEase;

class SvgFile {
	/**
	 * @var MimeAnalyzer
	 */
	private $mimeAnalyzer;

	/**
	 * @var RepoGroup
	 */
	private $repoGroup;

	/**
	 * @param MimeAnalyzer $mimeAnalyzer
	 * @param RepoGroup $repoGroup
	 */
	public function __construct(
		MimeAnalyzer $mimeAnalyzer,
		RepoGroup $repoGroup
	) {
		$this->mimeAnalyzer = $mimeAnalyzer;
		$this->repoGroup = $repoGroup;
	}

	/**
	 * @param Title $svgFile
	 * @param string $svg
	 * @param User $user
	 *
	 * @return void
	 * @throws CpdSvgException
	 */
	public function save( Title $svgFile, string $svg, User $user ): void {
		$filename = $svgFile->getDBkey();
		$tempFilePath = TempFSFile::getUsableTempDirectory() . '/' . $filename;
		if ( !file_put_contents( $tempFilePath, $svg ) ) {
			throw new CpdSvgException( 'Could not save SVG file' );
		}

		$repo = $this->repoGroup->getLocalRepo();
		$repoFile = $repo->newFile( $svgFile );

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
				Message::newFromKey( 'cpd-error-message-publish-svg-file', $status->getErrors()[0]['message'] )
			);
		}

		$commentText = SpecialUpload::getInitialPageText();
		$status = $repoFile->recordUpload3( $status->value, '', $commentText, $user, $props );

		if ( !$status->isOK() ) {
			throw new CpdSvgException(
				Message::newFromKey( 'cpd-error-message-publish-svg-file', $status->getErrors()[0]['message'] )
			);
		}
	}
}
