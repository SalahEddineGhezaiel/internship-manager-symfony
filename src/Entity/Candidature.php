<?php

namespace App\Entity;

use App\Repository\CandidatureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
#[ORM\Table(name: 'candidature')]
class Candidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateSoumission = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateReponse = null;

    #[ORM\Column(length: 50)]
    private string $statut = 'EN_ATTENTE';

    #[ORM\Column(type: Types::TEXT)]
    private ?string $lettreMotivation = null;

    #[ORM\Column(length: 255)]
    private ?string $cvFichier = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Etudiant $etudiant = null;

    #[ORM\ManyToOne(inversedBy: 'candidatures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OffreDeStage $offreDeStage = null;

    public function __construct()
    {
        $this->dateSoumission = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateSoumission(): ?\DateTimeImmutable
    {
        return $this->dateSoumission;
    }

    public function setDateSoumission(\DateTimeImmutable $dateSoumission): static
    {
        $this->dateSoumission = $dateSoumission;

        return $this;
    }

    public function getDateReponse(): ?\DateTimeImmutable
    {
        return $this->dateReponse;
    }

    public function setDateReponse(?\DateTimeImmutable $dateReponse): static
    {
        $this->dateReponse = $dateReponse;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getLettreMotivation(): ?string
    {
        return $this->lettreMotivation;
    }

    public function setLettreMotivation(string $lettreMotivation): static
    {
        $this->lettreMotivation = $lettreMotivation;

        return $this;
    }

    public function getCvFichier(): ?string
    {
        return $this->cvFichier;
    }

    public function setCvFichier(string $cvFichier): static
    {
        $this->cvFichier = $cvFichier;

        return $this;
    }

    public function getEtudiant(): ?Etudiant
    {
        return $this->etudiant;
    }

    public function setEtudiant(?Etudiant $etudiant): static
    {
        $this->etudiant = $etudiant;

        return $this;
    }

    public function getOffreDeStage(): ?OffreDeStage
    {
        return $this->offreDeStage;
    }

    public function setOffreDeStage(?OffreDeStage $offreDeStage): static
    {
        $this->offreDeStage = $offreDeStage;

        return $this;
    }
}
