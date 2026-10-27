ALTER TABLE client MODIFY COLUMN id INT NOT NULL;
CREATE TABLE IF NOT EXISTS klientu_importi (
    nr INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    izveidots DATETIME NOT NULL,
    darbinieks_id INT NOT NULL,
    izpilditajs VARCHAR(100) NOT NULL,
    faila_nosaukums VARCHAR(255) NOT NULL,
    faila_sha256 CHAR(64) NOT NULL,
    kopskaits INT NOT NULL,
    pievienoti INT NOT NULL,
    atjauninati INT NOT NULL,
    nemainiti INT NOT NULL,
    pieprasijums CHAR(64) NOT NULL,
    UNIQUE KEY uq_klientu_importi_pieprasijums (pieprasijums)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
