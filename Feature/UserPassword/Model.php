<?php

namespace Kanboard\Plugin\KanproBridge\Feature\UserPassword;

/**
 * User Password Model
 *
 * Manages user password changes via API.
 */
class Model
{
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
     * Change current user's password
     *
     * Requires verification of current password.
     *
     * @param string $currentPassword Current password for verification
     * @param string $newPassword New password to set
     * @return bool True on success, false on failure
     */
    public function change($currentPassword, $newPassword)
    {
        if (empty($currentPassword) || empty($newPassword)) {
            return false;
        }

        $userId = $this->container['userSession']->getId();
        if (empty($userId)) {
            return false;
        }

        // Get user with password hash
        $user = $this->container['userModel']->getById($userId);
        if (empty($user)) {
            return false;
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return false;
        }

        // Update password
        return $this->container['userModel']->update([
            'id' => $userId,
            'password' => $newPassword,
        ]);
    }

    /**
     * Reset a user's password (admin only)
     *
     * @param int $userId Target user ID
     * @param string $newPassword New password to set
     * @return bool True on success, false on failure
     */
    public function reset($userId, $newPassword)
    {
        if (empty($newPassword)) {
            return false;
        }

        // Check if current user is admin
        if (!$this->container['userSession']->isAdmin()) {
            return false;
        }

        // Check if target user exists
        $user = $this->container['userModel']->getById($userId);
        if (empty($user)) {
            return false;
        }

        // Update password
        return $this->container['userModel']->update([
            'id' => $userId,
            'password' => $newPassword,
        ]);
    }
}
