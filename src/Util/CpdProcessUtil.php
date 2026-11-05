<?php

namespace CognitiveProcessDesigner\Util;

use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\User;
use PermissionsError;

class CpdProcessUtil {

	public const DUMMY_PAGE_NAME = 'Dummy';

	/**
	 * @param PermissionManager $permissionManager
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly PermissionManager $permissionManager,
		private readonly TitleFactory $titleFactory
	) {
	}

	/**
	 * @param User $user
	 * @param string $permission
	 *
	 * @return bool
	 */
	public function hasPermission( User $user, string $permission = 'read' ): bool {
		return $this->permissionManager->userCan( $permission, $user, $this->getProcessDummyPage() );
	}

	/**
	 * @throws PermissionsError
	 */
	public function throwPermissionErrors( User $user, string $permission = 'read' ): void {
		$this->permissionManager->throwPermissionErrors(
			$permission,
			$user,
			$this->getProcessDummyPage()
		);
	}

	/**
	 * @return Title
	 */
	public function getProcessDummyPage(): Title {
		return $this->titleFactory->newFromText( self::DUMMY_PAGE_NAME, NS_PROCESS );
	}
}
