<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260106152110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE indisponibilite (id INT AUTO_INCREMENT NOT NULL, debut DATE NOT NULL, fin DATE NOT NULL, type VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE client CHANGE latitude latitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE service CHANGE icon icon VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE indisponibilite');
        $this->addSql('ALTER TABLE client CHANGE latitude latitude DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE service CHANGE icon icon VARCHAR(255) NOT NULL');
    }
}
