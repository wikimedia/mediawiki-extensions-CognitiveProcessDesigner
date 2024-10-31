CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/cpd_orphaned_description_pages(
	`page_title` VARBINARY(255) NOT NULL,
	`process` VARBINARY(255) NOT NULL
) /*$wgDBTableOptions*/;
