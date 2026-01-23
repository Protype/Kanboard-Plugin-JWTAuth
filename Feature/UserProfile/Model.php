<?php

namespace Kanboard\Plugin\KanproBridge\Feature\UserProfile;

/**
 * User Profile Model
 *
 * Manages user profile data retrieval and updates via API.
 * Access is restricted to the user themselves or administrators.
 */
class Model
{
    /**
     * Allowed fields for profile updates
     */
    const ALLOWED_FIELDS = [
        'username',
        'name',
        'email',
        'theme',
        'timezone',
        'language',
        'filter',
    ];

    /**
     * Profile fields to return in get response
     */
    const PROFILE_FIELDS = [
        'id',
        'username',
        'name',
        'email',
        'theme',
        'timezone',
        'language',
        'filter',
        'role',
        'is_active',
    ];

    /**
     * @var mixed Container
     */
    private $container;

    /**
     * Constructor
     *
     * @param mixed $container Pimple container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Check if current user can access target user's profile
     *
     * @param int $targetUserId Target user ID
     * @return bool True if access is allowed
     */
    private function canAccess($targetUserId)
    {
        $currentUserId = $this->container['userSession']->getId();
        $isAdmin = $this->container['userSession']->isAdmin();

        // User can access their own profile
        if ($currentUserId === (int) $targetUserId) {
            return true;
        }

        // Admin can access anyone's profile
        if ($isAdmin) {
            return true;
        }

        return false;
    }

    /**
     * Get user profile
     *
     * @param int $userId User ID
     * @return array|false Profile data with avatar or false if access denied
     */
    public function get($userId)
    {
        if (!$this->canAccess($userId)) {
            return false;
        }

        $user = $this->container['userModel']->getById($userId);
        if (empty($user)) {
            return false;
        }

        // Return only profile fields
        $profile = [];
        foreach (self::PROFILE_FIELDS as $field) {
            if (array_key_exists($field, $user)) {
                $profile[$field] = $user[$field];
            }
        }

        // Always include avatar
        $profile['avatar'] = $this->getAvatarData($userId);

        return $profile;
    }

    /**
     * Get avatar data for a user
     *
     * @param int $userId User ID
     * @return string|null Base64 encoded avatar or null if not found
     */
    private function getAvatarData($userId)
    {
        $filename = $this->container['avatarFileModel']->getFilename($userId);
        if (empty($filename)) {
            return null;
        }

        try {
            $blob = $this->container['objectStorage']->get($filename);
            return base64_encode($blob);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update user profile
     *
     * @param int $userId User ID
     * @param array $values Fields to update
     * @return bool True on success, false on failure or access denied
     */
    public function update($userId, array $values)
    {
        if (!$this->canAccess($userId)) {
            return false;
        }

        // Check if user exists
        $user = $this->container['userModel']->getById($userId);
        if (empty($user)) {
            return false;
        }

        // Filter only allowed fields
        $allowedValues = [];
        foreach ($values as $key => $value) {
            if (in_array($key, self::ALLOWED_FIELDS, true)) {
                $allowedValues[$key] = $value;
            }
        }

        // Nothing to update
        if (empty($allowedValues)) {
            return false;
        }

        // Add user ID for update
        $allowedValues['id'] = $userId;

        return $this->container['userModel']->update($allowedValues);
    }
}
