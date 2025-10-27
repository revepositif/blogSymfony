<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251026000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rendre le champ slug nullable dans la table article';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article MODIFY slug VARCHAR(255) NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article MODIFY slug VARCHAR(255) NOT NULL');
    }
}