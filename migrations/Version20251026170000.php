<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251026170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ slug à la table article';
    }

    public function up(Schema $schema): void
    {
        // Ajouter la colonne slug
        $this->addSql('ALTER TABLE article ADD slug VARCHAR(255)');
        
        // Mettre à jour les slugs existants
        $this->addSql('UPDATE article SET slug = CONCAT(LOWER(REPLACE(titre, " ", "-")), "-", id)');
        
        // Rendre la colonne NOT NULL après avoir défini les valeurs
        $this->addSql('ALTER TABLE article MODIFY slug VARCHAR(255) NOT NULL');
        
        // Ajouter l'index unique
        $this->addSql('CREATE UNIQUE INDEX UNIQ_23A0E66989D9B62 ON article (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_23A0E66989D9B62 ON article');
        $this->addSql('ALTER TABLE article DROP slug');
    }
}