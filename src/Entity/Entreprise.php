<?php

namespace App\Entity;

use App\Repository\EntrepriseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
#[ORM\Table(name: 'entreprise')]
class Entreprise implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $nom = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $motDePasse = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $secteurActivite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siteWeb = null;

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_ENTREPRISE'];

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateCreation = null;

    
    #[ORM\OneToMany(targetEntity: OffreDeStage::class, mappedBy: 'entreprise', orphanRemoval: true)]
    private Collection $offreDeStages;

    public function __construct()
    {
        $this->offreDeStages = new ArrayCollection();
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
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

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getSecteurActivite(): ?string
    {
        return $this->secteurActivite;
    }

    public function setSecteurActivite(?string $secteurActivite): static
    {
        $this->secteurActivite = $secteurActivite;

        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->siteWeb;
    }

    public function setSiteWeb(?string $siteWeb): static
    {
        $this->siteWeb = $siteWeb;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    
    public function getOffreDeStages(): Collection
    {
        return $this->offreDeStages;
    }

    public function addOffreDeStage(OffreDeStage $offreDeStage): static
    {
        if (!$this->offreDeStages->contains($offreDeStage)) {
            $this->offreDeStages->add($offreDeStage);
            $offreDeStage->setEntreprise($this);
        }

        return $this;
    }

    public function removeOffreDeStage(OffreDeStage $offreDeStage): static
    {
        if ($this->offreDeStages->removeElement($offreDeStage)) {
            if ($offreDeStage->getEntreprise() === $this) {
                $offreDeStage->setEntreprise(null);
            }
        }

        return $this;
    }

    
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    
    public function getRoles(): array
    {
        $roles = $this->roles;
        
        if (empty($roles)) {
            $roles[] = 'ROLE_ENTREPRISE';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    
    public function getPassword(): string
    {
        return $this->motDePasse;
    }

    
    
}
