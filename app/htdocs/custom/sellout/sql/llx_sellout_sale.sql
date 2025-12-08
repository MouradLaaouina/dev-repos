CREATE TABLE IF NOT EXISTS llx_sellout_sale (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    entity INT NOT NULL DEFAULT 1,
    fk_user INT NOT NULL,
    fk_soc INT NOT NULL,
    fk_product INT NOT NULL,
    qty NUMERIC(24,8) NOT NULL DEFAULT 1,
    unit_price NUMERIC(24,8) DEFAULT 0,
    currency_code VARCHAR(3),
    location_label VARCHAR(255),
    location_latitude DECIMAL(10,8),
    location_longitude DECIMAL(11,8),
    sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    source VARCHAR(50),
    note TEXT,
    datec DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tms TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;
CREATE INDEX idx_sellout_entity ON llx_sellout_sale (entity);
CREATE INDEX idx_sellout_soc ON llx_sellout_sale (fk_soc);
CREATE INDEX idx_sellout_product ON llx_sellout_sale (fk_product);
CREATE INDEX idx_sellout_user ON llx_sellout_sale (fk_user);
CREATE INDEX idx_sellout_date ON llx_sellout_sale (sale_date);
