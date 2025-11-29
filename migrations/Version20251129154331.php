<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129154331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE administrateur (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, roles JSON NOT NULL, date_creation DATETIME NOT NULL, UNIQUE INDEX UNIQ_32EB52E8E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE candidature (id INT AUTO_INCREMENT NOT NULL, date_soumission DATETIME NOT NULL, date_reponse DATETIME DEFAULT NULL, statut VARCHAR(50) NOT NULL, lettre_motivation LONGTEXT NOT NULL, cv_fichier VARCHAR(255) NOT NULL, etudiant_id INT NOT NULL, offre_de_stage_id INT NOT NULL, INDEX IDX_E33BD3B8DDEAB1A3 (etudiant_id), INDEX IDX_E33BD3B81D3C911F (offre_de_stage_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE entreprise (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, adresse LONGTEXT DEFAULT NULL, secteur_activite VARCHAR(255) DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, date_creation DATETIME NOT NULL, UNIQUE INDEX UNIQ_D19FA60E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE etudiant (id INT AUTO_INCREMENT NOT NULL, prenom VARCHAR(100) NOT NULL, nom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, roles JSON NOT NULL, date_creation DATETIME NOT NULL, UNIQUE INDEX UNIQ_717E22E3E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offre_de_stage (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, localisation VARCHAR(255) NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, date_creation DATETIME NOT NULL, est_active TINYINT(1) NOT NULL, entreprise_id INT NOT NULL, INDEX IDX_2A493787A4AEAFEA (entreprise_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8DDEAB1A3 FOREIGN KEY (etudiant_id) REFERENCES etudiant (id)');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B81D3C911F FOREIGN KEY (offre_de_stage_id) REFERENCES offre_de_stage (id)');
        $this->addSql('ALTER TABLE offre_de_stage ADD CONSTRAINT FK_2A493787A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B8DDEAB1A3');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B81D3C911F');
        $this->addSql('ALTER TABLE offre_de_stage DROP FOREIGN KEY FK_2A493787A4AEAFEA');
        $this->addSql('DROP TABLE administrateur');
        $this->addSql('DROP TABLE candidature');
        $this->addSql('DROP TABLE entreprise');
        $this->addSql('DROP TABLE etudiant');
        $this->addSql('DROP TABLE offre_de_stage');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
