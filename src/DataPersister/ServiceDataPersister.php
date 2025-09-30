<?php

namespace App\DataPersister;

use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ServiceDataPersister implements ContextAwareDataPersisterInterface
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
        return $data instanceof Service;
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
        if (count($data->getSellitems()) > 0) {
            throw new BadRequestHttpException("Une prestation utilisée par des ventes ne peut pas être supprimée.");
        }
        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }
}