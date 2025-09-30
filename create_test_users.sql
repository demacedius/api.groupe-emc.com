-- Profil B (Manager)
INSERT INTO user (email, password, firstname, lastname, roles, enabled, created_date, binomialAllowed)
VALUES (
    'manager@test.com',
    '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: "password"
    'Manager',
    'ProfilB',
    '["ROLE_PROFIL_B"]',
    1,
    NOW(),
    1
);

-- Commercial (Utilisateur commercial)
INSERT INTO user (email, password, firstname, lastname, roles, enabled, created_date, binomialAllowed, manager_id)
VALUES (
    'commercial@test.com',
    '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: "password"
    'Commercial',
    'Vendeur',
    '["ROLE_SALES"]',
    1,
    NOW(),
    1,
    (SELECT id FROM user WHERE email = 'manager@test.com' LIMIT 1)
);