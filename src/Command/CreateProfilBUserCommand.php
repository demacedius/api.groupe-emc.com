<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-profil-b-user',
    description: 'Create Profile B user for testing'
)]
class CreateProfilBUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Créer l'utilisateur Profile B
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'manager-b@emx-groupe.com']);

        if ($existingUser) {
            $io->success('Utilisateur Profile B existe déjà : ' . $existingUser->getFirstname() . ' ' . $existingUser->getLastname() . ' (' . $existingUser->getEmail() . ')');
            $profilBUser = $existingUser;
        } else {
            $profilBUser = new User();
            $profilBUser->setFirstname('Manager');
            $profilBUser->setLastname('Profile B');
            $profilBUser->setEmail('manager-b@emx-groupe.com');
            $profilBUser->setRoles(['ROLE_PROFIL_B']);
            $profilBUser->setEnabled(true);
            $profilBUser->setBinomialAllowed(false);

            // Hasher le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($profilBUser, 'password123');
            $profilBUser->setPassword($hashedPassword);

            $this->entityManager->persist($profilBUser);
            $this->entityManager->flush();

            $io->success('Utilisateur Profile B créé avec succès !');
        }

        // Créer des utilisateurs commerciaux pour les tests
        $commercialUsers = [
            ['firstname' => 'Jean', 'lastname' => 'Commercial1', 'email' => 'jean.commercial1@emx-groupe.com'],
            ['firstname' => 'Marie', 'lastname' => 'Commercial2', 'email' => 'marie.commercial2@emx-groupe.com'],
            ['firstname' => 'Pierre', 'lastname' => 'Commercial3', 'email' => 'pierre.commercial3@emx-groupe.com']
        ];

        foreach ($commercialUsers as $userData) {
            $existingCommercial = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userData['email']]);

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
                $hashedPassword = $this->passwordHasher->hashPassword($commercial, 'password123');
                $commercial->setPassword($hashedPassword);

                $this->entityManager->persist($commercial);

                $io->success('Utilisateur commercial créé : ' . $userData['firstname'] . ' ' . $userData['lastname']);
            } else {
                $io->info('Utilisateur commercial existe déjà : ' . $userData['firstname'] . ' ' . $userData['lastname']);
            }
        }

        $this->entityManager->flush();

        $io->success('Configuration Profile B terminée !');
        $io->section('Identifiants de connexion :');
        $io->table(
            ['Rôle', 'Email', 'Mot de passe'],
            [
                ['Profile B Manager', 'manager-b@emx-groupe.com', 'password123'],
                ['Commercial 1', 'jean.commercial1@emx-groupe.com', 'password123'],
                ['Commercial 2', 'marie.commercial2@emx-groupe.com', 'password123'],
                ['Commercial 3', 'pierre.commercial3@emx-groupe.com', 'password123']
            ]
        );

        return Command::SUCCESS;
    }
}