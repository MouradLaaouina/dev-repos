CREATE TABLE llx_btoc_stats (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    date_creation datetime NOT NULL,
    action varchar(255) NOT NULL,
    user_id integer,
    details text,
    entity integer DEFAULT 1
) ENGINE=innodb;
