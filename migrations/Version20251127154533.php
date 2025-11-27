<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127154533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription ADD COLUMN current_period_start DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE subscription ADD COLUMN current_period_end DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__subscription AS SELECT id, stripe_subscription_id, status, billing_period, cancellation_reason, cancel_at, user_id, plan_id FROM subscription');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('CREATE TABLE subscription (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, stripe_subscription_id VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, billing_period VARCHAR(255) NOT NULL, cancellation_reason VARCHAR(255) DEFAULT NULL, cancel_at DATETIME DEFAULT NULL, user_id INTEGER NOT NULL, plan_id INTEGER NOT NULL, CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES "plan" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO subscription (id, stripe_subscription_id, status, billing_period, cancellation_reason, cancel_at, user_id, plan_id) SELECT id, stripe_subscription_id, status, billing_period, cancellation_reason, cancel_at, user_id, plan_id FROM __temp__subscription');
        $this->addSql('DROP TABLE __temp__subscription');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A3C664D3A76ED395 ON subscription (user_id)');
        $this->addSql('CREATE INDEX IDX_A3C664D3E899029B ON subscription (plan_id)');
    }
}
