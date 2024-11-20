<?php

declare(strict_types=1);

namespace Stu\Testdata;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionTestDatabaseEntrys extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds default stu_database_entrys.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703434, \'Thalassanebel\', \'\', 4, 7, 22560, 22650);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (1, \'BM Forschung\', \'\', 8, 8, 22526, 22526);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6501001, \'Aerie\', \'\', 1, 1, 20, 6501);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703002, \'Klasse M\', \'\', 5, 6, 401, 401);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703006, \'Klasse O\', \'\', 5, 6, 405, 405);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703008, \'Klasse H\', \'\', 5, 6, 413, 413);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703009, \'Klasse P\', \'\', 5, 6, 415, 415);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703011, \'Klasse D\', \'\', 5, 6, 431, 431);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703019, \'Klasse M\', \'\', 5, 6, 201, 201);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703021, \'Klasse L\', \'\', 5, 6, 203, 203);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703025, \'Klasse K\', \'\', 5, 6, 211, 211);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703029, \'Klasse P\', \'\', 5, 6, 215, 215);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703032, \'Klasse D\', \'\', 5, 6, 231, 231);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703037, \'Klasse Q\', \'\', 5, 6, 221, 221);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703040, \'Klasse N\', \'\', 5, 6, 223, 223);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (67030049, \'Dichtes Asteroidenfeld\', \'\', 5, 6, 703, 703);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (67030053, \'Dünnes Asteroidenfeld\', \'\', 5, 6, 701, 701);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (67030055, \'Mittleres Asteroidenfeld\', \'\', 5, 6, 702, 702);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6901060, \'Roter Zwerg\', \'\', 6, 5, 1060, 1060);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6703311, \'Handelsposten "Zur goldenen Kugel"\', \'\', 3, 3, 168, 168);
INSERT INTO stu_database_entrys (id, description, data, category_id, type, sort, object_id) VALUES (6704252, \'Stempor\'\'Arr\', \'\', 7, 4, 148, 252);
        ');
    }
}
