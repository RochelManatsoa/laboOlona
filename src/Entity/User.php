<?php

namespace App\Entity;

use App\Entity\Blog\Post;
use App\Entity\Coworking\Contract;
use App\Entity\Facebook\ContestEntry;
use App\Entity\Marketing\Lead;
use App\Entity\Vues\VideoVues;
use Doctrine\DBAL\Types\Types;
use App\Entity\Coworking\Event;
use App\Entity\Finance\Employe;
use App\Entity\Logs\ActivityLog;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Credit;
use App\Entity\BusinessModel\History;
use App\Entity\BusinessModel\Transaction;
use Doctrine\Common\Collections\Collection;
use App\Entity\BusinessModel\BoostVisibility;
use App\Entity\BusinessModel\PurchasedContact;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cette adresse e-mail.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    const ACCOUNT_CANDIDAT = 'CANDIDAT';
    const ACCOUNT_ENTREPRISE = 'ENTREPRISE';
    const ACCOUNT_MODERATEUR = 'MODERATEUR';
    const ACCOUNT_REFERRER = 'REFERRER';
    const ACCOUNT_EMPLOYE = 'EMPLOYE';
   
    public static function getChoices() {
        return [
            'Candidat' => self::ACCOUNT_CANDIDAT ,
            'Entreprise' => self::ACCOUNT_ENTREPRISE ,
            'Coopteur' => self::ACCOUNT_REFERRER ,
            'Employé' => self::ACCOUNT_EMPLOYE ,
        ];
    }
   
    public static function getInverseChoices() {
        return [
             self::ACCOUNT_CANDIDAT => 'Candidat',
             self::ACCOUNT_ENTREPRISE => 'Entreprise',
             self::ACCOUNT_REFERRER => 'Coopteur',
             self::ACCOUNT_EMPLOYE => 'Employé',
        ];
    }
   
    public static function getTypeAccount() {
        return [
            'Candidat' => self::ACCOUNT_CANDIDAT ,
            'Entreprise' => self::ACCOUNT_ENTREPRISE ,
            'Coopteur' => self::ACCOUNT_REFERRER ,
        ];
    }
   
    public static function getProfileAccount() {
        return [
            'Candidat' => self::ACCOUNT_CANDIDAT ,
            'Entreprise' => self::ACCOUNT_ENTREPRISE ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user'])]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['user'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['user'])]
    private $isVerified = false;

    #[ORM\OneToOne(mappedBy: 'candidat', cascade: ['persist', 'remove'])]
    private ?CandidateProfile $candidateProfile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user'])]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user'])]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['user'])]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dernierLogin = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user'])]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adress = null;

    #[ORM\OneToOne(mappedBy: 'entreprise', cascade: ['persist', 'remove'])]
    private ?EntrepriseProfile $entrepriseProfile = null;

    #[ORM\OneToOne(mappedBy: 'moderateur', cascade: ['persist', 'remove'])]
    private ?ModerateurProfile $moderateurProfile = null;

    #[ORM\OneToMany(mappedBy: 'expediteur', targetEntity: Notification::class, orphanRemoval: true)]
    private Collection $envois;

    #[ORM\OneToMany(mappedBy: 'destinataire', targetEntity: Notification::class, orphanRemoval: true)]
    private Collection $recus;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: SearchHistory::class, cascade: ['remove'])]
    private Collection $searchHistories;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gravatar = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: VideoVues::class, cascade: ['remove'])]
    private Collection $videoVues;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\OneToOne(mappedBy: 'referrer', cascade: ['persist', 'remove'])]
    private ?ReferrerProfile $referrerProfile = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Employe $employe = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Credit $credit = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: History::class)]
    private Collection $histories;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Transaction::class)]
    private Collection $transactions;

    #[ORM\OneToMany(mappedBy: 'buyer', targetEntity: PurchasedContact::class)]
    private Collection $purchasedContacts;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Order::class)]
    private Collection $orders;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: BoostVisibility::class)]
    private Collection $boostVisibilities;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ActivityLog::class)]
    private Collection $activityLogs;

    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'user')]
    private Collection $events;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Post::class)]
    private Collection $posts;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Contract::class)]
    private Collection $contracts;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebookId = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ContestEntry::class)]
    private Collection $contestEntries;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Lead::class)]
    private Collection $leads;

    public function __construct()
    {
        $this->dateInscription = new \DateTime();
        $this->envois = new ArrayCollection();
        $this->recus = new ArrayCollection();
        $this->searchHistories = new ArrayCollection();
        $this->videoVues = new ArrayCollection();
        $this->histories = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->purchasedContacts = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->boostVisibilities = new ArrayCollection();
        $this->activityLogs = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->contracts = new ArrayCollection();
        $this->contestEntries = new ArrayCollection();
        $this->leads = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getFullName();
    }

    public function getFullName()
    {
        return $this->getNom().' '.$this->getPrenom();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
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
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function getCandidateProfile(): ?CandidateProfile
    {
        return $this->candidateProfile;
    }

    public function setCandidateProfile(?CandidateProfile $candidateProfile): static
    {
        // unset the owning side of the relation if necessary
        if ($candidateProfile === null && $this->candidateProfile !== null) {
            $this->candidateProfile->setCandidat(null);
        }

        // set the owning side of the relation if necessary
        if ($candidateProfile !== null && $candidateProfile->getCandidat() !== $this) {
            $candidateProfile->setCandidat($this);
        }

        $this->candidateProfile = $candidateProfile;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeInterface $dateInscription): static
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }

    public function getDernierLogin(): ?\DateTimeInterface
    {
        return $this->dernierLogin;
    }

    public function setDernierLogin(?\DateTimeInterface $dernierLogin): static
    {
        $this->dernierLogin = $dernierLogin;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getAdress(): ?string
    {
        return $this->adress;
    }

    public function setAdress(?string $adress): static
    {
        $this->adress = $adress;

        return $this;
    }

    public function getEntrepriseProfile(): ?EntrepriseProfile
    {
        return $this->entrepriseProfile;
    }

    public function setEntrepriseProfile(?EntrepriseProfile $entrepriseProfile): static
    {
        // unset the owning side of the relation if necessary
        if ($entrepriseProfile === null && $this->entrepriseProfile !== null) {
            $this->entrepriseProfile->setEntreprise(null);
        }

        // set the owning side of the relation if necessary
        if ($entrepriseProfile !== null && $entrepriseProfile->getEntreprise() !== $this) {
            $entrepriseProfile->setEntreprise($this);
        }

        $this->entrepriseProfile = $entrepriseProfile;

        return $this;
    }

    public function getModerateurProfile(): ?ModerateurProfile
    {
        return $this->moderateurProfile;
    }

    public function setModerateurProfile(?ModerateurProfile $moderateurProfile): static
    {
        // unset the owning side of the relation if necessary
        if ($moderateurProfile === null && $this->moderateurProfile !== null) {
            $this->moderateurProfile->setModerateur(null);
        }

        // set the owning side of the relation if necessary
        if ($moderateurProfile !== null && $moderateurProfile->getModerateur() !== $this) {
            $moderateurProfile->setModerateur($this);
        }

        $this->moderateurProfile = $moderateurProfile;

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getEnvois(): Collection
    {
        return $this->envois;
    }

    public function addEnvoi(Notification $envoi): static
    {
        if (!$this->envois->contains($envoi)) {
            $this->envois->add($envoi);
            $envoi->setExpediteur($this);
        }

        return $this;
    }

    public function removeEnvoi(Notification $envoi): static
    {
        if ($this->envois->removeElement($envoi)) {
            // set the owning side to null (unless already changed)
            if ($envoi->getExpediteur() === $this) {
                $envoi->setExpediteur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getRecus(): Collection
    {
        return $this->recus;
    }

    public function addRecu(Notification $recu): static
    {
        if (!$this->recus->contains($recu)) {
            $this->recus->add($recu);
            $recu->setDestinataire($this);
        }

        return $this;
    }

    public function removeRecu(Notification $recu): static
    {
        if ($this->recus->removeElement($recu)) {
            // set the owning side to null (unless already changed)
            if ($recu->getDestinataire() === $this) {
                $recu->setDestinataire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SearchHistory>
     */
    public function getSearchHistories(): Collection
    {
        return $this->searchHistories;
    }

    public function addSearchHistory(SearchHistory $searchHistory): static
    {
        if (!$this->searchHistories->contains($searchHistory)) {
            $this->searchHistories->add($searchHistory);
            $searchHistory->setUser($this);
        }

        return $this;
    }

    public function removeSearchHistory(SearchHistory $searchHistory): static
    {
        if ($this->searchHistories->removeElement($searchHistory)) {
            // set the owning side to null (unless already changed)
            if ($searchHistory->getUser() === $this) {
                $searchHistory->setUser(null);
            }
        }

        return $this;
    }

    public function getGravatar(): ?string
    {
        return $this->gravatar;
    }

    public function setGravatar(?string $gravatar): static
    {
        $this->gravatar = $gravatar;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    /**
     * @return Collection<int, VideoVues>
     */
    public function getVideoVues(): Collection
    {
        return $this->videoVues;
    }

    public function addVideoVue(VideoVues $videoVue): static
    {
        if (!$this->videoVues->contains($videoVue)) {
            $this->videoVues->add($videoVue);
            $videoVue->setUser($this);
        }

        return $this;
    }

    public function removeVideoVue(VideoVues $videoVue): static
    {
        if ($this->videoVues->removeElement($videoVue)) {
            // set the owning side to null (unless already changed)
            if ($videoVue->getUser() === $this) {
                $videoVue->setUser(null);
            }
        }

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getReferrerProfile(): ?ReferrerProfile
    {
        return $this->referrerProfile;
    }

    public function setReferrerProfile(?ReferrerProfile $referrerProfile): static
    {
        // unset the owning side of the relation if necessary
        if ($referrerProfile === null && $this->referrerProfile !== null) {
            $this->referrerProfile->setReferrer(null);
        }

        // set the owning side of the relation if necessary
        if ($referrerProfile !== null && $referrerProfile->getReferrer() !== $this) {
            $referrerProfile->setReferrer($this);
        }

        $this->referrerProfile = $referrerProfile;

        return $this;
    }

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): static
    {
        // unset the owning side of the relation if necessary
        if ($employe === null && $this->employe !== null) {
            $this->employe->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($employe !== null && $employe->getUser() !== $this) {
            $employe->setUser($this);
        }

        $this->employe = $employe;

        return $this;
    }

    public function getCredit(): ?Credit
    {
        return $this->credit;
    }

    public function setCredit(?Credit $credit): static
    {
        // unset the owning side of the relation if necessary
        if ($credit === null && $this->credit !== null) {
            $this->credit->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($credit !== null && $credit->getUser() !== $this) {
            $credit->setUser($this);
        }

        $this->credit = $credit;

        return $this;
    }

    /**
     * @return Collection<int, History>
     */
    public function getHistories(): Collection
    {
        return $this->histories;
    }

    public function addHistory(History $history): static
    {
        if (!$this->histories->contains($history)) {
            $this->histories->add($history);
            $history->setUser($this);
        }

        return $this;
    }

    public function removeHistory(History $history): static
    {
        if ($this->histories->removeElement($history)) {
            // set the owning side to null (unless already changed)
            if ($history->getUser() === $this) {
                $history->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setUser($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getUser() === $this) {
                $transaction->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PurchasedContact>
     */
    public function getPurchasedContacts(): Collection
    {
        return $this->purchasedContacts;
    }

    public function addPurchasedContact(PurchasedContact $purchasedContact): static
    {
        if (!$this->purchasedContacts->contains($purchasedContact)) {
            $this->purchasedContacts->add($purchasedContact);
            $purchasedContact->setBuyer($this);
        }

        return $this;
    }

    public function removePurchasedContact(PurchasedContact $purchasedContact): static
    {
        if ($this->purchasedContacts->removeElement($purchasedContact)) {
            // set the owning side to null (unless already changed)
            if ($purchasedContact->getBuyer() === $this) {
                $purchasedContact->setBuyer(null);
            }
        }

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setCustomer($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getCustomer() === $this) {
                $order->setCustomer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BoostVisibility>
     */
    public function getBoostVisibilities(): Collection
    {
        return $this->boostVisibilities;
    }

    public function addBoostVisibility(BoostVisibility $boostVisibility): static
    {
        if (!$this->boostVisibilities->contains($boostVisibility)) {
            $this->boostVisibilities->add($boostVisibility);
            $boostVisibility->setUser($this);
        }

        return $this;
    }

    public function removeBoostVisibility(BoostVisibility $boostVisibility): static
    {
        if ($this->boostVisibilities->removeElement($boostVisibility)) {
            // set the owning side to null (unless already changed)
            if ($boostVisibility->getUser() === $this) {
                $boostVisibility->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ActivityLog>
     */
    public function getActivityLogs(): Collection
    {
        return $this->activityLogs;
    }

    public function addActivityLog(ActivityLog $activityLog): static
    {
        if (!$this->activityLogs->contains($activityLog)) {
            $this->activityLogs->add($activityLog);
            $activityLog->setUser($this);
        }

        return $this;
    }

    public function removeActivityLog(ActivityLog $activityLog): static
    {
        if ($this->activityLogs->removeElement($activityLog)) {
            // set the owning side to null (unless already changed)
            if ($activityLog->getUser() === $this) {
                $activityLog->setUser(null);
            }
        }

        return $this;
    }
    
    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setUser($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getUser() === $this) {
                $event->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setAuthor($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Contract>
     */
    public function getContracts(): Collection
    {
        return $this->contracts;
    }

    public function addContract(Contract $contract): static
    {
        if (!$this->contracts->contains($contract)) {
            $this->contracts->add($contract);
            $contract->setUser($this);
        }

        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            // set the owning side to null (unless already changed)
            if ($contract->getUser() === $this) {
                $contract->setUser(null);
            }
        }

        return $this;
    }

    public function getFacebookId(): ?string
    {
        return $this->facebookId;
    }

    public function setFacebookId(?string $facebookId): static
    {
        $this->facebookId = $facebookId;

        return $this;
    }

    /**
     * @return Collection<int, ContestEntry>
     */
    public function getContestEntries(): Collection
    {
        return $this->contestEntries;
    }

    public function addContestEntry(ContestEntry $contestEntry): static
    {
        if (!$this->contestEntries->contains($contestEntry)) {
            $this->contestEntries->add($contestEntry);
            $contestEntry->setUser($this);
        }

        return $this;
    }

    public function removeContestEntry(ContestEntry $contestEntry): static
    {
        if ($this->contestEntries->removeElement($contestEntry)) {
            // set the owning side to null (unless already changed)
            if ($contestEntry->getUser() === $this) {
                $contestEntry->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Lead>
     */
    public function getLeads(): Collection
    {
        return $this->leads;
    }

    public function addLead(Lead $lead): static
    {
        if (!$this->leads->contains($lead)) {
            $this->leads->add($lead);
            $lead->setUser($this);
        }

        return $this;
    }

    public function removeLead(Lead $lead): static
    {
        if ($this->leads->removeElement($lead)) {
            // set the owning side to null (unless already changed)
            if ($lead->getUser() === $this) {
                $lead->setUser(null);
            }
        }

        return $this;
    }
}
