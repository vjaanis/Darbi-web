-- Izpilda ar Darba-web datubāzes pieslēgumu. Esošos darbus nemaina.
CREATE TABLE IF NOT EXISTS eksporti (
    nr INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    izveidots DATETIME NOT NULL,
    darbinieks_id INT NOT NULL,
    izpilditajs VARCHAR(100) NOT NULL,
    ierakstu_skaits INT NOT NULL,
    faila_nosaukums VARCHAR(100) NOT NULL,
    csv_saturs LONGBLOB NOT NULL,
    sha256 CHAR(64) NOT NULL,
    darbu_numuri LONGTEXT NOT NULL,
    pieprasijums CHAR(32) NOT NULL,
    atlases_hash CHAR(64) NOT NULL,
    UNIQUE KEY uq_eksporti_pieprasijums (pieprasijums),
    KEY idx_eksporti_datums (izveidots)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE darbi ADD COLUMN IF NOT EXISTS eksportets TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE darbi ADD COLUMN IF NOT EXISTS eksporta_nr INT NULL;
ALTER TABLE darbi ADD COLUMN IF NOT EXISTS eksporta_datums DATETIME NULL;
CREATE INDEX IF NOT EXISTS idx_darbi_eksports ON darbi (eksportets, darbinieks_id, nr);
