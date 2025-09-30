<?php

namespace App\Entity;

use App\Repository\SellRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Controller\StatisticsController;

/**
 * @ORM\Entity(repositoryClass=SellRepository::class)
 * @ORM\EntityListeners({"App\Doctrine\SellListener"})
 * @ORM\HasLifecycleCallbacks()
 */
#[ApiResource(
    order: ['id' => 'DESC'],
    shortName: 'Sales',
    collectionOperations: [
        'get' => [
            'pagination_enabled' => false,
            'normalization_context' => ['groups' => ['sell:read']],
            'security' => 'user.enabled == true'
        ],
        'post' => [
            'normalization_context' => ['groups' => ['sell:read']],
            'denormalization_context' => ['groups' => ['sell:write']],
            'security' => 'user.enabled == true'
        ],
        'stats' => [
            'method' => 'GET',
            'path' => '/stats',
            'deserialize' => false,
            'controller' => StatisticsController::class,
            'security' => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_SUPER_SALES')) and user.enabled == true",
            'pagination_enabled' => false,
            'openapi_context' => [
                'summary'     => 'Retrieve statistics (admin only).',
                'description'     => 'Retrieve statistics (admin only).',
                'responses' => [
                    '200' => [
                        'description' => 'Statistics retrieved'
                    ]
                ]
            ]
        ],
        'profil-b-stats' => [
            'method' => 'GET',
            'path' => '/profil-b-stats',
            'deserialize' => false,
            'controller' => 'App\Controller\ProfilBStatisticsController',
            'security' => "is_granted('ROLE_PROFIL_B') and user.enabled == true",
            'pagination_enabled' => false,
            'openapi_context' => [
                'summary'     => 'Retrieve statistics for PROFIL B (filtered by affiliated users).',
                'description'     => 'Retrieve statistics for PROFIL B (filtered by affiliated users).',
                'responses' => [
                    '200' => [
                        'description' => 'PROFIL B Statistics retrieved'
                    ]
                ]
            ]
        ],
        'profil-b-sales' => [
            'method' => 'GET',
            'path' => '/profil-b-sales',
            'deserialize' => false,
            'controller' => 'App\Controller\ProfilBSalesController',
            'security' => "is_granted('ROLE_PROFIL_B') and user.enabled == true",
            'pagination_enabled' => false,
            'openapi_context' => [
                'summary'     => 'Retrieve sales for PROFIL B (filtered by affiliated users).',
                'description'     => 'Retrieve sales for PROFIL B (filtered by affiliated users).',
                'responses' => [
                    '200' => [
                        'description' => 'PROFIL B Sales retrieved'
                    ]
                ]
            ]
        ]
    ],
    itemOperations: [
        'get' => [
            'normalization_context' => ['groups' => ['sell:read']],
            'security' => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_PROFIL_B') or is_granted('IS_ALLOWED', object)) and user.enabled == true"
        ],
        'patch' => [
            'normalization_context' => ['groups' => ['sell:read']],
            'denormalization_context' => ['groups' => ['sell:update']],
            'security' => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_PROFIL_B') or is_granted('IS_ALLOWED', object)) and user.enabled == true"
        ],
        'delete' => [
            'security' => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or (is_granted('ROLE_SUPER_SALES') and is_granted('IS_ALLOWED', object))) and user.enabled == true",
            'openapi_context' => [
                'summary'     => 'Removes the Sales resource.',
                'description' => 'Removes the Sales resource.'
            ]
        ]
    ]
)]
class Sell
{
    const STATUS = [
        "En attente FDR",
        "Dossier incomplet",
        "VENTE A REVOIR",
        "En attente pose",
        "En attente paiement",
        "Encaissée",
        "Annulée",
        "Impayé",
        "Black List",
        "Autre"
    ];

    const INPROGRESS = [
        "En attente FDR",
        "Dossier incomplet",
        "VENTE A REVOIR",
        "En attente pose",
        "En attente paiement"
    ];

    const NOTPAID = [
        "En attente FDR",
        "Dossier incomplet",
        "En attente pose",
        "En attente paiement"
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(['sell:read', 'customer:read'])]
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    #[Groups(['sell:read', 'sell:write', 'customer:read'])]
    private $created_date;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Groups(['sell:read'])]
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Groups(['sell:read'])]
    private $cancellationDate;

    /**
     * @ORM\ManyToOne(targetEntity=Customer::class, inversedBy="sales")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Groups(['sell:read', 'sell:write'])]
    private $customer;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $telesalesperson;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $payment_method;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Choice(
     *      choices=Sell::STATUS,
     *      message="Le statut est invalide."
     * )
     */
    #[Groups(['sell:read', 'sell:write', 'customer:read', 'sell:update'])]
    private $status;

    /**
     * @ORM\OneToMany(targetEntity=SellItem::class, mappedBy="sell", orphanRemoval=true)
     */
    #[Groups(['sell:read'])]
    private $sellItems;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $organism;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $comment;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="sales")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Groups(['sell:read', 'sell:write', 'customer:read'])]
    private $company;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $source;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $salesman;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $workDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $workers;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $twoyear;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $tenyear;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $followup;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $teleoperatorName;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $paymentType; // 'cash' or 'financing'

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $fees;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $details;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $financialSection = [];

    /**
     * @ORM\OneToMany(targetEntity=SellImage::class, mappedBy="sell", orphanRemoval=true)
     */
    #[Groups(['sell:read'])]
    private $images;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="sales")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Groups(['sell:read'])]
    public $user;

    /**
     * @ORM\OneToOne(targetEntity=WorkSheet::class, mappedBy="sell", cascade={"persist", "remove"})
     */
    #[Groups(['sell:read'])]
    private $workSheet;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="binomialSales")
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $additionnalSeller;


    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    #[Groups(['sell:read'])]
    private $sellId;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=3, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $discountAmount;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $discountType; // 'percentage' or 'amount'

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $encashmentDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $fdrDate;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=3, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $depositAmount;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=3, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $balanceAmount;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $additionalSellers = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $customStatusComment;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    #[Groups(['sell:read', 'sell:write', 'sell:update'])]
    private $fileFeesDisabled = false;

    public function __construct()
    {
        $this->sellItems = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->created_date;
    }

    public function setCreatedDate(\DateTimeInterface $created_date): self
    {
        $this->created_date = $created_date;

        return $this;
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

    public function getCancellationDate(): ?\DateTimeInterface
    {
        return $this->cancellationDate;
    }

    public function setCancellationDate(?\DateTimeInterface $cancellationDate): self
    {
        $this->cancellationDate = $cancellationDate;

        return $this;
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

    public function getTelesalesperson(): ?string
    {
        return $this->telesalesperson;
    }

    public function setTelesalesperson(?string $telesalesperson): self
    {
        $this->telesalesperson = $telesalesperson;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->payment_method;
    }

    #[Groups(["sell:write", "sell:update"])]
    public function setPaymentMethod(?string $payment_method): self
    {
        $this->payment_method = $payment_method;

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
     * @return Collection<int, SellItem>
     */
    public function getSellItems(): Collection
    {
        return $this->sellItems;
    }

    public function addSellItem(SellItem $sellItem): self
    {
        if (!$this->sellItems->contains($sellItem)) {
            $this->sellItems[] = $sellItem;
            $sellItem->setSell($this);
        }

        return $this;
    }

    public function removeSellItem(SellItem $sellItem): self
    {
        if ($this->sellItems->removeElement($sellItem)) {
            // set the owning side to null (unless already changed)
            if ($sellItem->getSell() === $this) {
                $sellItem->setSell(null);
            }
        }

        return $this;
    }

    /**
     * 
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->created_date = new \DateTime();
    }

    public function getOrganism(): ?string
    {
        return $this->organism;
    }

    public function setOrganism(?string $organism): self
    {
        $this->organism = $organism;

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

    #[Groups(["sell:read", "customer:read"])]
    public function getTotal() {
        $total = 0;
        foreach($this->sellItems as $item) {
            $price = floatval($item->getPrice());
            $quantity = floatval($item->getQuantity());
            $lineTotal = $quantity * $price;
            $total += $lineTotal;
        }

        return $total;
    }

    #[Groups(["sell:read"])]
    public function getTotalWithTaxes() {
        $total = 0;
        foreach($this->sellItems as $item) {
            $price = floatval($item->getPrice());
            $quantity = floatval($item->getQuantity());
            $vat = floatval($item->getService()->getVat());
            $unitPrice = $price * (1 + ($vat / 100));
            $lineTotal = $quantity * $unitPrice;
            $total += $lineTotal;
        }

        return $total;
    }

    #[Groups(["sell:read"])]
    public function getTotalTaxes() {
        $total = 0;
        foreach($this->sellItems as $item) {
            $price = floatval($item->getPrice());
            $quantity = floatval($item->getQuantity());
            $vat = floatval($item->getService()->getVat());
            $unitPrice = $price * ($vat / 100);
            $lineTotal = $quantity * $unitPrice;
            $total += $lineTotal;
        }

        return $total;
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

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getSalesman(): ?string
    {
        return $this->salesman;
    }

    public function setSalesman(?string $salesman): self
    {
        $this->salesman = $salesman;

        return $this;
    }

    public function getWorkDate(): ?\DateTimeInterface
    {
        return $this->workDate;
    }

    public function setWorkDate(?\DateTimeInterface $workDate): self
    {
        $this->workDate = $workDate;

        return $this;
    }

    public function getWorkers(): ?string
    {
        return $this->workers;
    }

    public function setWorkers(?string $workers): self
    {
        $this->workers = $workers;

        return $this;
    }

    public function getTwoyear(): ?bool
    {
        return $this->twoyear;
    }

    public function setTwoyear(?bool $twoyear): self
    {
        $this->twoyear = $twoyear;

        return $this;
    }

    public function getTenyear(): ?bool
    {
        return $this->tenyear;
    }

    public function setTenyear(?bool $tenyear): self
    {
        $this->tenyear = $tenyear;

        return $this;
    }

    public function getFollowup(): ?string
    {
        return $this->followup;
    }

    public function setFollowup(?string $followup): self
    {
        $this->followup = $followup;

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

    public function getPaymentType(): ?string
    {
        return $this->paymentType;
    }

    public function setPaymentType(?string $paymentType): self
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    public function getFees(): ?string
    {
        return $this->fees;
    }

    public function setFees(?string $fees): self
    {
        $this->fees = $fees;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getFinancialSection(): ?array
    {
        return $this->financialSection;
    }

    public function setFinancialSection(?array $financialSection): self
    {
        $this->financialSection = $financialSection;

        return $this;
    }

    /**
     * @return Collection<int, SellImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(SellImage $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setSell($this);
        }

        return $this;
    }

    public function removeImage(SellImage $image): self
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getSell() === $this) {
                $image->setSell(null);
            }
        }

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

    public function getWorkSheet(): ?WorkSheet
    {
        return $this->workSheet;
    }

    public function setWorkSheet(WorkSheet $workSheet): self
    {
        // set the owning side of the relation if necessary
        if ($workSheet->getSell() !== $this) {
            $workSheet->setSell($this);
        }

        $this->workSheet = $workSheet;

        return $this;
    }

    public function getAdditionnalSeller(): ?User
    {
        return $this->additionnalSeller;
    }

    public function setAdditionnalSeller(?User $additionnalSeller): self
    {
        $this->additionnalSeller = $additionnalSeller;

        return $this;
    }

    public function getSellId(): ?int
    {
        return $this->sellId;
    }

    public function setSellId(int $sellId): self
    {
        $this->sellId = $sellId;

        return $this;
    }

    public function getDiscountAmount(): ?string
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(?string $discountAmount): self
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }

    public function getDiscountType(): ?string
    {
        return $this->discountType;
    }

    public function setDiscountType(?string $discountType): self
    {
        $this->discountType = $discountType;

        return $this;
    }

    public function getEncashmentDate(): ?\DateTimeInterface
    {
        return $this->encashmentDate;
    }

    public function setEncashmentDate(?\DateTimeInterface $encashmentDate): self
    {
        $this->encashmentDate = $encashmentDate;

        return $this;
    }

    public function getFdrDate(): ?\DateTimeInterface
    {
        return $this->fdrDate;
    }

    public function setFdrDate(?\DateTimeInterface $fdrDate): self
    {
        $this->fdrDate = $fdrDate;

        return $this;
    }

    public function getDepositAmount(): ?string
    {
        return $this->depositAmount;
    }

    public function setDepositAmount(?string $depositAmount): self
    {
        $this->depositAmount = $depositAmount;

        return $this;
    }

    public function getBalanceAmount(): ?string
    {
        return $this->balanceAmount;
    }

    public function setBalanceAmount(?string $balanceAmount): self
    {
        $this->balanceAmount = $balanceAmount;

        return $this;
    }

    public function getAdditionalSellers(): ?array
    {
        return $this->additionalSellers;
    }

    public function setAdditionalSellers(?array $additionalSellers): self
    {
        $this->additionalSellers = $additionalSellers ?? [];

        return $this;
    }

    public function getCustomStatusComment(): ?string
    {
        return $this->customStatusComment;
    }

    public function setCustomStatusComment(?string $customStatusComment): self
    {
        $this->customStatusComment = $customStatusComment;

        return $this;
    }

    public function isFileFeesDisabled(): ?bool
    {
        return $this->fileFeesDisabled;
    }

    public function setFileFeesDisabled(bool $fileFeesDisabled): self
    {
        $this->fileFeesDisabled = $fileFeesDisabled;

        return $this;
    }

    // #[Groups(["sell:read"])] - Temporairement désactivé pour debug
    public function getTotalWithDiscount(): float
    {
        $total = $this->getTotal();
        
        if ($this->discountAmount && $this->discountType) {
            if ($this->discountType === 'percentage') {
                $discount = $total * (floatval($this->discountAmount) / 100);
            } else {
                $discount = floatval($this->discountAmount);
            }
            $total -= $discount;
        }
        
        return max(0, $total);
    }

    // #[Groups(["sell:read"])] - Temporairement désactivé pour debug
    public function getAmountInWords(): string
    {
        $amount = $this->getTotalWithDiscount();
        return $this->convertAmountToWords($amount);
    }

    private function convertAmountToWords(float $amount): string
    {
        $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
        $teens = ['dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $tens = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante-dix', 'quatre-vingt', 'quatre-vingt-dix'];
        $hundreds = ['', 'cent', 'deux cents', 'trois cents', 'quatre cents', 'cinq cents', 'six cents', 'sept cents', 'huit cents', 'neuf cents'];
        
        $euros = intval($amount);
        $cents = intval(($amount - $euros) * 100);
        
        $result = '';
        
        if ($euros == 0) {
            $result = 'zéro euro';
        } else {
            $result = $this->convertNumberToWords($euros) . ($euros > 1 ? ' euros' : ' euro');
        }
        
        if ($cents > 0) {
            $result .= ' et ' . $this->convertNumberToWords($cents) . ($cents > 1 ? ' centimes' : ' centime');
        }
        
        return ucfirst($result);
    }

    private function convertNumberToWords(int $number): string
    {
        if ($number == 0) return '';
        
        $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
        $teens = ['dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $tens = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante-dix', 'quatre-vingt', 'quatre-vingt-dix'];
        
        $result = '';
        
        if ($number >= 1000) {
            $thousands = intval($number / 1000);
            if ($thousands == 1) {
                $result .= 'mille ';
            } else {
                $result .= $this->convertNumberToWords($thousands) . ' mille ';
            }
            $number %= 1000;
        }
        
        if ($number >= 100) {
            $hundredsDigit = intval($number / 100);
            if ($hundredsDigit == 1) {
                $result .= 'cent ';
            } else {
                $result .= $units[$hundredsDigit] . ' cents ';
            }
            $number %= 100;
        }
        
        if ($number >= 20) {
            $tensDigit = intval($number / 10);
            $unitsDigit = $number % 10;
            $result .= $tens[$tensDigit];
            if ($unitsDigit > 0) {
                $result .= '-' . $units[$unitsDigit];
            }
        } elseif ($number >= 10) {
            $result .= $teens[$number - 10];
        } elseif ($number > 0) {
            $result .= $units[$number];
        }
        
        return trim($result);
    }

}
