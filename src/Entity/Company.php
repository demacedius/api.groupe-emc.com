<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=CompanyRepository::class)
 */
#[ApiResource(
    attributes: ['security' => 'user.enabled == true'],
    collectionOperations: [
        'get' => [
            'pagination_enabled' => false,
            'normalization_context' => ['groups' => ['company:read']],
        ]
    ],
    itemOperations: [
        'get' => [
            'normalization_context' => ['groups' => ['company:read']],
        ]
    ]
)]
class Company
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(['sell:read', 'company:read'])]
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(['sell:read', 'company:read'])]
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(['sell:read'])]
    private $address;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(['sell:read'])]
    private $postcode;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(['sell:read'])]
    private $city;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(['sell:read', 'customer:read'])]
    private $prefix;

    /**
     * @ORM\OneToMany(targetEntity=Sell::class, mappedBy="company")
     */
    private $sales;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read'])]
    private $telephone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read'])]
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read'])]
    private $legal;

    /**
     * @ORM\Column(type="integer", options={"default" : 1})
     */
    private $nextSellId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read'])]
    private $siret;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read'])]
    private $tvaIntra;

    public function __construct()
    {
        $this->sales = new ArrayCollection();
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(string $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return Collection<int, Sell>
     */
    public function getSales(): Collection
    {
        return $this->sales;
    }

    public function addSale(Sell $sale): self
    {
        if (!$this->sales->contains($sale)) {
            $this->sales[] = $sale;
            $sale->setCompany($this);
        }

        return $this;
    }

    public function removeSale(Sell $sale): self
    {
        if ($this->sales->removeElement($sale)) {
            // set the owning side to null (unless already changed)
            if ($sale->getCompany() === $this) {
                $sale->setCompany(null);
            }
        }

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLegal(): ?string
    {
        return $this->legal;
    }

    public function setLegal(?string $legal): self
    {
        $this->legal = $legal;

        return $this;
    }

    public function getNextSellId(): ?int
    {
        return $this->nextSellId;
    }

    public function setNextSellId(?int $nextSellId): self
    {
        $this->nextSellId = $nextSellId;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): self
    {
        $this->siret = $siret;

        return $this;
    }

    public function getTvaIntra(): ?string
    {
        return $this->tvaIntra;
    }

    public function setTvaIntra(?string $tvaIntra): self
    {
        $this->tvaIntra = $tvaIntra;

        return $this;
    }
}
