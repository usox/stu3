# ST-Universe Source

[![Unittests](https://github.com/st-universe/core/actions/workflows/unittests.yml/badge.svg?branch=master)](https://github.com/st-universe/core/actions/workflows/unittests.yml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/st-universe/core/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/st-universe/core)
[![Code Coverage](https://scrutinizer-ci.com/g/st-universe/core/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/st-universe/core/?branch=master)

## Lokales Setup

Benötigte Software: Linux (geht u.U. auch unter macOS) docker, php 8.4, composer, git.

- Repository forken und das Source-Code auschecken
- config.dist.json nach config.json kopieren und anpassen
- make init
- make dev-create-db
- make migrateDatabase
- ./bin/cli game:reset
- make dev-serve
- Use the cli command to create a new user `./bin/cli user:create --help`

Danach sollte die Software via <http://localhost:1337> erreichbar sein, ggf. kann
es sein, dass der Hostname (stu-db) nicht aufgelöst werden kann. In diesem
Fall muss für die im `docker-compose.yml` File hinterlegte IP ein Eintrag in
der Hosts-Datei des Betriebssystems gemacht werden.

## Server-Deployment

Code:

- Repository auschecken
- make init-production
- make dirs
- rsync -rv --delete-after --exclude=assets --links --exclude=config.json --exclude=src/Public/admin/manage --exclude=src/Public/avatare/* src bin vendor Makefile cli-config.php config.dist.json /path/to/stuniverse-source/
- cd /path/to/stuniverse-source && make clearCache && make migrateDatabase

### Assets

- Repository auschecken
- php generator/building_generator/gen.php
- php generator/field_generator/generator.php
- rsync -rv --delete-after --exclude=dist --exclude=".git" . /path/to/stuniverse-source/assets/

## DB-Änderungen via Doctrine-Migrations

- Entities editieren und danach Proxies generieren:

```shell
bin/doctrine orm:generate-proxies
```

- DB-Änderungen mit Script generieren. In src/Migrations/Sqlite darf immer nur die aktuellste Datei liegen!

```shell
generate-migrations.sh
```

- DB-Änderungen einspielen:

```shell
vendor/bin/doctrine-migrations migrate --all-or-nothing --allow-no-migration --quiet -vv
```

- Änderungen der Entities zusammen mit den Migration PHPs einchecken

## DB-Dump aus Backups einspielen

- aktuelles Schema umbenennen, z.B. in 'stuDamaged'
- neue Datenbank mit Original-Name erstellen, z.B. 'stu'
- auf Kommandozeile in den postgres user einloggen, mittels 'sudo su - postgres'
- in Backup-Folder wechseln, z.B: core/dist/db/backup
- Backup einspielen mittels 'pg_restore -d stu -U postgres -C dd-MM-yyyy.dump'

## Automatische Scripte (Rundenwechsel, etc)

Um alle automatisierte Scripte zu den definierten Zeiten laufen zu lassen, muss folgender `cronjob` hinzugefügt werden.

```shell
* * * * * cd /path/to/stu/core && vendor/bin/crunz schedule:run
```

Die Tasks und deren Schedules werden in Dateien innerhalb des `resource/cron` Ordners definiert.

## Command Line Interface (cli)

Mittels dem Shell-Befehl `./bin/cli` kann das STU CLI gestartet werden. Hier können diverse Befehle ausgeführt werden,
z.B. das manuelle Auslösen der Rundenwechsel. Alle verfügbaren Befehle können via `./bin/cli --help` eingesehen werden.
