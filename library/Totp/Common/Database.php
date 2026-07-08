<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Totp\Common;

use Icinga\Application\Config as AppConfig;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Config as SqlConfig;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\QueryBuilder;
use ipl\Sql\Select;
use PDO;

/**
 * Database connection provider for the TOTP module
 *
 * Lazily creates and caches a single {@see Connection} configured from the
 * resource named in the module's `[database]` section. Applies MySQL strict
 * mode and a PostgreSQL-specific `SELECT DISTINCT` / `ORDER BY` fix at
 * connection time.
 */
abstract class Database
{
    /** @var ?Connection The database connection */
    protected static ?Connection $connection = null;

    /**
     * Get a connection to the database
     *
     * @return Connection
     */
    public static function connection(): Connection
    {
        return static::$connection ??= self::getConnection();
    }

    /**
     * Create the database connection
     *
     * @return Connection
     *
     * @throws ConfigurationError If the related resource configuration does not exist
     */
    private static function getConnection(): Connection
    {
        $config = new SqlConfig(ResourceFactory::getResourceConfig(
            AppConfig::module('totp')->get('database', 'resource')
        ));

        $config->options = [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ];
        if ($config->db === 'mysql') {
            $config->options[Pdo\Mysql::ATTR_INIT_COMMAND] = "SET SESSION SQL_MODE='STRICT_TRANS_TABLES"
                . ",NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
        }

        $db = new Connection($config);

        $adapter = $db->getAdapter();
        if ($adapter instanceof Pgsql) {
            $db->getQueryBuilder()
                ->on(QueryBuilder::ON_ASSEMBLE_SELECT, function (Select $select): void {
                    // For SELECT DISTINCT, all ORDER BY columns must appear in SELECT list.
                    if (! $select->getDistinct() || ! $select->hasOrderBy()) {
                        return;
                    }

                    $candidates = [];
                    foreach ($select->getOrderBy() as [$columnOrAlias, $_]) {
                        if ($columnOrAlias instanceof Expression) {
                            // Expressions can be and include anything,
                            // also columns that aren't already part of the SELECT list,
                            // so we're not trying to guess anything here.
                            // Such expressions must be in the SELECT list if necessary and
                            // referenced manually with an alias in ORDER BY.
                            continue;
                        }

                        $candidates[$columnOrAlias] = true;
                    }

                    foreach ($select->getColumns() as $alias => $column) {
                        if (is_int($alias)) {
                            if ($column instanceof Expression) {
                                // This is the complement to the above consideration.
                                // If it is an unaliased expression, ignore it.
                                continue;
                            }
                        } else {
                            unset($candidates[$alias]);
                        }

                        if (! $column instanceof Expression) {
                            unset($candidates[$column]);
                        }
                    }

                    if ($candidates !== []) {
                        $select->columns(array_keys($candidates));
                    }
                });
        }

        return $db;
    }
}
