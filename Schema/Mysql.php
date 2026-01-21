<?php

namespace Kanboard\Plugin\KanproBridge\Schema;

use PDO;

const VERSION = 2;

function version_1(PDO $pdo)
{
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS jwt_revoked_tokens (
            jti VARCHAR(64) NOT NULL,
            user_id INT NOT NULL,
            token_type VARCHAR(16) NOT NULL,
            revoked_at INT NOT NULL,
            expires_at INT NOT NULL,
            PRIMARY KEY (jti),
            INDEX jwt_revoked_tokens_user_idx (user_id),
            INDEX jwt_revoked_tokens_expires_idx (expires_at)
        ) ENGINE=InnoDB CHARSET=utf8mb4
    ');
}

function version_2(PDO $pdo)
{
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS kanpro_user_metadata (
            id INT AUTO_INCREMENT,
            user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            value TEXT,
            changed_by INT NOT NULL DEFAULT 0,
            changed_on INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY kanpro_user_metadata_unique (user_id, name),
            INDEX kanpro_user_metadata_user_idx (user_id)
        ) ENGINE=InnoDB CHARSET=utf8mb4
    ');
}
