CREATE TABLE IF NOT EXISTS llx_categorydiscount_rule (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    entity integer NOT NULL DEFAULT 1,
    fk_soc integer NULL,
    fk_categorie integer NOT NULL,
    discount_percent double(24,8) NOT NULL DEFAULT 0,
    datec datetime NULL,
    tms timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    fk_user_creat integer NULL,
    fk_user_modif integer NULL,
    INDEX idx_categorydiscount_entity (entity),
    INDEX idx_categorydiscount_soc (fk_soc),
    INDEX idx_categorydiscount_cat (fk_categorie)
) ENGINE=innodb;
