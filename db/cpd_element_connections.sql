CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/cpd_element_connections(
    `from_page` VARCHAR(255) NOT NULL,
    `to_page` VARCHAR(255) NOT NULL,
    `process` VARCHAR(255) NOT NULL
) /*$wgDBTableOptions*/;
