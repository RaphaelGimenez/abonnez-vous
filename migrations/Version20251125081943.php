<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251125081943 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE hello');
        $this->addSql('CREATE TEMPORARY TABLE __temp__plan AS SELECT id, name, monthly_price, annual_price FROM "plan"');
        $this->addSql('DROP TABLE "plan"');
        $this->addSql('CREATE TABLE "plan" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, monthly_price INTEGER NOT NULL, yearly_price INTEGER NOT NULL)');
        $this->addSql('INSERT INTO "plan" (id, name, monthly_price, yearly_price) SELECT id, name, monthly_price, annual_price FROM __temp__plan');
        $this->addSql('DROP TABLE __temp__plan');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE hello (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, greeted BOOLEAN NOT NULL, name VARCHAR(255) DEFAULT NULL COLLATE "BINARY")');
        $this->addSql('CREATE TEMPORARY TABLE __temp__plan AS SELECT id, name, monthly_price, yearly_price FROM "plan"');
        $this->addSql('DROP TABLE "plan"');
        $this->addSql('CREATE TABLE "plan" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, monthly_price INTEGER NOT NULL, annual_price INTEGER NOT NULL)');
        $this->addSql('INSERT INTO "plan" (id, name, monthly_price, annual_price) SELECT id, name, monthly_price, yearly_price FROM __temp__plan');
        $this->addSql('DROP TABLE __temp__plan');
    }
}
