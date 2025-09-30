<?php

namespace App\Entity;

use App\Repository\SellItemRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=SellItemRepository::class)
 */
#[ApiResource(
    collectionOperations: [
        'get' => [
            'pagination_enabled' => false,
            "security" => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_SUPER_SALES') or is_granted('ROLE_PROFIL_B')) and user.enabled == true"
        ], 
        'post' => [
            "security_post_denormalize" => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_SUPER_SALES') or is_granted('ROLE_PROFIL_B') or is_granted('IS_ALLOWED', object)) and user.enabled == true"
        ]
    ],
    itemOperations: [
        'get' => [
            "security" => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_SUPER_SALES') or is_granted('ROLE_PROFIL_B') or is_granted('IS_ALLOWED', object)) and user.enabled == true"
        ],
        'patch' => [
            "security" => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_SUPER_SALES') or is_granted('ROLE_PROFIL_B') or is_granted('IS_ALLOWED', object)) and user.enabled == true"
        ],
        'delete' => [
            "security" => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_SUPER_SALES') or is_granted('ROLE_PROFIL_B') or is_granted('IS_ALLOWED', object)) and user.enabled == true"
        ]
    ]
)]
class SellItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(["sell:read"])]
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(["sell:read"])]
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(["sell:read"])]
    private $description;

    /**
     * @ORM\Column(type="float")
     */
    #[Groups(["sell:read"])]
    private $price;

    /**
     * @ORM\Column(type="float")
     */
    #[Groups(["sell:read"])]
    private $quantity;

    /**
     * @ORM\ManyToOne(targetEntity=Service::class, inversedBy="sellItems")
     * @ORM\JoinColumn(nullable=true)
     */
    #[Groups(["sell:read"])]
    private $service;

    /**
     * @ORM\ManyToOne(targetEntity=ServicePackage::class)
     * @ORM\JoinColumn(nullable=true)
     */
    #[Groups(["sell:read"])]
    private $servicePackage;

    /**
     * @ORM\ManyToOne(targetEntity=Sell::class, inversedBy="sellItems")
     * @ORM\JoinColumn(nullable=false)
     */
    private $sell;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

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

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function getServicePackage(): ?ServicePackage
    {
        return $this->servicePackage;
    }

    public function setServicePackage(?ServicePackage $servicePackage): self
    {
        $this->servicePackage = $servicePackage;

        return $this;
    }

    public function getSell(): ?Sell
    {
        return $this->sell;
    }

    public function setSell(?Sell $sell): self
    {
        $this->sell = $sell;

        return $this;
    }

    #[Groups(["sell:read"])]
    public function getTotal() {
        $price = floatval($this->price);
        $quantity = floatval($this->quantity);
        
        // Utiliser le prix du forfait si disponible, sinon utiliser le prix du service individuel
        if ($this->servicePackage) {
            $vat = floatval($this->servicePackage->getVat() ?? 0);
        } elseif ($this->service) {
            $vat = floatval($this->service->getVat() ?? 0);
        } else {
            $vat = 0;
        }
        
        // Calcul avec TVA si nécessaire
        // $unitPrice = $price * (1 + ($vat / 100));
        // $total = $quantity * $unitPrice;
        $total = $quantity * $price;

        return $total;
    }
}
