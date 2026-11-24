-- Izpildīt pirms app/auth.php ar datubāzes sesiju apstrādi izvietošanas.
-- Sesijas ir īslaicīgas: beigu laiks tiek pagarināts katrā lietotāja pieprasījumā.
CREATE TABLE IF NOT EXISTS darba_web_sesijas (
    sesijas_id VARCHAR(128) NOT NULL PRIMARY KEY,
    saturs MEDIUMBLOB NOT NULL,
    beidzas DATETIME NOT NULL,
    KEY idx_darba_web_sesijas_beidzas (beidzas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
