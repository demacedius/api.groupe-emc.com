<?php

namespace App\EventListener;

use App\Entity\Customer;
use App\Service\PostalCodeExtractor;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;

class CustomerPostalCodeListener implements EventSubscriber
{
    private PostalCodeExtractor $postalCodeExtractor;

    public function __construct(PostalCodeExtractor $postalCodeExtractor)
    {
        $this->postalCodeExtractor = $postalCodeExtractor;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->extractAndSetClientCode($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->extractAndSetClientCode($args);
    }

    private function extractAndSetClientCode(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Customer) {
            return;
        }

        // Si le client n'a pas encore de code client ou si l'adresse/code postal a changé
        $address = $entity->getAddress();
        $postcode = $entity->getPostcode();

        if ((empty($entity->getClientCode()) || $this->addressHasChanged($args) || $this->postcodeHasChanged($args))) {
            $extractedPostalCode = null;

            // Essayer d'extraire de l'adresse d'abord
            if ($address) {
                $extractedPostalCode = $this->postalCodeExtractor->extractFromAddress($address);
            }

            // Si pas trouvé dans l'adresse, utiliser le champ postcode existant comme fallback
            if (!$extractedPostalCode && $postcode && $this->postalCodeExtractor->isValidFrenchPostalCode($postcode)) {
                $extractedPostalCode = $postcode;
            }

            if ($extractedPostalCode) {
                $entity->setClientCode($extractedPostalCode);
            }
        }
    }

    private function addressHasChanged(LifecycleEventArgs $args): bool
    {
        if ($args->hasChangedField('address')) {
            return true;
        }

        return false;
    }

    private function postcodeHasChanged(LifecycleEventArgs $args): bool
    {
        if ($args->hasChangedField('postcode')) {
            return true;
        }

        return false;
    }
}