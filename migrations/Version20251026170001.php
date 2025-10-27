<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251026170001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la colonne vu pour les commentaires';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment ADD vu TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment DROP vu');
    }
}