<?php

namespace Kanboard\Plugin\KanproBridge\Feature\UserMetadata;

/**
 * User Metadata Model
 *
 * Manages user metadata storage and retrieval.
 * Access is restricted to the user themselves or administrators.
 */
class Model
{
    const TABLE = 'kanpro_user_metadata';

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
     * Check if current user can access target user's metadata
     *
     * @param int $targetUserId Target user ID
     * @return bool True if access is allowed
     */
    private function canAccess($targetUserId)
    {
        $currentUserId = $this->container['userSession']->getId();
        $isAdmin = $this->container['userSession']->isAdmin();

        // User can access their own metadata
        if ($currentUserId === (int) $targetUserId) {
            return true;
        }

        // Admin can access anyone's metadata
        if ($isAdmin) {
            return true;
        }

        return false;
    }

    /**
     * Get all metadata for a user
     *
     * @param int $userId User ID
     * @return array|false Array of metadata or false if access denied
     */
    public function getAll($userId)
    {
        if (!$this->canAccess($userId)) {
            return false;
        }

        $rows = $this->container['db']->table(self::TABLE)
            ->eq('user_id', $userId)
            ->findAll();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['name']] = $row['value'];
        }

        return $result;
    }

    /**
     * Get a specific metadata value by name
     *
     * @param int $userId User ID
     * @param string $name Metadata name
     * @param string $default Default value if not found
     * @return string|false Metadata value or false if access denied
     */
    public function get($userId, $name, $default = '')
    {
        if (!$this->canAccess($userId)) {
            return false;
        }

        $row = $this->container['db']->table(self::TABLE)
            ->eq('user_id', $userId)
            ->eq('name', $name)
            ->findOne();

        if ($row) {
            return $row['value'];
        }

        return $default;
    }

    /**
     * Check if a metadata entry exists
     *
     * @param int $userId User ID
     * @param string $name Metadata name
     * @return bool|null True if exists, false if not, null if access denied
     */
    public function exists($userId, $name)
    {
        if (!$this->canAccess($userId)) {
            return null;
        }

        return $this->container['db']->table(self::TABLE)
            ->eq('user_id', $userId)
            ->eq('name', $name)
            ->exists();
    }

    /**
     * Save metadata for a user
     *
     * @param int $userId User ID
     * @param array $values Key-value pairs to save
     * @return bool True on success, false on failure or access denied
     */
    public function save($userId, array $values)
    {
        if (!$this->canAccess($userId)) {
            return false;
        }

        $currentUserId = $this->container['userSession']->getId();
        $now = time();

        foreach ($values as $name => $value) {
            $existing = $this->container['db']->table(self::TABLE)
                ->eq('user_id', $userId)
                ->eq('name', $name)
                ->findOne();

            if ($existing) {
                // Update existing entry
                $result = $this->container['db']->table(self::TABLE)
                    ->eq('id', $existing['id'])
                    ->update([
                        'value' => $value,
                        'changed_by' => $currentUserId,
                        'changed_on' => $now,
                    ]);
            } else {
                // Insert new entry
                $result = $this->container['db']->table(self::TABLE)->insert([
                    'user_id' => $userId,
                    'name' => $name,
                    'value' => $value,
                    'changed_by' => $currentUserId,
                    'changed_on' => $now,
                ]);
            }

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove a specific metadata entry
     *
     * @param int $userId User ID
     * @param string $name Metadata name
     * @return bool True on success, false on failure or access denied
     */
    public function remove($userId, $name)
    {
        if (!$this->canAccess($userId)) {
            return false;
        }

        return $this->container['db']->table(self::TABLE)
            ->eq('user_id', $userId)
            ->eq('name', $name)
            ->remove();
    }
}
