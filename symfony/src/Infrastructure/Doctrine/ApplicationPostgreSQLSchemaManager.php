<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Name\OptionallyQualifiedName;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\DBAL\Schema\Table;

final class ApplicationPostgreSQLSchemaManager extends PostgreSQLSchemaManager
{
    /**
     * @return list<Table>
     *
     * @throws Exception
     */
    public function listTables(): array
    {
        $tables = parent::listTables();

        foreach ($tables as $table) {
            if (strtolower($table->getObjectName()->toString()) === 'training'
                && $table->hasIndex('training_no_busy_overlap_per_worktime')
            ) {
                $table->dropIndex('training_no_busy_overlap_per_worktime');
            }
        }

        return $tables;
    }

    /**
     * @return array<string, Index>
     *
     * @throws Exception
     */
    public function listTableIndexes(string $table): array
    {
        /** @var array<string, Index> $indexes */
        $indexes = $this->filterTrainingIndexes($table, parent::listTableIndexes($table));

        return $indexes;
    }

    /**
     * @return list<Index>
     *
     * @throws Exception
     */
    public function introspectTableIndexes(OptionallyQualifiedName $tableName): array
    {
        return array_values($this->filterTrainingIndexes(
            $tableName->getUnqualifiedName()->toString(),
            parent::introspectTableIndexes($tableName),
        ));
    }

    /**
     * @param array<array-key, Index> $indexes
     *
     * @return array<array-key, Index>
     */
    private function filterTrainingIndexes(string $table, array $indexes): array
    {
        if (strtolower($table) !== 'training') {
            return $indexes;
        }

        return array_filter(
            $indexes,
            static fn (Index $index): bool => $index->getObjectName()->toString() !== 'training_no_busy_overlap_per_worktime',
        );
    }
}
