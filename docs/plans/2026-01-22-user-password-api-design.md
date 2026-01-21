# User Password API Design

## Overview

Add JSON-RPC API methods for user password management in KanproBridge plugin.

## API Methods

| Method | Parameters | Permission | Description |
|--------|------------|------------|-------------|
| `changeUserPassword` | `currentPassword`, `newPassword` | Self only | User changes own password (requires current password) |
| `resetUserPassword` | `userId`, `newPassword` | Admin only | Admin resets any user's password |

## Implementation

### New File: `Feature/UserPassword/Model.php`

```php
class Model
{
    public function change($currentPassword, $newPassword)
    public function reset($userId, $newPassword)
}
```

### Logic

**change():**
1. Get current user ID from session
2. Verify current password using `password_verify()`
3. Update password via `userModel->update()`

**reset():**
1. Check if current user is admin
2. Update target user's password via `userModel->update()`

### Error Cases

- Returns `false` if:
  - Permission denied
  - Current password incorrect
  - New password empty
  - User not found

## Plugin.php Changes

1. Add configuration option `kanpro_user_password_enable`
2. Register API methods when enabled
3. Update `getPluginInfo()` to include user_password feature

## Configuration

| Key | Description | Default |
|-----|-------------|---------|
| `kanpro_user_password_enable` | Enable/disable User Password API | Disabled |
