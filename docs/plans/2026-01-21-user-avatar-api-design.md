# User Avatar API Design

## Overview

Add JSON-RPC API methods for user avatar management in KanproBridge plugin.

## API Methods

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `uploadUserAvatar` | `userId`, `imageData` (base64) | `bool` | Upload/update avatar |
| `getUserAvatar` | `userId` | `string` (base64) or `null` | Get avatar image |
| `removeUserAvatar` | `userId` | `bool` | Remove avatar |

## Permissions

- Users can only operate on their own avatar
- Administrators can operate on any user's avatar
- Returns `false` or `null` if access denied

## Implementation

### New File: `Feature/UserAvatar/Model.php`

```php
class Model extends Base
{
    public function upload($userId, $imageData)
    public function get($userId)
    public function remove($userId)
    private function canAccess($userId)
}
```

### Dependencies

- Uses Kanboard's existing `AvatarFileModel` for storage operations
- Uses `objectStorage` for reading file content
- Permission logic mirrors `UserMetadata/Model.php`

### Image Validation

- Accepted formats: PNG, JPG, GIF
- Validate image header after base64 decode
- Max size: inherit from Kanboard settings

## Plugin.php Changes

1. Add configuration option `kanpro_user_avatar_enable`
2. Register API methods when enabled:
   - `uploadUserAvatar` → `Model::upload`
   - `getUserAvatar` → `Model::get`
   - `removeUserAvatar` → `Model::remove`
3. Update `getPluginInfo()` to include user_avatar feature

## Configuration

| Key | Description | Default |
|-----|-------------|---------|
| `kanpro_user_avatar_enable` | Enable/disable User Avatar API | Disabled |

## Settings UI

Add toggle to existing KanproBridge settings page in `Template/config/settings.php`.
