<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251024170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial tables for Article, ArticleLike, Category, Comment and User (safe - uses IF NOT EXISTS)';
    }

    public function up(Schema $schema): void
    {
        // Create `user` table
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `user` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `email` VARCHAR(180) NOT NULL,
  `roles` JSON NOT NULL,
  `password` LONGTEXT NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `nom` VARCHAR(100) NOT NULL,
  `date_creation` DATETIME NOT NULL,
  `est_verifie` TINYINT(1) NOT NULL,
  `token_verification` VARCHAR(255) DEFAULT NULL,
  `date_verification` DATETIME DEFAULT NULL,
  `reset_token` VARCHAR(255) DEFAULT NULL,
  `reset_token_created_at` DATETIME DEFAULT NULL,
  `reset_token_expires_at` DATETIME DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `biographie` LONGTEXT DEFAULT NULL,
  UNIQUE INDEX UNIQ_USER_EMAIL (`email`),
  PRIMARY KEY(`id`) 
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
SQL
        );

        // Create `article` table
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `article` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `contenu` LONGTEXT NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `date_creation` DATETIME NOT NULL,
  `date_publication` DATETIME DEFAULT NULL,
  `statut` VARCHAR(20) NOT NULL,
  `auteur_id` INT NOT NULL,
  UNIQUE INDEX UNIQ_ARTICLE_SLUG (`slug`),
  INDEX IDX_ARTICLE_AUTEUR (`auteur_id`),
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
SQL
        );

        // Create `category` table
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `category` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `nom` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` LONGTEXT DEFAULT NULL,
  UNIQUE INDEX UNIQ_CATEGORY_SLUG (`slug`),
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
SQL
        );

        // Create join table for article <-> category
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `article_category` (
  `article_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  INDEX IDX_ARTICLE_CATEGORY_ARTICLE (`article_id`),
  INDEX IDX_ARTICLE_CATEGORY_CATEGORY (`category_id`),
  PRIMARY KEY(`article_id`, `category_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
SQL
        );

        // Create `comment` table
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `comment` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `contenu` LONGTEXT NOT NULL,
  `date_creation` DATETIME NOT NULL,
  `statut` VARCHAR(20) NOT NULL,
  `article_id` INT NOT NULL,
  `auteur_id` INT NOT NULL,
  `parent_id` INT DEFAULT NULL,
  INDEX IDX_COMMENT_ARTICLE (`article_id`),
  INDEX IDX_COMMENT_AUTEUR (`auteur_id`),
  INDEX IDX_COMMENT_PARENT (`parent_id`),
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
SQL
        );

        // Create `article_like` table
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS `article_like` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `user_id` INT NOT NULL,
  `article_id` INT NOT NULL,
  `date_creation` DATETIME NOT NULL,
  INDEX IDX_ARTICLE_LIKE_USER (`user_id`),
  INDEX IDX_ARTICLE_LIKE_ARTICLE (`article_id`),
  UNIQUE INDEX UNIQ_USER_ARTICLE_LIKE (`user_id`, `article_id`),
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
SQL
        );

        // Foreign keys
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_ARTICLE_AUTEUR FOREIGN KEY (auteur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE article_category ADD CONSTRAINT FK_ARTICLE_CATEGORY_ARTICLE FOREIGN KEY (article_id) REFERENCES article (id)');
        $this->addSql('ALTER TABLE article_category ADD CONSTRAINT FK_ARTICLE_CATEGORY_CATEGORY FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_COMMENT_ARTICLE FOREIGN KEY (article_id) REFERENCES article (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_COMMENT_AUTEUR FOREIGN KEY (auteur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_COMMENT_PARENT FOREIGN KEY (parent_id) REFERENCES comment (id)');
        $this->addSql('ALTER TABLE article_like ADD CONSTRAINT FK_ARTICLE_LIKE_USER FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE article_like ADD CONSTRAINT FK_ARTICLE_LIKE_ARTICLE FOREIGN KEY (article_id) REFERENCES article (id)');
    }

    public function down(Schema $schema): void
    {
        // Drop in reverse order to avoid FK constraint problems
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY IF EXISTS FK_COMMENT_PARENT');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY IF EXISTS FK_COMMENT_AUTEUR');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY IF EXISTS FK_COMMENT_ARTICLE');
        $this->addSql('ALTER TABLE article_category DROP FOREIGN KEY IF EXISTS FK_ARTICLE_CATEGORY_CATEGORY');
        $this->addSql('ALTER TABLE article_category DROP FOREIGN KEY IF EXISTS FK_ARTICLE_CATEGORY_ARTICLE');
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY IF EXISTS FK_ARTICLE_AUTEUR');
        $this->addSql('ALTER TABLE article_like DROP FOREIGN KEY IF EXISTS FK_ARTICLE_LIKE_ARTICLE');
        $this->addSql('ALTER TABLE article_like DROP FOREIGN KEY IF EXISTS FK_ARTICLE_LIKE_USER');

        $this->addSql('DROP TABLE IF EXISTS article_like');
        $this->addSql('DROP TABLE IF EXISTS comment');
        $this->addSql('DROP TABLE IF EXISTS article_category');
        $this->addSql('DROP TABLE IF EXISTS category');
        $this->addSql('DROP TABLE IF EXISTS article');
        $this->addSql('DROP TABLE IF EXISTS `user`');
    }
}
