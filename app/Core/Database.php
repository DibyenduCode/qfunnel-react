<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
        self::$instance = null;
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = self::$config['host'] ?? Env::get('DB_HOST', '127.0.0.1');
            $port = self::$config['port'] ?? Env::get('DB_PORT', '3306');
            $database = self::$config['database'] ?? Env::get('DB_DATABASE', 'qfunnel_db');
            $username = self::$config['username'] ?? Env::get('DB_USERNAME', 'root');
            $password = self::$config['password'] ?? Env::get('DB_PASSWORD', '');
            $charset = self::$config['charset'] ?? 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

            $initAttr = class_exists('Pdo\Mysql') ? \Pdo\Mysql::ATTR_INIT_COMMAND : 1002;

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                $initAttr                    => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                Logger::error("Database connection failed: " . $e->getMessage());
                throw new RuntimeException("Database connection error. Please verify database configuration.");
            }
        }

        return self::$instance;
    }

    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public static function execute(string $sql, array $params = []): bool
    {
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    public static function beginTransaction(): bool
    {
        if (!self::getConnection()->inTransaction()) {
            return self::getConnection()->beginTransaction();
        }
        return false;
    }

    public static function commit(): bool
    {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->commit();
        }
        return false;
    }

    public static function rollBack(): bool
    {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->rollBack();
        }
        return false;
    }

    public static function inTransaction(): bool
    {
        return self::getConnection()->inTransaction();
    }

    public static function transaction(callable $callback): mixed
    {
        self::beginTransaction();
        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollBack();
            throw $e;
        }
    }
}
