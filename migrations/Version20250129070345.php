<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250129070345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_8573DC2EA76ED395');
        $this->addSql('DROP INDEX IDX_8573DC2EA76ED395 ON application');
        $this->addSql('ALTER TABLE application CHANGE user_id portfolio_id INT NOT NULL');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1B96B5643 FOREIGN KEY (portfolio_id) REFERENCES portfolio (id)');
        $this->addSql('CREATE INDEX IDX_A45BDDC1B96B5643 ON application (portfolio_id)');
        $this->addSql('ALTER TABLE application RENAME INDEX idx_8573dc2edcd6110 TO IDX_A45BDDC1DCD6110');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC1B96B5643');
        $this->addSql('DROP INDEX IDX_A45BDDC1B96B5643 ON application');
        $this->addSql('ALTER TABLE application CHANGE portfolio_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_8573DC2EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8573DC2EA76ED395 ON application (user_id)');
        $this->addSql('ALTER TABLE application RENAME INDEX idx_a45bddc1dcd6110 TO IDX_8573DC2EDCD6110');
    }
}
