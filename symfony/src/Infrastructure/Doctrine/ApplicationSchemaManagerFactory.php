<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\SchemaManagerFactory;

final readonly class ApplicationSchemaManagerFactory implements SchemaManagerFactory
{
    public function __construct(private SchemaManagerFactory $defaultFactory)
    {
    }

    /**
     * @return AbstractSchemaManager<*>
     */
    public function createSchemaManager(Connection $connection): AbstractSchemaManager
    {
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            return new ApplicationPostgreSQLSchemaManager($connection, $platform);
        }

        return $this->defaultFactory->createSchemaManager($connection);
    }
}
