# Configuration Environnement de Test - EMC Groupe

## 1. Préparation Base de Données

### Dump et Import
```bash
# Créer un dump de la base actuelle
mysqldump -u root -p api_groupe_emc > backup_prod_$(date +%Y%m%d_%H%M).sql

# Créer la base de test
mysql -u root -p -e "CREATE DATABASE api_groupe_emc_test;"

# Importer dans la base de test
mysql -u root -p api_groupe_emc_test < backup_prod_$(date +%Y%m%d_%H%M).sql
```

## 2. Configuration Application Test

### Fichier .env pour l'environnement test
```bash
# Copier le projet
cp -r /path/to/api.groupe-emc.com /path/to/test.groupe-emc.com

# Modifier le .env de test
cd /path/to/test.groupe-emc.com
cp .env .env.test

# Éditer .env avec les paramètres de test :
APP_ENV=test
DATABASE_URL="mysql://user:pass@127.0.0.1:3306/api_groupe_emc_test"

# URLs et domaines
CORS_ALLOW_ORIGIN='^https?://(test\.groupe-emc\.com|localhost)(:[0-9]+)?$'
```

## 3. Sécurisation des Données

### Script de nettoyage des données sensibles
```sql
-- Anonymisation des données personnelles pour la démo
UPDATE customer SET
    email = CONCAT('client', id, '@test-emc.com'),
    telephone = CONCAT('06.00.00.', LPAD(id, 2, '0'), '.', LPAD(id, 2, '0'));

UPDATE user SET
    email = CASE
        WHEN id = 1 THEN 'superadmin@test-emc.com'
        WHEN id = 68 THEN 'thomas.vidal@test-emc.com'
        WHEN id = 69 THEN 'amelie.guillot@test-emc.com'
        ELSE CONCAT('user', id, '@test-emc.com')
    END;

-- Réinitialiser tous les mots de passe avec "demo123"
UPDATE user SET password = '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
```

## 4. Configuration Serveur Web

### Apache Virtual Host
```apache
<VirtualHost *:80>
    ServerName test.groupe-emc.com
    DocumentRoot /path/to/test.groupe-emc.com/public

    <Directory /path/to/test.groupe-emc.com/public>
        AllowOverride All
        Require all granted

        # Headers pour CORS
        Header always set Access-Control-Allow-Origin "*"
        Header always set Access-Control-Allow-Methods "GET,POST,PUT,DELETE,OPTIONS"
        Header always set Access-Control-Allow-Headers "Content-Type,Authorization"
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/test-emc_error.log
    CustomLog ${APACHE_LOG_DIR}/test-emc_access.log combined
</VirtualHost>
```

### Nginx (alternative)
```nginx
server {
    listen 80;
    server_name test.groupe-emc.com;
    root /path/to/test.groupe-emc.com/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }

    # CORS headers
    add_header Access-Control-Allow-Origin *;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS";
    add_header Access-Control-Allow-Headers "Content-Type, Authorization";
}
```

## 5. Script Automatisé de Déploiement Test

### deploy_test.sh
```bash
#!/bin/bash

echo "=== Déploiement Environnement Test EMC Groupe ==="

# Variables
PROD_PATH="/path/to/api.groupe-emc.com"
TEST_PATH="/path/to/test.groupe-emc.com"
DB_PROD="api_groupe_emc"
DB_TEST="api_groupe_emc_test"
BACKUP_DIR="/backups"

# 1. Backup base production
echo "1. Sauvegarde base production..."
mysqldump -u root -p$DB_PASSWORD $DB_PROD > $BACKUP_DIR/prod_backup_$(date +%Y%m%d_%H%M).sql

# 2. Drop et recréer base test
echo "2. Recréation base test..."
mysql -u root -p$DB_PASSWORD -e "DROP DATABASE IF EXISTS $DB_TEST;"
mysql -u root -p$DB_PASSWORD -e "CREATE DATABASE $DB_TEST;"

# 3. Import en base test
echo "3. Import base test..."
mysql -u root -p$DB_PASSWORD $DB_TEST < $BACKUP_DIR/prod_backup_$(date +%Y%m%d_%H%M).sql

# 4. Copie fichiers
echo "4. Copie fichiers application..."
rsync -av --delete --exclude='var/cache/*' --exclude='var/log/*' $PROD_PATH/ $TEST_PATH/

# 5. Configuration test
echo "5. Configuration environnement test..."
cd $TEST_PATH
cp .env .env.backup
sed -i 's/APP_ENV=prod/APP_ENV=test/' .env
sed -i "s/$DB_PROD/$DB_TEST/" .env

# 6. Nettoyage cache
echo "6. Nettoyage cache..."
php bin/console cache:clear --env=test
chmod -R 777 var/

# 7. Anonymisation données
echo "7. Anonymisation données sensibles..."
mysql -u root -p$DB_PASSWORD $DB_TEST << 'EOF'
UPDATE customer SET
    email = CONCAT('client', id, '@test-emc.com'),
    telephone = CONCAT('06.00.00.', LPAD(id, 2, '0'), '.', LPAD(id, 2, '0'));

UPDATE user SET
    email = CASE
        WHEN roles LIKE '%SUPER_ADMIN%' THEN 'superadmin@test-emc.com'
        WHEN roles LIKE '%PROFIL_B%' THEN CONCAT('manager', id, '@test-emc.com')
        ELSE CONCAT('user', id, '@test-emc.com')
    END,
    password = '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
EOF

echo "=== Déploiement terminé ==="
echo "URL test: http://test.groupe-emc.com"
echo "Tous les comptes utilisent le mot de passe: demo123"
```

## 6. Documentation Client

### Accès Démo
- **URL**: http://test.groupe-emc.com
- **Mot de passe universel**: `demo123`

### Comptes de Test
- **Super Admin**: superadmin@test-emc.com
- **Manager Paris**: manager10@test-emc.com
- **Manager Lyon**: manager11@test-emc.com
- **Commercial**: user68@test-emc.com (Thomas Vidal)
- **Commercial**: user69@test-emc.com (Amélie Guillot)

### Fonctionnalités Testables
✅ Dashboard avec statistiques temps réel
✅ Gestion permissions par rôle
✅ Modification ventes selon statut
✅ Interface de modification avancée
✅ Système de binômes
✅ Gestion rendez-vous
✅ Upload d'images
✅ Rapports et exports

## 7. Maintenance

### Mise à jour de l'environnement test
```bash
# Script à exécuter après chaque mise à jour prod
./deploy_test.sh
```

### Reset données test
```bash
# En cas de données corrompues en test
mysql -u root -p api_groupe_emc_test < /backups/clean_test_data.sql
```

## 8. Sécurité

⚠️ **Important**:
- L'environnement test ne doit JAMAIS contenir de vraies données clients
- Utiliser des données anonymisées uniquement
- Mettre un bandeau "ENVIRONNEMENT DE TEST" sur l'interface
- Bloquer l'envoi d'emails depuis l'environnement test