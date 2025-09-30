<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\AppointmentRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AppointmentRepository::class)
 * @ORM\EntityListeners({"App\Doctrine\AppointmentSetUserListener"})
 * @ORM\HasLifecycleCallbacks()
 */
#[ApiResource(
    collectionOperations: [
        'get' => [
            'pagination_enabled' => false,
            'normalization_context' => ['groups' => ['appointment:read']],
            'security' => 'user.enabled == true'
        ],
        'post' => [
            'normalization_context' => ['groups' => ['appointment:read']],
            'denormalization_context' => ['groups' => ['appointment:write']],
            'security' => 'user.enabled == true'
        ],
    ],
    itemOperations: [
        'get' => [
            'normalization_context' => ['groups' => ['appointment:read']],
            'security' => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_SUPER_SALES')) and user.enabled == true",
            'openapi_context' => [
                'summary'     => 'Retrieves a Appointment resource.',
                'description' => 'Retrieves a Appointment resource.'
            ]
        ],
        'patch' => [
            'normalization_context' => ['groups' => ['appointment:read']],
            'denormalization_context' => ['groups' => ['appointment:update']],
            'security' => "((is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_SUPER_SALES')) or object.user == user) and user.enabled == true"
        ],
        'delete' => [
            'security' => "is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_SUPER_SALES') and user.enabled == true",
            'openapi_context' => [
                'summary'     => 'Removes the Appointment resource.',
                'description' => 'Removes the Appointment resource.'
            ]
        ]
    ]
)]
class Appointment
{
    const STATUS = [
        "A venir",
        "ABS/NRP", // Fusion de ABS et NRP
        "RDC",
        "Entrée sans suite",
        "Vente",
        "HORS CIBLE",
        "PARTIEL",
        "A REPLACER",
        "HORS SECTEUR",
        "EN RDV",
        "REF AUTRE société"
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(['appointment:read'])]
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Customer::class, inversedBy="appointments", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    #[Groups(['appointment:read', 'appointment:write'])]
    private $customer;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="appointments")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Groups(['appointment:read', 'customer:read', 'allnotsales:write'])]
    public $user;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Choice(
     *      choices=Appointment::STATUS,
     *      message="Le statut est invalide."
     * )
     */
    #[Groups(['appointment:read', 'appointment:update', 'customer:read'])]
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['appointment:read', 'appointment:update', 'customer:read'])]
    private $comment;

    /**
     * @ORM\Column(type="datetime")
     */
    #[Groups(['appointment:read', 'customer:read'])]
    private $createdDate;

    /**
     * @ORM\Column(type="boolean", options={"default":false})
     */
    #[Groups(['appointment:read', 'allnotsales:write'])]
    private $closed;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Groups(['appointment:read', 'appointment:update'])]
    private $replacementDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Groups(['appointment:read', 'appointment:write', 'appointment:update'])]
    private $appointmentDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['appointment:read', 'appointment:write', 'appointment:update'])]
    private $teleoperatorName;

    /**
     * 
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->closed = false;
        $this->status = "A venir";
        $this->createdDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->createdDate;
    }

    public function setCreatedDate(\DateTimeInterface $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function getClosed(): ?bool
    {
        return $this->closed;
    }

    public function setClosed(bool $closed): self
    {
        $this->closed = $closed;

        return $this;
    }

    public function getReplacementDate(): ?\DateTimeInterface
    {
        return $this->replacementDate;
    }

    public function setReplacementDate(?\DateTimeInterface $replacementDate): self
    {
        $this->replacementDate = $replacementDate;

        return $this;
    }

    public function getAppointmentDate(): ?\DateTimeInterface
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(?\DateTimeInterface $appointmentDate): self
    {
        $this->appointmentDate = $appointmentDate;

        return $this;
    }

    public function getTeleoperatorName(): ?string
    {
        return $this->teleoperatorName;
    }

    public function setTeleoperatorName(?string $teleoperatorName): self
    {
        $this->teleoperatorName = $teleoperatorName;

        return $this;
    }
}
