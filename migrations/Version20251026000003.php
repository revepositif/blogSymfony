<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251026000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rendre le champ slug nullable dans la table category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category MODIFY slug VARCHAR(100) NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category MODIFY slug VARCHAR(100) NOT NULL');
    }
}