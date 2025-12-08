CREATE TABLE IF NOT EXISTS llx_expedition_payment (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    entity INT NOT NULL DEFAULT 1,
    ref VARCHAR(32) NOT NULL,
    fk_soc INT NOT NULL,
    amount DOUBLE(24,8) NOT NULL,
    datep DATETIME NOT NULL,
    mode VARCHAR(16) NULL,
    num_payment VARCHAR(64) NULL,
    note TEXT NULL,
    status SMALLINT NOT NULL DEFAULT 1, -- 1=validated, 0=draft, 9=cancelled
    fk_user_creat INT NULL,
    fk_user_valid INT NULL,
    fk_user_cancel INT NULL,
    datec DATETIME NULL,
    datev DATETIME NULL,
    date_cancel DATETIME NULL,
    tms TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_expedition_payment_ref (ref, entity),
    INDEX idx_expedition_payment_entity (entity),
    INDEX idx_expedition_payment_soc (fk_soc)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_expedition_payment_expedition (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    fk_payment INT NOT NULL,
    fk_expedition INT NOT NULL,
    amount DOUBLE(24,8) NOT NULL,
    entity INT NOT NULL DEFAULT 1,
    INDEX idx_expedition_payment_exp_entity (entity),
    INDEX idx_expedition_payment_exp_pay (fk_payment),
    INDEX idx_expedition_payment_exp_expedition (fk_expedition),
    CONSTRAINT fk_expedition_payment FOREIGN KEY (fk_payment) REFERENCES llx_expedition_payment (rowid) ON DELETE CASCADE
) ENGINE=innodb;
