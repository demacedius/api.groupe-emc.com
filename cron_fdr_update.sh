#!/bin/bash
# Cron job pour mettre à jour automatiquement les statuts FDR
# À exécuter tous les jours à 2h du matin
# Ajouter dans crontab: 0 2 * * * /path/to/cron_fdr_update.sh

cd /Users/demacedoanthony/emx-groupe/api.groupe-emc.com
/usr/bin/php bin/console app:update-fdr-status >> var/log/fdr_update.log 2>&1