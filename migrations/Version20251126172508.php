<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126172508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__subscription AS SELECT id, status, user_id, plan_id, billing_period FROM subscription');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('CREATE TABLE subscription (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(255) NOT NULL, user_id INTEGER NOT NULL, plan_id INTEGER NOT NULL, billing_period VARCHAR(255) NOT NULL, stripe_subscription_id VARCHAR(255) NOT NULL, CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES "plan" (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO subscription (id, status, user_id, plan_id, billing_period) SELECT id, status, user_id, plan_id, billing_period FROM __temp__subscription');
        $this->addSql('DROP TABLE __temp__subscription');
        $this->addSql('CREATE INDEX IDX_A3C664D3E899029B ON subscription (plan_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A3C664D3A76ED395 ON subscription (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__subscription AS SELECT id, status, billing_period, user_id, plan_id FROM subscription');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('CREATE TABLE subscription (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(255) NOT NULL, billing_period VARCHAR(255) NOT NULL, user_id INTEGER NOT NULL, plan_id INTEGER NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, auto_renew BOOLEAN NOT NULL, CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES "plan" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO subscription (id, status, billing_period, user_id, plan_id) SELECT id, status, billing_period, user_id, plan_id FROM __temp__subscription');
        $this->addSql('DROP TABLE __temp__subscription');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A3C664D3A76ED395 ON subscription (user_id)');
        $this->addSql('CREATE INDEX IDX_A3C664D3E899029B ON subscription (plan_id)');
    }
}
