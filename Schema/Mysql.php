<?php

namespace Kanboard\Plugin\JWTAuth\Schema;

use PDO;

const VERSION = 1;

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
