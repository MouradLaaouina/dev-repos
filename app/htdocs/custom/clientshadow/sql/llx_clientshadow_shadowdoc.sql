CREATE TABLE IF NOT EXISTS llx_clientshadow_shadowdoc (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    entity INT NOT NULL,
    fk_object INT NOT NULL,
    element_type VARCHAR(32) NOT NULL,
    fk_soc INT NOT NULL,
    shadow_state VARCHAR(20) NOT NULL DEFAULT 'shadow',
    label VARCHAR(255),
    amount DOUBLE(24,8) DEFAULT 0,
    datec DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=innodb;
