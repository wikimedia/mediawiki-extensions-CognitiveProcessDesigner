<?php

namespace CognitiveProcessDesigner\Rest;

use MediaHandler;
use MediaWiki\Context\RequestContext;
use MediaWiki\Rest\Handler;
use MediaWiki\Specials\SpecialUpload;
use MediaWiki\Title\Title;
use MWFileProps;
use RepoGroup;
use Wikimedia\FileBackend\FSFile\TempFSFile;
use Wikimedia\Mime\MimeAnalyzer;
use Wikimedia\ParamValidator\ParamValidator;

class SaveSvgHandler extends Handler {

	/**
	 * @param MimeAnalyzer $mimeAnalyzer
	 * @param RepoGroup $repoGroup
	 */
	public function __construct( private readonly MimeAnalyzer $mimeAnalyzer, private readonly RepoGroup $repoGroup ) {
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$params = $this->getValidatedParams();

		$user = RequestContext::getMain()->getUser();

		$tempFilePath = TempFSFile::getUsableTempDirectory() . '/' . $params['filename'];
		if ( !file_put_contents( $tempFilePath, $params['svgContent'] ) ) {
			return $this->getResponseFactory()->createJson( [
				'success' => false,
				'error' => "Could not create temporary file: '$tempFilePath'"
			] );
		}

		// MediaWiki normalizes multiple spaces/undescores into one single score/underscore
		$title = str_replace( ' ', '_', $params['filename'] );
		$title = preg_replace( '#(_)+#si', '_', $title );

		$targetTitle = Title::makeTitle( NS_FILE, $title );
		$repo = $this->repoGroup->getLocalRepo();

		$repoFile = $repo->newFile( $targetTitle );

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
				// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
				$metadata = @unserialize( $props['metadata'] );
			}

			$publishOptions['headers'] = $handler->getContentHeaders( $metadata );
		} else {
			$publishOptions['headers'] = [];
		}
		$archive = $repoFile->publish( $tempFilePath, $flags, $publishOptions );
		if ( !$archive->isGood() ) {
			return $this->getResponseFactory()->createJson( [
				'success' => false,
				'error' => 'Error when publishing file'
			] );
		}

		$commentText = SpecialUpload::getInitialPageText();

		$status = $repoFile->recordUpload3( $archive->value, '', $commentText, $user, $props );
		if ( !$status->isGood() ) {
			// Check for case if file already exists. Then no error here
			if ( $status->getMessages()[0]['message'] !== 'fileexists-no-change' ) {
				return $this->getResponseFactory()->createJson( [
					'success' => false,
					'error' => 'Error when recording file upload'
				] );
			}
		}

		// Get and return to frontend information about image
		$file = $repo->findBySha1( $props['sha1'] )[0];

		$imageInfo = [
			'height' => $file->getHeight(),
			'width' => $file->getWidth(),
			'url' => $file->getCanonicalUrl(),
			'timestamp' => $file->getTimestamp()
		];

		return $this->getResponseFactory()->createJson( [
			'success' => true,
			'imageInfo' => $imageInfo
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		return [
			'filename' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'svgContent' => [
				static::PARAM_SOURCE => 'post',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}
