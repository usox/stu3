<?php

declare(strict_types=1);

namespace Stu\Migrations\Pgsql;

use Override;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240822091021_LotteryWinnerBuildplan extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Adds new entity for ship buildplans that lottery winners get.';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stu_lottery_buildplan (id INT GENERATED BY DEFAULT AS IDENTITY NOT NULL, buildplan_id INT NOT NULL, chance INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E8141D9B8638E4E7 ON stu_lottery_buildplan (buildplan_id)');
        $this->addSql('ALTER TABLE stu_lottery_buildplan ADD CONSTRAINT FK_E8141D9B8638E4E7 FOREIGN KEY (buildplan_id) REFERENCES stu_buildplans (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    #[Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stu_lottery_buildplan DROP CONSTRAINT FK_E8141D9B8638E4E7');
        $this->addSql('DROP TABLE stu_lottery_buildplan');
    }
}