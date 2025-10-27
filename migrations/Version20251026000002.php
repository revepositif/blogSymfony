<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251026000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression de la colonne slug de la table article';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article DROP COLUMN slug');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article ADD slug VARCHAR(255)');
    }
}