<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity()
 */
#[ApiResource(
    attributes: ['security' => 'user.enabled == true'],
    collectionOperations: [
        'get' => [
            'pagination_enabled' => false,
            'normalization_context' => ['groups' => ['package:read']],
            'security' => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_SUPER_SALES')) and user.enabled == true"
        ],
        'post' => [
            'normalization_context' => ['groups' => ['package:read']],
            'denormalization_context' => ['groups' => ['package:write']],
            'security' => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')) and user.enabled == true"
        ]
    ],
    itemOperations: [
        'get' => [
            'normalization_context' => ['groups' => ['package:read']],
        ],
        'put' => [
            'normalization_context' => ['groups' => ['package:read']],
            'denormalization_context' => ['groups' => ['package:write']],
            'security' => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')) and user.enabled == true"
        ],
        'delete' => [
            'security' => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')) and user.enabled == true"
        ]
    ]
)]
class ServicePackage
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(['package:read'])]
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(['package:read', 'package:write'])]
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(['package:read', 'package:write'])]
    private $description;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=3)
     */
    #[Groups(['package:read', 'package:write'])]
    private $price;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     */
    #[Groups(['package:read', 'package:write'])]
    private $vat;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     */
    #[Groups(['package:read', 'package:write'])]
    private $enabled = true;

    /**
     * @ORM\ManyToMany(targetEntity=Service::class)
     * @ORM\JoinTable(name="service_package_services")
     */
    #[Groups(['package:read', 'package:write'])]
    private $services;

    /**
     * @ORM\Column(type="integer", options={"default": 1})
     */
    #[Groups(['package:read', 'package:write'])]
    private $sortOrder = 1;

    /**
     * @ORM\Column(type="datetime")
     */
    #[Groups(['package:read'])]
    private $createdAt;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getVat(): ?string
    {
        return $this->vat;
    }

    public function setVat(?string $vat): self
    {
        $this->vat = $vat;
        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): self
    {
        if (!$this->services->contains($service)) {
            $this->services[] = $service;
        }
        return $this;
    }

    public function removeService(Service $service): self
    {
        $this->services->removeElement($service);
        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Calcule le prix total TTC du forfait
     */
    public function getTotalPriceWithVat(): float
    {
        $price = floatval($this->price);
        $vat = floatval($this->vat ?? 0);
        return $price * (1 + $vat / 100);
    }

    /**
     * Retourne le nombre de services inclus dans le forfait
     */
    public function getServicesCount(): int
    {
        return $this->services->count();
    }
}