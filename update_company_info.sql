-- Mise à jour des informations légales des sociétés EMX Groupe
-- Date: 07/09/2025

-- Mise à jour FPEMC (France Patrimoine EMC)
UPDATE company 
SET legal = 'RCS CRETEIL 801 442 658 - CODE APE 70227 - TVA INTRACOMMUNAUTAIRE : FR44 801 442 658',
    name = 'France Patrimoine EMC'
WHERE id = 1 AND prefix = 'FP';

-- Mise à jour Mon Patrimoine avec nouvelle adresse et informations légales
UPDATE company 
SET legal = 'RCS 877 566 562 00022 - CODE APE 4391B - TVA INTRACOMMUNAUTAIRE : FR00877566562',
    address = '13 rue du belvédère',
    postcode = '94430',
    city = 'Chennevières sur Marne',
    name = 'Mon Patrimoine'
WHERE id = 2 AND prefix = 'MP';

-- Vérification des mises à jour
SELECT id, name, prefix, address, postcode, city, legal 
FROM company 
WHERE id IN (1, 2)
ORDER BY id;