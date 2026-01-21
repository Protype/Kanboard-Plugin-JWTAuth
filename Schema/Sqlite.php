<?php

namespace Kanboard\Plugin\KanproBridge\Schema;

use PDO;

const VERSION = 2;

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

function version_2(PDO $pdo)
{
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS kanpro_user_metadata (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            value TEXT DEFAULT \'\',
            changed_by INTEGER NOT NULL DEFAULT 0,
            changed_on INTEGER NOT NULL DEFAULT 0,
            UNIQUE(user_id, name)
        )
    ');

    $pdo->exec('CREATE INDEX IF NOT EXISTS kanpro_user_metadata_user_idx ON kanpro_user_metadata(user_id)');
}
