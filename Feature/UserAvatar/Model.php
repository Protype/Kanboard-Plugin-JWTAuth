<?php

namespace Kanboard\Plugin\KanproBridge\Feature\UserAvatar;

/**
 * User Avatar Model
 *
 * Manages user avatar upload and retrieval via API.
 * Access is restricted to the user themselves or administrators.
 */
class Model
{
    /**
     * @var mixed Container
     */
    private $container;

    /**
     * Allowed image MIME types
     */
    private $allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
    ];

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
     * Check if current user can access target user's avatar
     *
     * @param int $targetUserId Target user ID
     * @return bool True if access is allowed
     */
    private function canAccess($targetUserId)
    {
        $currentUserId = $this->container['userSession']->getId();
        $isAdmin = $this->container['userSession']->isAdmin();

        if ($currentUserId === (int) $targetUserId) {
            return true;
        }

        if ($isAdmin) {
            return true;
        }

        return false;
    }

    /**
     * Validate image data
     *
     * @param string $imageData Raw binary image data
     * @return bool True if valid image
     */
    private function isValidImage($imageData)
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);

        return in_array($mimeType, $this->allowedTypes);
    }

    /**
     * Upload avatar for a user
     *
     * @param int $userId User ID
     * @param string $imageData Base64 encoded image data
     * @return bool True on success, false on failure or access denied
     */
    public function upload($userId, $imageData)
    {
        if (!$this->canAccess($userId)) {
            return false;
        }

        // Decode base64 data
        $binaryData = base64_decode($imageData, true);
        if ($binaryData === false) {
            return false;
        }

        // Validate image
        if (!$this->isValidImage($binaryData)) {
            return false;
        }

        // Remove existing avatar first
        $this->container['avatarFileModel']->remove($userId);

        // Upload new avatar
        return $this->container['avatarFileModel']->uploadImageContent($userId, $binaryData);
    }

    /**
     * Get avatar for a user
     *
     * @param int $userId User ID
     * @return string|null Base64 encoded image data or null if not found/access denied
     */
    public function get($userId)
    {
        if (!$this->canAccess($userId)) {
            return null;
        }

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
     * Remove avatar for a user
     *
     * @param int $userId User ID
     * @return bool True on success, false on failure or access denied
     */
    public function remove($userId)
    {
        if (!$this->canAccess($userId)) {
            return false;
        }

        return $this->container['avatarFileModel']->remove($userId);
    }
}
