<?php

require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\Company;

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$entityManager = $kernel->getContainer()->get('doctrine')->getManager();
$companyRepository = $entityManager->getRepository(Company::class);

$companies = $companyRepository->findAll();

if (empty($companies)) {
    echo "Aucune agence (Company) n'a été trouvée en base de données.\n";
} else {
    echo "Voici les agences (Companies) existantes :\n";
    foreach ($companies as $company) {
        echo sprintf("- ID: %d, Nom: %s\n", $company->getId(), $company->getName());
    }
}
