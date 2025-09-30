<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Controller\FusionCustomersController;

/**
 * @ORM\Entity(repositoryClass=CustomerRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
#[ApiResource(
    collectionOperations: [
        'get' => [
            'pagination_enabled' => false,
            'normalization_context' => ['groups' => ['customer:read']],
            'security' => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')) and user.enabled == true"
        ],
        'get-prospects' => [
            'pagination_enabled' => false,
            'normalization_context' => ['groups' => ['customer:restrict:read']],
            'method' => 'GET',
            'path' => '/prospects',
            'security' => 'user.enabled == true',
            'openapi_context' => [
                'summary'     => 'Retrieves the collection of filtered Customer resources.',
                'description' => 'Retrieves the collection of filtered Customer resources.'
            ]
        ],
        'post' => [
            'normalization_context' => ['groups' => ['customer:read']],
            'denormalization_context' => ['groups' => ['customer:write']],
            'security' => 'user.enabled == true'
        ],
        'fusion-customers' => [
            'method' => 'POST',
            'path' => '/fusion-customers',
            'deserialize' => false,
            'controller' => FusionCustomersController::class,
            'security' => "(is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')) and user.enabled == true",
            'openapi_context' => [
                'summary'     => 'Fusion multiple customers (admin only).',
                'description' => "If you provide a list of customer ids, they'll fusion.",
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema'  => [
                                'type'       => 'object',
                                'properties' =>
                                    [
                                        'ids' => ['type' => 'string']
                                    ],
                            ],
                            'example' => [
                                'ids' => '1,2,3'
                            ],
                        ],
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Customers fusioned'
                    ],
                    '201' => '',
                    '400' => [
                        'description' => 'Bad request'
                    ],
                    '401' => [
                        'description' => 'Invalid email'
                    ]
                ]
            ]
        ]
    ],
    itemOperations: [
        'get' => [
            'normalization_context' => ['groups' => ['customer:read']],
            'security' => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')) and user.enabled == true",
            'openapi_context' => [
                'summary'     => 'Retrieves a Customer resource.',
                'description' => 'Retrieves a Customer resource.'
            ]
        ],
        'patch' => [
            'normalization_context' => ['groups' => ['customer:read']],
            'denormalization_context' => ['groups' => ['customer:write', 'customer:update']],
            'security' => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('IS_ALLOWED', object)) and user.enabled == true",
            'openapi_context' => [
                'summary'     => 'Updates the Customer resource.',
                'description' => 'Updates the Customer resource.'
            ]
        ],
        'delete' => [
            'security' => "(is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')) and user.enabled == true",
            'openapi_context' => [
                'summary'     => 'Removes the Customer resource.',
                'description' => 'Removes the Customer resource.'
            ]
        ]
    ]
)]
class Customer
{
    const STATUS = [
        "En cours",
        "Annulé",
        "Litige",
        "Prospect",
        "Encaissé",
        "Impayé",
        "Black List"
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(['sell:read', 'customer:read', 'customer:restrict:read', 'appointment:read'])]
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(['sell:read', 'customer:read', 'customer:write', 'appointment:read', 'appointment:write', 'customer:restrict:read'])]
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(['sell:read', 'customer:read', 'customer:write', 'appointment:read', 'appointment:write', 'customer:restrict:read'])]
    private $lastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read', 'customer:read', 'customer:write', 'appointment:write', 'appointment:read'])]
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read', 'customer:read', 'customer:write', 'appointment:read', 'appointment:write'])]
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read', 'customer:read', 'customer:write', 'appointment:write', 'appointment:read'])]
    private $mobile;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(['sell:read', 'customer:read', 'customer:write', 'appointment:write', 'appointment:read'])]
    private $address;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(['sell:read', 'customer:read', 'customer:write', 'appointment:read', 'appointment:write', 'customer:restrict:read'])]
    private $postcode;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(['sell:read', 'customer:read', 'customer:write', 'appointment:read', 'appointment:write'])]
    private $city;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Choice(
     *      choices=Customer::STATUS,
     *      message="Le statut est invalide."
     * )
     */
    #[Groups(['sell:read', 'customer:read', 'customer:write', 'appointment:write', 'appointment:read'])]
    #[ApiProperty(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_SUPER_SALES')")]
    private $status;

    /**
     * @ORM\OneToMany(targetEntity=Sell::class, mappedBy="customer")
     */
    #[Groups(['customer:read'])]
    private $sales;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['customer:read', 'customer:update'])]
    private $comment;

    /**
     * @ORM\Column(type="boolean", options={"default":false})
     */
    #[Groups(['sell:read', 'customer:read', 'customer:update'])]
    private $flag;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Groups(['customer:read'])]
    private $created_date;

    /**
     * @ORM\OneToMany(targetEntity=Appointment::class, mappedBy="customer")
     */
    #[Groups(['customer:read'])]
    private $appointments;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    #[Groups(['customer:read', 'customer:write', 'sell:read'])]
    private $clientCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['customer:read', 'customer:write'])]
    private $secondMobile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['customer:read', 'customer:write'])]
    private $secondEmail;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=7, nullable=true)
     */
    #[Groups(['customer:read', 'customer:write'])]
    private $latitude;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=7, nullable=true)
     */
    #[Groups(['customer:read', 'customer:write'])]
    private $longitude;

    public function __construct()
    {
        $this->sales = new ArrayCollection();
        $this->appointments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(?string $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(?string $postcode): self
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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
            $sale->setCustomer($this);
        }

        return $this;
    }

    public function removeSale(Sell $sale): self
    {
        if ($this->sales->removeElement($sale)) {
            // set the owning side to null (unless already changed)
            if ($sale->getCustomer() === $this) {
                $sale->setCustomer(null);
            }
        }

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getFlag(): ?bool
    {
        return $this->flag;
    }

    public function setFlag(bool $flag): self
    {
        $this->flag = $flag;

        return $this;
    }

    /**
     * 
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->flag = false;
        $this->created_date = new \DateTime();
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->created_date;
    }

    public function setCreatedDate(?\DateTimeInterface $created_date): self
    {
        $this->created_date = $created_date;

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): self
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments[] = $appointment;
            $appointment->setCustomer($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): self
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getCustomer() === $this) {
                $appointment->setCustomer(null);
            }
        }

        return $this;
    }

    public function getClientCode(): ?string
    {
        return $this->clientCode;
    }

    public function setClientCode(?string $clientCode): self
    {
        $this->clientCode = $clientCode;
        return $this;
    }

    public function getSecondMobile(): ?string
    {
        return $this->secondMobile;
    }

    public function setSecondMobile(?string $secondMobile): self
    {
        $this->secondMobile = $secondMobile;
        return $this;
    }

    public function getSecondEmail(): ?string
    {
        return $this->secondEmail;
    }

    public function setSecondEmail(?string $secondEmail): self
    {
        $this->secondEmail = $secondEmail;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
