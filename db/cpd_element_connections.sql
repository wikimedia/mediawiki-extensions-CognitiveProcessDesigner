CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/cpd_element_connections(
    `from_page` VARBINARY(255) NOT NULL,
    `to_page` VARBINARY(255) NOT NULL,
    `process` VARBINARY(255) NOT NULL
) /*$wgDBTableOptions*/;
