-- Izpildīt vienreiz pirms atjauninātā klientu API izvietošanas.
-- Esošie un turpmāk importētie jaunie klienti sākotnēji ir aktīvi.
ALTER TABLE client ADD COLUMN act TINYINT(1) NOT NULL DEFAULT 1;
