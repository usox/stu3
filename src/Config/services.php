<?php

declare(strict_types=1);

namespace Stu\Config;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\Adapter\Redis\RedisCachePool;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Exception;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use Hackzilla\PasswordGenerator\Generator\PasswordGeneratorInterface;
use JBBCode\Parser;
use JsonMapper\JsonMapperFactory;
use JsonMapper\JsonMapperInterface;
use Noodlehaus\Config;
use Noodlehaus\ConfigInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Redis;
use Stu\Lib\ParserWithImage;
use Stu\Lib\ParserWithImageInterface;
use Stu\Lib\Session;
use Stu\Lib\SessionInterface;
use Stu\Lib\StuBbCodeDefinitionSet;
use Stu\Lib\StuBbCodeWithImageDefinitionSet;
use Stu\Module\Control\GameController;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Tal\TalPage;
use Stu\Module\Tal\TalPageInterface;
use Ubench;
use function DI\autowire;

return [
    ErrorHandler::class => autowire(),
    ConfigInterface::class => function (): ConfigInterface {
        $path = __DIR__ . '/../../';
        return new Config(
            [
                sprintf('%s/config.dist.json', $path),
                sprintf('?%s/config.json', $path),
            ]
        );
    },
    CacheItemPoolInterface::class => function (ContainerInterface $c): CacheItemPoolInterface {
        $config = $c->get(ConfigInterface::class);

        if ($config->get('debug.debug_mode') === true) {
            return new ArrayCachePool();
        } else {
            $redis = new Redis();

            if ($config->has('cache.redis_socket')) {
                try {
                    $redis->connect($config->get('cache.redis_socket'));
                } catch (Exception $e) {
                    $redis->connect(
                        $config->get('cache.redis_host'),
                        $config->get('cache.redis_port')
                    );
                }
            } else {
                $redis->connect(
                    $config->get('cache.redis_host'),
                    $config->get('cache.redis_port')
                );
            }
            $redis->setOption(Redis::OPT_PREFIX, $config->get('db.database'));

            return new RedisCachePool($redis);
        }
    },
    SessionInterface::class => autowire(Session::class),
    EntityManagerInterface::class => function (ContainerInterface $c): EntityManagerInterface {
        $config = $c->get(ConfigInterface::class);

        $emConfig = ORMSetup::createAnnotationMetadataConfiguration(
            [__DIR__ . '/../Orm/Entity/'],
            $config->get('debug.debug_mode') === true,
            __DIR__ . '/../OrmProxy/',
            $c->get(CacheItemPoolInterface::class)
        );
        $emConfig->setAutoGenerateProxyClasses(0);
        $emConfig->setProxyNamespace($config->get('db.proxy_namespace'));

        $manager = new EntityManager(
            $c->get(Connection::class),
            $emConfig
        );

        $manager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'integer');
        return $manager;
    },
    Connection::class => fn (ConfigInterface $config): Connection => DriverManager::getConnection([
        'driver' => 'pdo_pgsql',
        'user' => $config->get('db.user'),
        'password' => $config->get('db.pass'),
        'dbname' => $config->get('db.database'),
        'host'  => $config->get('db.host'),
        'charset' => 'utf8',
    ]),
    TalPageInterface::class => autowire(TalPage::class),
    GameControllerInterface::class => autowire(GameController::class),
    Parser::class => function (): Parser {
        $parser = new Parser();
        $parser->addCodeDefinitionSet(new StuBbCodeDefinitionSet());
        return $parser;
    },
    ParserWithImageInterface::class => function (): ParserWithImage {
        $parser = new Parser();
        $parser->addCodeDefinitionSet(new StuBbCodeWithImageDefinitionSet());
        return new ParserWithImage($parser);
    },
    JsonMapperInterface::class => function (): JsonMapperInterface {
        return (new JsonMapperFactory())->bestFit();
    },
    Ubench::class => function (): Ubench {
        $bench = new Ubench();
        $bench->start();

        return $bench;
    },
    PasswordGeneratorInterface::class => function (): PasswordGeneratorInterface {
        $generator = new ComputerPasswordGenerator();

        $generator
            ->setOptionValue(ComputerPasswordGenerator::OPTION_UPPER_CASE, true)
            ->setOptionValue(ComputerPasswordGenerator::OPTION_LOWER_CASE, true)
            ->setOptionValue(ComputerPasswordGenerator::OPTION_NUMBERS, true)
            ->setOptionValue(ComputerPasswordGenerator::OPTION_SYMBOLS, false)
            ->setOptionValue(ComputerPasswordGenerator::OPTION_LENGTH, 10);

        return $generator;
    },
];
