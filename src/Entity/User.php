<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use App\Controller\ResetPasswordController;
use App\Controller\ForgotPasswordController;
use App\Controller\UsersStatisticsController;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use App\Entity\Company;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="Cet email est déjà utilisé")
 * @Vich\Uploadable
 */
#[ApiResource(
    collectionOperations: [
        'get' => [
            'pagination_enabled' => false,
            'normalization_context' => ['groups' => ['user:read']],
            'order' => ['enabled' => 'DESC', 'lastname' => 'ASC'],
            'security' => "(is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_SALES'))",
            'openapi_context' => [
                'summary'     => 'Retrieves the collection of User resources (admin only).',
                'description' => 'Retrieves the collection of User resources (admin only).'
            ]
        ], 
        'get-additionnal-users' => [
            'pagination_enabled' => false,
            'normalization_context' => ['groups' => ['user:restrict:read']],
            'method' => 'GET',
            'path' => '/additionnal-users',
            'security' => 'user.enabled == true',
            'openapi_context' => [
                'summary'     => 'Retrieves the collection of filtered User resources.',
                'description' => 'Retrieves the collection of filtered User resources.'
            ]
        ],
        'post' => [
            'normalization_context' => ['groups' => ['user:read']],
            'denormalization_context' => ['groups' => ['user:write']],
            'security_post_denormalize' => "(is_granted('ROLE_SUPER_ADMIN') or (is_granted('ROLE_ADMIN') and is_granted('IS_ALLOWED', object)) or is_granted('ROLE_PROFIL_B')) and user.enabled == true",
            'openapi_context' => [
                'summary'     => 'Creates a User resource (admin only).',
                'description' => 'Creates a User resource (admin only).'
            ]
        ],
        'reset-password' => [
            'method' => 'POST',
            'path' => '/account/reset-password',
            'deserialize' => false,
            'controller' => ResetPasswordController::class,
            'openapi_context' => [
                'summary'     => 'Replace your previous password.',
                'description' => 'If you provide a valid token, user password will be replaced by the new one.',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema'  => [
                                'type'       => 'object',
                                'properties' =>
                                    [
                                        'token'        => ['type' => 'string'],
                                        'password'     => ['type' => 'string']
                                    ],
                            ],
                            'example' => [
                                'token'        => 'test',
                                'password'     => 'test'
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Password updated'
                    ],
                    '201' => '',
                    '400' => [
                        'description' => 'Bad request'
                    ],
                    '401' => [
                        'description' => 'Access denied'
                    ]
                ]
            ]
        ],
        'forgot-password' => [
            'method' => 'POST',
            'path' => '/account/forgot-password',
            'deserialize' => false,
            'controller' => ForgotPasswordController::class,
            'openapi_context' => [
                'summary'     => 'Request a new password link.',
                'description' => 'If you provide a valid email address, you will receive an email with a reset password link.',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema'  => [
                                'type'       => 'object',
                                'properties' =>
                                    [
                                        'email'        => ['type' => 'string']
                                    ],
                            ],
                            'example' => [
                                'email'        => 'test@example.com'
                            ],
                        ],
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Email sent'
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
        ],
        'users-stats' => [
            'method' => 'GET',
            'path' => '/users/stats',
            'deserialize' => false,
            'controller' => UsersStatisticsController::class,
            'security' => 'user.enabled == true',
            'pagination_enabled' => false,
            'openapi_context' => [
                'summary'     => 'Retrieves user statistics.',
                'description' => 'Retrieves user statistics.',
                'responses' => [
                    '200' => [
                        'description' => 'User statistics retrieved'
                    ]
                ]
            ]
        ],
        'profil-b-users-stats' => [
            'method' => 'GET',
            'path' => '/profil-b/users/stats',
            'deserialize' => false,
            'controller' => 'App\Controller\ProfilBUsersStatisticsController',
            'security' => "is_granted('ROLE_PROFIL_B') and user.enabled == true",
            'pagination_enabled' => false,
            'openapi_context' => [
                'summary'     => 'Retrieves user statistics for PROFIL B (filtered by affiliated users).',
                'description' => 'Retrieves user statistics for PROFIL B (filtered by affiliated users).',
                'responses' => [
                    '200' => [
                        'description' => 'PROFIL B User statistics retrieved'
                    ]
                ]
            ]
        ]
    ],
    itemOperations: [
        'get' => [
            'pagination_enabled' => false,
            'security' => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')) and user.enabled == true",
            'openapi_context' => [
                'summary'     => 'Retrieves a User resource (admin only).',
                'description' => 'Retrieves a User resource (admin only).'
            ]
        ], 
        'patch' => [
            'security' => "(is_granted('ROLE_SUPER_ADMIN') or (is_granted('ROLE_ADMIN') and is_granted('IS_ALLOWED', object))) and user.enabled == true"
        ],
        'delete' => [
            'security' => "(is_granted('ROLE_SUPER_ADMIN') or (is_granted('ROLE_ADMIN') and is_granted('IS_ALLOWED', object))) and user.enabled == true"
        ],
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    const ROLES = [
        "ROLE_SUPER_ADMIN",
        "ROLE_ADMIN",
        "ROLE_SALES",
        "ROLE_SUPER_SALES",
        "ROLE_PROFIL_B"
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(['user:read', 'sell:read', 'appointment:read', 'user:restrict:read'])]
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    #[Groups(['user:read', 'user:write'])]
    #[ApiProperty(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')")]
    private $email;

    /**
     * @ORM\Column(type="json")
     * @Assert\Choice(
     *      choices=User::ROLES,
     *      multiple=true,
     *      message="Le role est invalide.",
     *      multipleMessage="roles contains one or more invalid value"
     * )
     */
    #[Groups(['user:read', 'user:write'])]
    #[ApiProperty(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')")]
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(['user:read', 'user:write', 'sell:read', 'customer:read', 'appointment:read', 'user:restrict:read'])]
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Groups(['user:read', 'user:write', 'sell:read', 'customer:read', 'appointment:read', 'user:restrict:read'])]
    private $lastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['user:read', 'user:write', 'sell:read', 'customer:read', 'appointment:read', 'user:restrict:read'])]
    private $profilePicture;

    #[
        Groups(['user:reset']),
        SerializedName('password')
    ]
    private $plainPassword;

    /**
     * @ORM\OneToMany(targetEntity=Sell::class, mappedBy="user")
     */
    private $sales;

    /**
     * @ORM\Column(type="boolean")
     */
    #[Groups(['user:read', 'user:write'])]
    #[ApiProperty(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')")]
    public $enabled;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $resetExp;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $resetToken;

    /**
     * @ORM\OneToMany(targetEntity=Appointment::class, mappedBy="user")
     */
    private $appointments;

    /**
     * @ORM\OneToMany(targetEntity=Sell::class, mappedBy="additionnalSeller")
     */
    private $binomialSales;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class)
     */
    #[Groups(['user:read', 'user:write'])]
    private $company;


    /**
     * @ORM\Column(type="boolean", options={"default":false})
     */
    #[Groups(['user:read', 'user:write'])]
    #[ApiProperty(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')")]
    private $binomialAllowed;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="managedUsers")
     * @ORM\JoinColumn(nullable=true)
     */
    #[Groups(['user:read', 'user:write'])]
    private $manager;

    /**
     * @ORM\OneToMany(targetEntity=User::class, mappedBy="manager")
     */
    #[Groups(['user:read'])]
    private $managedUsers;

    public function __construct()
    {
        $this->sales = new ArrayCollection();
        $this->appointments = new ArrayCollection();
        $this->binomialSales = new ArrayCollection();
        $this->managedUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture ? 'http://192.168.1.231:8080/images/users/' . $this->profilePicture : null;
    }

    public function setProfilePicture(?string $profilePicture): self
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    /**
     * @Vich\UploadableField(mapping="user_profile_picture", fileNameProperty="profilePicture")
     * @var File|null
     */
    private $imageFile;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTimeInterface|null
     */
    private $updatedAt;

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
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
            $sale->setUser($this);
        }

        return $this;
    }

    public function removeSale(Sell $sale): self
    {
        if ($this->sales->removeElement($sale)) {
            // set the owning side to null (unless already changed)
            if ($sale->getUser() === $this) {
                $sale->setUser(null);
            }
        }

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getResetExp(): ?\DateTimeInterface
    {
        return $this->resetExp;
    }

    public function setResetExp(?\DateTimeInterface $resetExp): self
    {
        $this->resetExp = $resetExp;

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;

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
            $appointment->setUser($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): self
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getUser() === $this) {
                $appointment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Sell>
     */
    public function getBinomialSales(): Collection
    {
        return $this->binomialSales;
    }

    public function addBinomialSale(Sell $binomialSale): self
    {
        if (!$this->binomialSales->contains($binomialSale)) {
            $this->binomialSales[] = $binomialSale;
            $binomialSale->setAdditionnalSeller($this);
        }

        return $this;
    }

    public function removeBinomialSale(Sell $binomialSale): self
    {
        if ($this->binomialSales->removeElement($binomialSale)) {
            // set the owning side to null (unless already changed)
            if ($binomialSale->getAdditionnalSeller() === $this) {
                $binomialSale->setAdditionnalSeller(null);
            }
        }

        return $this;
    }

    public function getBinomialAllowed(): ?bool
    {
        return $this->binomialAllowed;
    }

    public function setBinomialAllowed(bool $binomialAllowed): self
    {
        $this->binomialAllowed = $binomialAllowed;

        return $this;
    }

    public function getManager(): ?self
    {
        return $this->manager;
    }

    public function setManager(?self $manager): self
    {
        $this->manager = $manager;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getManagedUsers(): Collection
    {
        return $this->managedUsers;
    }

    public function addManagedUser(self $managedUser): self
    {
        if (!$this->managedUsers->contains($managedUser)) {
            $this->managedUsers[] = $managedUser;
            $managedUser->setManager($this);
        }

        return $this;
    }

    public function removeManagedUser(self $managedUser): self
    {
        if ($this->managedUsers->removeElement($managedUser)) {
            // set the owning side to null (unless already changed)
            if ($managedUser->getManager() === $this) {
                $managedUser->setManager(null);
            }
        }

        return $this;
    }

    /**
     * @Groups({"user:read"})
     */
    public function getImageUrl(): ?string
    {
        return $this->profilePicture ? 'http://192.168.1.231:8000/images/users/' . $this->profilePicture : null;
    }
}
