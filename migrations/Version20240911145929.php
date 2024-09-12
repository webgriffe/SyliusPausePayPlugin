<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240911145929 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE webgriffe_sylius_pausepay_payment_order (id INT AUTO_INCREMENT NOT NULL, order_id VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, paymentTokenHash VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_164E801E8D9F6D38 (order_id), UNIQUE INDEX UNIQ_164E801E93EEC8B (paymentTokenHash), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE webgriffe_sylius_pausepay_payment_order ADD CONSTRAINT FK_164E801E93EEC8B FOREIGN KEY (paymentTokenHash) REFERENCES sylius_payment_security_token (hash) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE webgriffe_sylius_pausepay_payment_order DROP FOREIGN KEY FK_164E801E93EEC8B');
        $this->addSql('DROP TABLE webgriffe_sylius_pausepay_payment_order');
    }
}
