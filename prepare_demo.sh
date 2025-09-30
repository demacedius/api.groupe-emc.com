#!/bin/bash

# =================================================================
# SCRIPT DE PRÉPARATION ENVIRONNEMENT DÉMO - EMC GROUPE
# =================================================================

echo "🚀 Préparation environnement de démonstration EMC Groupe"
echo "========================================================"

# Variables (à adapter selon votre configuration)
DB_NAME="api_groupe_emc"
DB_TEST="api_groupe_emc_test"
DB_USER="root"
BACKUP_DIR="./backups"

# Créer dossier backup si nécessaire
mkdir -p $BACKUP_DIR

echo ""
echo "📊 1. Sauvegarde base de données actuelle..."
mysqldump -u $DB_USER -p $DB_NAME > $BACKUP_DIR/backup_prod_$(date +%Y%m%d_%H%M).sql
if [ $? -eq 0 ]; then
    echo "✅ Backup créé : $BACKUP_DIR/backup_prod_$(date +%Y%m%d_%H%M).sql"
else
    echo "❌ Erreur lors du backup"
    exit 1
fi

echo ""
echo "🗃️ 2. Création base de données test..."
mysql -u $DB_USER -p -e "DROP DATABASE IF EXISTS $DB_TEST;"
mysql -u $DB_USER -p -e "CREATE DATABASE $DB_TEST;"
if [ $? -eq 0 ]; then
    echo "✅ Base test créée : $DB_TEST"
else
    echo "❌ Erreur lors de la création de la base test"
    exit 1
fi

echo ""
echo "📥 3. Import des données dans la base test..."
mysql -u $DB_USER -p $DB_TEST < $BACKUP_DIR/backup_prod_$(date +%Y%m%d_%H%M).sql
if [ $? -eq 0 ]; then
    echo "✅ Données importées dans la base test"
else
    echo "❌ Erreur lors de l'import"
    exit 1
fi

echo ""
echo "🔒 4. Anonymisation des données sensibles..."
mysql -u $DB_USER -p $DB_TEST << 'EOF'

-- Anonymisation des clients
UPDATE customer SET
    email = CONCAT('client', id, '@demo-emc.com'),
    telephone = CONCAT('06.00.', LPAD(id, 2, '0'), '.', LPAD(id, 2, '0'), '.', LPAD(id, 2, '0'));

-- Anonymisation et standardisation des utilisateurs
UPDATE user SET
    email = CASE
        WHEN roles LIKE '%ROLE_SUPER_ADMIN%' THEN 'superadmin@demo-emc.com'
        WHEN roles LIKE '%ROLE_ADMIN%' THEN CONCAT('admin', id, '@demo-emc.com')
        WHEN roles LIKE '%ROLE_PROFIL_B%' THEN CONCAT('manager', id, '@demo-emc.com')
        WHEN roles LIKE '%ROLE_SUPER_SALES%' THEN CONCAT('supersales', id, '@demo-emc.com')
        ELSE CONCAT('commercial', id, '@demo-emc.com')
    END,
    -- Mot de passe = "demo123"
    password = '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    reset_token = NULL,
    reset_exp = NULL;

-- Nettoyage des tokens de reset
UPDATE user SET reset_token = NULL, reset_exp = NULL;

EOF

if [ $? -eq 0 ]; then
    echo "✅ Données anonymisées"
else
    echo "❌ Erreur lors de l'anonymisation"
    exit 1
fi

echo ""
echo "⚙️ 5. Configuration Symfony pour la démo..."

# Créer fichier .env.demo
cat > .env.demo << 'EOF'
# Configuration Environnement DEMO - EMC Groupe
APP_ENV=demo
APP_SECRET=change_me_for_demo
DATABASE_URL="mysql://root:password@127.0.0.1:3306/api_groupe_emc_test?serverVersion=8.0"

# CORS pour démo (plus permissif)
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1|test\.groupe-emc\.com)(:[0-9]+)?$'

# JWT (garder les mêmes clés pour éviter les problèmes)
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase

# Mailer désactivé pour la démo
MAILER_DSN=null://null
EOF

echo "✅ Fichier .env.demo créé"

echo ""
echo "🧹 6. Nettoyage cache..."
php bin/console cache:clear --env=demo
chmod -R 755 var/

echo ""
echo "🎯 7. Génération du rapport de comptes de test..."

# Créer rapport des comptes
mysql -u $DB_USER -p $DB_TEST -e "
SELECT
    'COMPTES DEMO DISPONIBLES' as info,
    '' as email,
    '' as role,
    '' as mot_de_passe
UNION ALL
SELECT
    '=======================',
    '',
    '',
    ''
UNION ALL
SELECT
    CONCAT(firstname, ' ', lastname) as nom,
    email,
    JSON_UNQUOTE(JSON_EXTRACT(roles, '$[0]')) as role,
    'demo123' as mot_de_passe
FROM user
WHERE enabled = 1
ORDER BY
    CASE
        WHEN email LIKE '%superadmin%' THEN 1
        WHEN email LIKE '%admin%' THEN 2
        WHEN email LIKE '%manager%' THEN 3
        WHEN email LIKE '%supersales%' THEN 4
        ELSE 5
    END;
" > comptes_demo.txt

echo ""
echo "🎉 ENVIRONNEMENT DEMO PRÊT !"
echo "============================"
echo ""
echo "📋 Résumé :"
echo "• Base de données test : $DB_TEST"
echo "• Backup original : $BACKUP_DIR/backup_prod_$(date +%Y%m%d_%H%M).sql"
echo "• Configuration : .env.demo"
echo "• Mot de passe universel : demo123"
echo ""
echo "📄 Liste des comptes disponibles :"
cat comptes_demo.txt
echo ""
echo "🌐 Pour lancer la démo :"
echo "   php -S localhost:8080 -t public"
echo "   ou configurer un virtual host pointant vers ce dossier"
echo ""
echo "⚠️  IMPORTANT :"
echo "   - Utilisez .env.demo pour la configuration"
echo "   - Les données sont anonymisées"
echo "   - Mot de passe universel : demo123"
echo ""