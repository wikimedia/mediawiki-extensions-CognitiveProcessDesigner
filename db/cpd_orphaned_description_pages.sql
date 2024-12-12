CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/cpd_orphaned_description_pages(
	`page_title` VARCHAR(255) NOT NULL,
	`process` VARCHAR(255) NOT NULL COLLATE utf8_general_ci
) /*$wgDBTableOptions*/;
