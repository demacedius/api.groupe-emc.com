<?php
/**
 * Script pour créer un utilisateur Profile B de test
 * À exécuter depuis la racine du projet API
 */

require_once './vendor/autoload.php';

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

// Bootstrap Symfony
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

/** @var EntityManagerInterface $em */
$em = $container->get('doctrine.orm.entity_manager');

/** @var UserPasswordHasherInterface $passwordHasher */
try {
    $passwordHasher = $container->get('security.user_password_hasher');
} catch (Exception $e) {
    // Fallback pour les versions plus récentes de Symfony
    $passwordHasher = $container->get('security.password_hasher');
}

// Créer l'utilisateur Profile B
$profilBUser = new User();
$profilBUser->setFirstname('Manager');
$profilBUser->setLastname('Profile B');
$profilBUser->setEmail('manager-b@emx-groupe.com');
$profilBUser->setRoles(['ROLE_PROFIL_B']);
$profilBUser->setEnabled(true);
$profilBUser->setBinomialAllowed(false);

// Hasher le mot de passe
$hashedPassword = $passwordHasher->hashPassword($profilBUser, 'password123');
$profilBUser->setPassword($hashedPassword);

// Vérifier s'il existe déjà
$existingUser = $em->getRepository(User::class)->findOneBy(['email' => 'manager-b@emx-groupe.com']);

if ($existingUser) {
    echo "✅ Utilisateur Profile B existe déjà : " . $existingUser->getFirstname() . " " . $existingUser->getLastname() . " (" . $existingUser->getEmail() . ")\n";
} else {
    // Sauvegarder
    $em->persist($profilBUser);
    $em->flush();

    echo "✅ Utilisateur Profile B créé avec succès !\n";
    echo "Email: manager-b@emx-groupe.com\n";
    echo "Mot de passe: password123\n";
    echo "Rôle: ROLE_PROFIL_B\n";
}

// Créer aussi quelques utilisateurs commerciaux pour les tests
$commercialUsers = [
    ['firstname' => 'Jean', 'lastname' => 'Commercial1', 'email' => 'jean.commercial1@emx-groupe.com'],
    ['firstname' => 'Marie', 'lastname' => 'Commercial2', 'email' => 'marie.commercial2@emx-groupe.com'],
    ['firstname' => 'Pierre', 'lastname' => 'Commercial3', 'email' => 'pierre.commercial3@emx-groupe.com']
];

foreach ($commercialUsers as $userData) {
    $existingCommercial = $em->getRepository(User::class)->findOneBy(['email' => $userData['email']]);

    if (!$existingCommercial) {
        $commercial = new User();
        $commercial->setFirstname($userData['firstname']);
        $commercial->setLastname($userData['lastname']);
        $commercial->setEmail($userData['email']);
        $commercial->setRoles(['ROLE_SALES']);
        $commercial->setEnabled(true);
        $commercial->setBinomialAllowed(true);

        // Assigner le manager Profile B
        $commercial->setManager($profilBUser);

        // Hasher le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($commercial, 'password123');
        $commercial->setPassword($hashedPassword);

        $em->persist($commercial);

        echo "✅ Utilisateur commercial créé : " . $userData['firstname'] . " " . $userData['lastname'] . "\n";
    } else {
        echo "ℹ️ Utilisateur commercial existe déjà : " . $userData['firstname'] . " " . $userData['lastname'] . "\n";
    }
}

$em->flush();

echo "\n🎉 Configuration Profile B terminée !\n";
echo "Vous pouvez maintenant vous connecter avec :\n";
echo "- Email: manager-b@emx-groupe.com\n";
echo "- Mot de passe: password123\n";
echo "\nLes commerciaux affiliés :\n";
foreach ($commercialUsers as $userData) {
    echo "- " . $userData['email'] . " (password123)\n";
}