<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass=ServiceRepository::class)
 */
#[ApiResource(
    attributes: ["security" => "user.enabled == true"],
    collectionOperations: [
        'get' => [
            'pagination_enabled' => false,
            'normalization_context' => ['groups' => ['service:read']]
        ],
        'get-profil-b' => [
            'method' => 'GET',
            'path' => '/services/profil-b',
            'pagination_enabled' => false,
            'normalization_context' => ['groups' => ['service:read:profil-b']],
            'security' => "is_granted('ROLE_PROFIL_B') and user.enabled == true",
            'openapi_context' => [
                'summary'     => 'Retrieve services for PROFIL B (without category).',
                'description' => 'Retrieve services for PROFIL B (without category).'
            ]
        ],
        "post"
    ],
    itemOperations: ["get", "patch", "delete"]
)]
class Service
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(["service:read", "service:read:profil-b"])]
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(["service:read", "service:read:profil-b"])]
    private $title;

    /**
     * @ORM\Column(type="float")
     */
    #[Groups(["service:read", "service:read:profil-b"])]
    private $price;

    /**
     * @ORM\Column(type="float")
     */
    #[Groups(["service:read", "service:read:profil-b"])]
    private $vat;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(["service:read", "service:read:profil-b"])]
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(["service:read", "service:read:profil-b"])]
    private $code;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(["service:read", "service:read:profil-b"])]
    private $description;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(["sell:read", "service:read", "service:read:profil-b"])]
    private $unit;

    /**
     * @ORM\OneToMany(targetEntity=SellItem::class, mappedBy="service")
     */
    private $sellItems;

    /**
     * @ORM\ManyToOne(targetEntity=ServiceCategory::class, inversedBy="services")
     */
    #[Groups(["service:read"])]
    private $category;

    public function __construct()
    {
        $this->sellItems = new ArrayCollection();
    }

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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getVat(): ?float
    {
        return $this->vat;
    }

    public function setVat(float $vat): self
    {
        $this->vat = $vat;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

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

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @return Collection<int, SellItem>
     */
    public function getSellItems(): Collection
    {
        return $this->sellItems;
    }

    public function getCategory(): ?ServiceCategory
    {
        return $this->category;
    }

    public function setCategory(?ServiceCategory $category): self
    {
        $this->category = $category;

        return $this;
    }
}
