<?php

namespace App\DataPersister;

use App\Entity\ServiceCategory;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ServiceCategoryDataPersister implements ContextAwareDataPersisterInterface
{
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof ServiceCategory;
    }
    
    /**
     * @param Service $data
     */
    public function persist($data, array $context = [])
    {
        $this->entityManager->persist($data);
        $this->entityManager->flush();
    }

    public function remove($data, array $context = [])
    {
        if (count($data->getServices()) > 0) {
            throw new BadRequestHttpException("Une catégorie de prestations utilisée ne peut pas être supprimée.");
        }
        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }
}