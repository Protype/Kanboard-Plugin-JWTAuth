<?php

namespace Kanboard\Plugin\JWTAuth\Schema;

use PDO;

const VERSION = 1;

function version_1(PDO $pdo)
{
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS jwt_revoked_tokens (
            jti VARCHAR(64) PRIMARY KEY,
            user_id INTEGER NOT NULL,
            token_type VARCHAR(16) NOT NULL,
            revoked_at INTEGER NOT NULL,
            expires_at INTEGER NOT NULL
        )
    ');

    $pdo->exec('CREATE INDEX IF NOT EXISTS jwt_revoked_tokens_user_idx ON jwt_revoked_tokens(user_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS jwt_revoked_tokens_expires_idx ON jwt_revoked_tokens(expires_at)');
}
