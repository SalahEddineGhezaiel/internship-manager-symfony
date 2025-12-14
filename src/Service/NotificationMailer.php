<?php

namespace App\Service;

use App\Entity\Candidature;
use App\Entity\Entreprise;
use App\Entity\Etudiant;
use App\Entity\OffreDeStage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationMailer
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    private function send(Email $email): void
    {
        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            // Swallow mail errors in dev to not break UX; consider logging in prod
        }
    }

    public function sendApplicationReceived(Entreprise $entreprise, OffreDeStage $offre, Candidature $candidature): void
    {
        $email = (new Email())
            ->from('no-reply@gestion-stages.local')
            ->to($entreprise->getEmail())
            ->subject('Nouvelle candidature reçue')
            ->text(sprintf(
                "Bonjour %s,\n\nUne nouvelle candidature a été soumise pour votre offre '%s'.\n\nCandidat: %s %s\nSoumise le: %s\n\nConnectez-vous pour la consulter.",
                $entreprise->getNom(),
                $offre->getTitre(),
                $candidature->getEtudiant()->getPrenom(),
                $candidature->getEtudiant()->getNom(),
                $candidature->getDateSoumission()->format('d/m/Y H:i')
            ));

        $this->send($email);
    }

    public function sendCandidatureStatus(Etudiant $etudiant, Candidature $candidature): void
    {
        $status = $candidature->getStatut();
        $subject = match ($status) {
            'ACCEPTEE' => 'Votre candidature a été acceptée',
            'REFUSEE' => 'Votre candidature a été refusée',
            default => 'Mise à jour de votre candidature',
        };

        $email = (new Email())
            ->from('no-reply@gestion-stages.local')
            ->to($etudiant->getEmail())
            ->subject($subject)
            ->text(sprintf(
                "Bonjour %s %s,\n\nVotre candidature pour l'offre '%s' a été mise à jour.\nStatut: %s\n\nConnectez-vous pour plus de détails.",
                $etudiant->getPrenom(),
                $etudiant->getNom(),
                $candidature->getOffreDeStage()->getTitre(),
                $status
            ));

        $this->send($email);
    }
}
