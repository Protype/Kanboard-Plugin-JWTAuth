# KanproBridge

Multi-functional Kanboard plugin providing JWT authentication, User Metadata, User Avatar, User Password, User Profile, and Project User API.

## Features

- **JWT Authentication**: Dual token mode (access + refresh) with token revocation
- **User Metadata**: Custom key-value storage per user
- **User Avatar**: Upload and retrieve avatars via API
- **User Password**: Change and reset passwords via API
- **User Profile**: Get and update user profile fields via API
- **Project User**: Extended getProjectUsers/getAssignableUsers returning full user objects

## Installation

1. Extract to `plugins/KanproBridge` directory
2. Enable features in **Settings > KanproBridge**

## Configuration

### JWT Settings

| Setting | Description | Default |
|---------|-------------|---------|
| JWT Secret | Signing key (auto-generated if empty) | - |
| Access Token Expiration | Access token TTL (seconds) | 259200 (3 days) |
| Refresh Token Expiration | Refresh token TTL (seconds) | 2592000 (30 days) |

### User Metadata Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Enable User Metadata | Enable/disable User Metadata API | Disabled |

### User Avatar Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Enable User Avatar | Enable/disable User Avatar API | Disabled |

### User Password Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Enable User Password | Enable/disable User Password API | Disabled |

### User Profile Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Enable User Profile | Enable/disable User Profile API | Disabled |

### Project User Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Enable Project User | Enable/disable Project User API | Disabled |

## API Methods

### JWT Authentication

| Method | Permission | Description |
|--------|------------|-------------|
| `getKanproBridgeStatus` | Any user | Get plugin info and available methods |
| `getJWTToken` | Any user | Get token(s) with basic auth |
| `refreshJWTToken` | Any user | Exchange refresh token for new access token |
| `revokeJWTToken` | Any user | Revoke own token only |
| `revokeUserJWTTokens` | Admin | Revoke all tokens for a specific user |
| `revokeAllJWTTokens` | Admin | Revoke all tokens in system |

### User Metadata

| Method | Permission | Description |
|--------|------------|-------------|
| `getUserMetadata` | Self or Admin | Get all metadata for a user |
| `getUserMetadataByName` | Self or Admin | Get a specific metadata value |
| `saveUserMetadata` | Self or Admin | Save metadata key-value pairs |
| `removeUserMetadata` | Self or Admin | Remove a metadata entry |

### User Avatar

| Method | Permission | Description |
|--------|------------|-------------|
| `uploadUserAvatar` | Self or Admin | Upload avatar (base64 PNG/JPG/GIF) |
| `getUserAvatar` | Self or Admin | Get avatar (base64) |
| `removeUserAvatar` | Self or Admin | Remove avatar |

### User Password

| Method | Permission | Description |
|--------|------------|-------------|
| `changeUserPassword` | Self only | Change own password (requires current) |
| `resetUserPassword` | Admin only | Reset any user's password |

### User Profile

| Method | Permission | Description |
|--------|------------|-------------|
| `getUserProfile` | Self or Admin | Get user profile data |
| `updateUserProfile` | Self or Admin | Update profile (username, name, email, theme, timezone, language, filter) |

### Project User

| Method | Permission | Description |
|--------|------------|-------------|
| `getProjectUsersExtended` | Any user | Get full user objects for all project members |
| `getAssignableUsersExtended` | Any user | Get full user objects for assignable users (excludes viewers) |

## Usage

### Get Plugin Info

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getKanproBridgeStatus","id":1}' \
  http://localhost/jsonrpc.php
```

**Response:**
```json
{
  "result": {
    "name": "KanproBridge",
    "version": "2.3.0",
    "description": "Multi-functional bridge plugin connecting Kanboard and Kanpro interface systems",
    "features": {...}
  }
}
```

### Get Token

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getJWTToken","id":1}' \
  http://localhost/jsonrpc.php
```

**Response:**
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

### Save User Metadata

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"saveUserMetadata","id":1,"params":{"userId":1,"values":{"theme":"dark"}}}' \
  http://localhost/jsonrpc.php
```

### Get User Metadata

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getUserMetadata","id":1,"params":{"userId":1}}' \
  http://localhost/jsonrpc.php
```

### Upload User Avatar

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"uploadUserAvatar","id":1,"params":{"userId":1,"imageData":"BASE64_IMAGE"}}' \
  http://localhost/jsonrpc.php
```

### Get User Avatar

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getUserAvatar","id":1,"params":{"userId":1}}' \
  http://localhost/jsonrpc.php
```

### Change User Password

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"changeUserPassword","id":1,"params":{"currentPassword":"oldpass","newPassword":"newpass"}}' \
  http://localhost/jsonrpc.php
```

### Reset User Password (Admin)

```sh
curl -u "admin:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"resetUserPassword","id":1,"params":{"userId":1,"newPassword":"resetpass"}}' \
  http://localhost/jsonrpc.php
```

### Get User Profile

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getUserProfile","id":1,"params":{"userId":1}}' \
  http://localhost/jsonrpc.php
```

**Response:**
```json
{
  "result": {
    "id": 1,
    "username": "user",
    "name": "User Name",
    "email": "user@example.com",
    "theme": "dark",
    "timezone": "Asia/Taipei",
    "language": "zh_TW",
    "filter": "status:open",
    "role": "app-user",
    "is_active": 1
  }
}
```

### Update User Profile

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"updateUserProfile","id":1,"params":{"userId":1,"values":{"name":"New Name","theme":"light","timezone":"UTC"}}}' \
  http://localhost/jsonrpc.php
```

### Get Project Users (Extended)

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getProjectUsersExtended","id":1,"params":{"projectId":1}}' \
  http://localhost/jsonrpc.php
```

**Response:**
```json
{
  "result": [
    {
      "id": 1,
      "username": "alice",
      "name": "Alice",
      "email": "alice@example.com",
      "role": "app-user",
      "is_active": 1,
      "project_role": "project-manager"
    },
    {
      "id": 2,
      "username": "bob",
      "name": "Bob",
      "email": "bob@example.com",
      "role": "app-user",
      "is_active": 1,
      "project_role": "project-member"
    }
  ]
}
```

### Get Assignable Users (Extended)

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getAssignableUsersExtended","id":1,"params":{"projectId":1}}' \
  http://localhost/jsonrpc.php
```

**Note:** This method excludes users with `project-viewer` role.

## Troubleshooting

### "Method not found" Error

API methods require their feature to be enabled first. If you see:

```json
{"error":{"code":-32601,"message":"Method not found"}}
```

**Solution:** Enable the feature in **Settings > KanproBridge** and save.

---

# KanproBridge (繁體中文)

多功能 Kanboard 外掛，提供 JWT 認證、使用者 Metadata、頭像、密碼、個人資料與專案成員 API。

## 功能

- **JWT 認證**：雙 Token 模式（存取 + 刷新）與 Token 撤銷
- **User Metadata**：使用者自訂鍵值對儲存
- **User Avatar**：透過 API 上傳與取得頭像
- **User Password**：透過 API 更改與重設密碼
- **User Profile**：透過 API 取得與更新個人資料
- **Project User**：擴充版 getProjectUsers/getAssignableUsers，回傳完整使用者物件

## 安裝

1. 解壓縮至 `plugins/KanproBridge` 目錄
2. 在 **設定 > KanproBridge** 中啟用功能

## 設定選項

### JWT 設定

| 設定 | 說明 | 預設值 |
|-----|------|-------|
| JWT Secret | 簽名金鑰（空白時自動產生） | - |
| Access Token Expiration | 存取 Token 有效期（秒） | 259200 (3 天) |
| Refresh Token Expiration | 刷新 Token 有效期（秒） | 2592000 (30 天) |

### User Metadata 設定

| 設定 | 說明 | 預設值 |
|-----|------|-------|
| 啟用 User Metadata | 啟用/停用 User Metadata API | 停用 |

### User Avatar 設定

| 設定 | 說明 | 預設值 |
|-----|------|-------|
| 啟用 User Avatar | 啟用/停用 User Avatar API | 停用 |

### User Password 設定

| 設定 | 說明 | 預設值 |
|-----|------|-------|
| 啟用 User Password | 啟用/停用 User Password API | 停用 |

### User Profile 設定

| 設定 | 說明 | 預設值 |
|-----|------|-------|
| 啟用 User Profile | 啟用/停用 User Profile API | 停用 |

### Project User 設定

| 設定 | 說明 | 預設值 |
|-----|------|-------|
| 啟用 Project User | 啟用/停用 Project User API | 停用 |

## API 方法

### JWT 認證

| 方法 | 權限 | 說明 |
|-----|------|-----|
| `getKanproBridgeStatus` | 任何用戶 | 取得外掛資訊與可用方法清單 |
| `getJWTToken` | 任何用戶 | 使用基本認證取得 Token |
| `refreshJWTToken` | 任何用戶 | 用刷新 Token 換取新的存取 Token |
| `revokeJWTToken` | 任何用戶 | 僅能撤銷自己的 Token |
| `revokeUserJWTTokens` | 管理員 | 撤銷指定用戶的所有 Token |
| `revokeAllJWTTokens` | 管理員 | 撤銷系統所有 Token |

### User Metadata

| 方法 | 權限 | 說明 |
|-----|------|-----|
| `getUserMetadata` | 本人或管理員 | 取得使用者所有 Metadata |
| `getUserMetadataByName` | 本人或管理員 | 取得指定 Metadata 值 |
| `saveUserMetadata` | 本人或管理員 | 儲存 Metadata 鍵值對 |
| `removeUserMetadata` | 本人或管理員 | 移除 Metadata 項目 |

### User Avatar

| 方法 | 權限 | 說明 |
|-----|------|-----|
| `uploadUserAvatar` | 本人或管理員 | 上傳頭像（base64 PNG/JPG/GIF） |
| `getUserAvatar` | 本人或管理員 | 取得頭像（base64） |
| `removeUserAvatar` | 本人或管理員 | 移除頭像 |

### User Password

| 方法 | 權限 | 說明 |
|-----|------|-----|
| `changeUserPassword` | 僅限本人 | 更改自己密碼（需驗證舊密碼） |
| `resetUserPassword` | 僅限管理員 | 重設任何使用者密碼 |

### User Profile

| 方法 | 權限 | 說明 |
|-----|------|-----|
| `getUserProfile` | 本人或管理員 | 取得使用者個人資料 |
| `updateUserProfile` | 本人或管理員 | 更新個人資料（username, name, email, theme, timezone, language, filter） |

### Project User

| 方法 | 權限 | 說明 |
|-----|------|-----|
| `getProjectUsersExtended` | 任何用戶 | 取得專案所有成員的完整使用者物件 |
| `getAssignableUsersExtended` | 任何用戶 | 取得可指派使用者的完整物件（排除 viewer） |

## 使用方式

### 取得外掛資訊

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getKanproBridgeStatus","id":1}' \
  http://localhost/jsonrpc.php
```

**回應：**
```json
{
  "result": {
    "name": "KanproBridge",
    "version": "2.3.0",
    "description": "Multi-functional bridge plugin connecting Kanboard and Kanpro interface systems",
    "features": {...}
  }
}
```

### 取得 Token

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getJWTToken","id":1}' \
  http://localhost/jsonrpc.php
```

**回應：**
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

### 儲存 User Metadata

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"saveUserMetadata","id":1,"params":{"userId":1,"values":{"theme":"dark"}}}' \
  http://localhost/jsonrpc.php
```

### 取得 User Metadata

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getUserMetadata","id":1,"params":{"userId":1}}' \
  http://localhost/jsonrpc.php
```

### 上傳 User Avatar

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"uploadUserAvatar","id":1,"params":{"userId":1,"imageData":"BASE64_IMAGE"}}' \
  http://localhost/jsonrpc.php
```

### 取得 User Avatar

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getUserAvatar","id":1,"params":{"userId":1}}' \
  http://localhost/jsonrpc.php
```

### 更改密碼

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"changeUserPassword","id":1,"params":{"currentPassword":"oldpass","newPassword":"newpass"}}' \
  http://localhost/jsonrpc.php
```

### 重設密碼（管理員）

```sh
curl -u "admin:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"resetUserPassword","id":1,"params":{"userId":1,"newPassword":"resetpass"}}' \
  http://localhost/jsonrpc.php
```

### 取得個人資料

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getUserProfile","id":1,"params":{"userId":1}}' \
  http://localhost/jsonrpc.php
```

**回應：**
```json
{
  "result": {
    "id": 1,
    "username": "user",
    "name": "使用者名稱",
    "email": "user@example.com",
    "theme": "dark",
    "timezone": "Asia/Taipei",
    "language": "zh_TW",
    "filter": "status:open",
    "role": "app-user",
    "is_active": 1
  }
}
```

### 更新個人資料

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"updateUserProfile","id":1,"params":{"userId":1,"values":{"name":"新名稱","theme":"light","timezone":"UTC"}}}' \
  http://localhost/jsonrpc.php
```

### 取得專案成員（擴充版）

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getProjectUsersExtended","id":1,"params":{"projectId":1}}' \
  http://localhost/jsonrpc.php
```

**回應：**
```json
{
  "result": [
    {
      "id": 1,
      "username": "alice",
      "name": "Alice",
      "email": "alice@example.com",
      "role": "app-user",
      "is_active": 1,
      "project_role": "project-manager"
    },
    {
      "id": 2,
      "username": "bob",
      "name": "Bob",
      "email": "bob@example.com",
      "role": "app-user",
      "is_active": 1,
      "project_role": "project-member"
    }
  ]
}
```

### 取得可指派使用者（擴充版）

```sh
curl -u "user:password" -X POST \
  -d '{"jsonrpc":"2.0","method":"getAssignableUsersExtended","id":1,"params":{"projectId":1}}' \
  http://localhost/jsonrpc.php
```

**注意：** 此方法會排除 `project-viewer` 角色的使用者。

## 疑難排解

### 「Method not found」錯誤

API 方法需要先啟用對應功能。如果你看到：

```json
{"error":{"code":-32601,"message":"Method not found"}}
```

**解決方案：** 在 **設定 > KanproBridge** 中啟用功能並儲存。
