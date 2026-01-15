# Kanboard-Plugin-JWTAuth

JWT authentication plugin for Kanboard API. Supports dual token mode (access + refresh) with token revocation.

## Installation

1. Extract to `plugins/JWTAuth` directory
2. Enable in **Settings > JWT Auth**

## Configuration

| Setting | Description | Default |
|---------|-------------|---------|
| JWT Secret | Signing key (auto-generated if empty) | - |
| JWT Expiration | Legacy token TTL (seconds) | 259200 (3 days) |
| Access Token Expiration | Access token TTL (enables dual mode) | - |
| Refresh Token Expiration | Refresh token TTL | 2592000 (30 days) |

## API Methods

| Method | Permission | Description |
|--------|------------|-------------|
| `getJWTPlugin` | Any user | Get plugin info and available methods |
| `getJWTToken` | Any user | Get token(s) with basic auth |
| `refreshJWTToken` | Any user | Exchange refresh token for new access token |
| `revokeJWTToken` | Any user | Revoke own token only |
| `revokeUserJWTTokens` | Admin | Revoke all tokens for a specific user |
| `revokeAllJWTTokens` | Admin | Revoke all tokens in system |

## Usage

### Get Plugin Info

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getJWTPlugin","id":1}' \
  http://localhost/jsonrpc.php
```

**Response:**
```json
{
  "result": {
    "name": "JWTAuth",
    "version": "1.2.0",
    "description": "Provide JWT authentication for Kanboard API",
    "methods": [...]
  }
}
```

### Get Token

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getJWTToken","id":1}' \
  http://localhost/jsonrpc.php
```

**Response (Dual Mode):**
```json
{
  "result": {
    "access_token":  "...",
    "refresh_token": "..."
  }
}
```

### Use Token

```sh
curl -u "user:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"getAllProjects","id":1}' \
  http://localhost/jsonrpc.php
```

### Refresh Token

```sh
curl -u "user:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"refreshJWTToken","id":1,"params":["REFRESH_TOKEN"]}' \
  http://localhost/jsonrpc.php
```

**Response (Token Rotation):**
```json
{
  "result": {
    "access_token":  "...",
    "refresh_token": "..."
  }
}
```

### Revoke Token

```sh
curl -u "user:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"revokeJWTToken","id":1,"params":["TOKEN_TO_REVOKE"]}' \
  http://localhost/jsonrpc.php
```

### Revoke User Tokens (Admin)

```sh
curl -u "admin:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"revokeUserJWTTokens","id":1,"params":[USER_ID]}' \
  http://localhost/jsonrpc.php
```

## Troubleshooting

### "Method not found" Error

All JWT API methods require JWT authentication to be enabled first. If you see:

```json
{"error":{"code":-32601,"message":"Method not found"}}
```

**Solution:** Enable JWT in **Settings > JWT Auth** and save.

---

# Kanboard-Plugin-JWTAuth (繁體中文)

Kanboard API 的 JWT 認證外掛。支援雙 Token 模式（存取 + 刷新）與 Token 撤銷功能。

## 安裝

1. 解壓縮至 `plugins/JWTAuth` 目錄
2. 在 **設定 > JWT Auth** 中啟用

## 設定選項

| 設定 | 說明 | 預設值 |
|-----|------|-------|
| JWT Secret | 簽名金鑰（空白時自動產生） | - |
| JWT Expiration | 傳統模式 Token 有效期（秒） | 259200 (3 天) |
| Access Token Expiration | 存取 Token 有效期（啟用雙 Token 模式） | - |
| Refresh Token Expiration | 刷新 Token 有效期 | 2592000 (30 天) |

## API 方法

| 方法 | 權限 | 說明 |
|-----|------|-----|
| `getJWTPlugin` | 任何用戶 | 取得外掛資訊與可用方法清單 |
| `getJWTToken` | 任何用戶 | 使用基本認證取得 Token |
| `refreshJWTToken` | 任何用戶 | 用刷新 Token 換取新的存取 Token |
| `revokeJWTToken` | 任何用戶 | 僅能撤銷自己的 Token |
| `revokeUserJWTTokens` | 管理員 | 撤銷指定用戶的所有 Token |
| `revokeAllJWTTokens` | 管理員 | 撤銷系統所有 Token |

## 使用方式

### 取得外掛資訊

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getJWTPlugin","id":1}' \
  http://localhost/jsonrpc.php
```

**回應：**
```json
{
  "result": {
    "name": "JWTAuth",
    "version": "1.2.0",
    "description": "Provide JWT authentication for Kanboard API",
    "methods": [...]
  }
}
```

### 取得 Token

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getJWTToken","id":1}' \
  http://localhost/jsonrpc.php
```

**回應（雙 Token 模式）：**
```json
{
  "result": {
    "access_token":  "...",
    "refresh_token": "..."
  }
}
```

### 使用 Token

```sh
curl -u "user:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"getAllProjects","id":1}' \
  http://localhost/jsonrpc.php
```

### 刷新 Token

```sh
curl -u "user:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"refreshJWTToken","id":1,"params":["REFRESH_TOKEN"]}' \
  http://localhost/jsonrpc.php
```

**回應（Token Rotation）：**
```json
{
  "result": {
    "access_token":  "...",
    "refresh_token": "..."
  }
}
```

### 撤銷 Token

```sh
curl -u "user:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"revokeJWTToken","id":1,"params":["TOKEN_TO_REVOKE"]}' \
  http://localhost/jsonrpc.php
```

### 撤銷用戶 Token（管理員）

```sh
curl -u "admin:access_token" -X POST \
  -d '{"jsonrpc":"2.0","method":"revokeUserJWTTokens","id":1,"params":[USER_ID]}' \
  http://localhost/jsonrpc.php
```

## 疑難排解

### 「Method not found」錯誤

所有 JWT API 方法都需要先啟用 JWT 認證。如果你看到：

```json
{"error":{"code":-32601,"message":"Method not found"}}
```

**解決方案：** 在 **設定 > JWT Auth** 中啟用並儲存。
