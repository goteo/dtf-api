<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261027180901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds DTF-only description fields to project table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project ADD desc_knowledge LONGTEXT DEFAULT NULL, ADD desc_infrastructure LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project DROP desc_knowledge, DROP desc_infrastructure');
    }
}
