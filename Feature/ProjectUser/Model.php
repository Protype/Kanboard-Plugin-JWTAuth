<?php

namespace Kanboard\Plugin\KanproBridge\Feature\ProjectUser;

/**
 * Project User Model
 *
 * Overrides Kanboard's native user listing APIs to include avatar data.
 * - getAllUsers: Returns full user objects with avatar
 * - getProjectUsers/getAssignableUsers: Returns full user objects with avatar and project_role
 */
class Model
{
    /**
     * User fields to return (excludes sensitive data)
     */
    const USER_FIELDS = [
        'id',
        'username',
        'name',
        'email',
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
     * Get all users with avatar data
     *
     * Overrides Kanboard's getAllUsers to include avatar.
     *
     * @return array Array of user objects with full details and avatar
     */
    public function getAllUsers()
    {
        $users = $this->container['db']
            ->table('users')
            ->findAll();

        $result = [];
        foreach ($users as $user) {
            $filteredUser = [];
            foreach (self::USER_FIELDS as $field) {
                if (array_key_exists($field, $user)) {
                    $filteredUser[$field] = $user[$field];
                }
            }

            // Always include avatar
            $filteredUser['avatar'] = $this->getAvatarData($user['id']);

            $result[] = $filteredUser;
        }

        return $result;
    }

    /**
     * Get extended user data for project members
     *
     * Returns full user objects for all project members (all roles).
     * Includes avatar data (base64) for each user.
     *
     * @param int $projectId Project ID
     * @return array Array of user objects with full details and avatar
     */
    public function getProjectUsers($projectId)
    {
        // Get project member user IDs from project_has_users table
        $members = $this->container['db']
            ->table('project_has_users')
            ->columns('user_id', 'role')
            ->eq('project_id', $projectId)
            ->findAll();

        if (empty($members)) {
            return [];
        }

        $userIds = array_column($members, 'user_id');
        $projectRoles = array_column($members, 'role', 'user_id');

        return $this->getUsersByIds($userIds, $projectRoles);
    }

    /**
     * Get extended user data for assignable users
     *
     * Returns full user objects for users who can be assigned to tasks.
     * Excludes users with viewer-only roles.
     * Includes avatar data (base64) for each user.
     *
     * @param int $projectId Project ID
     * @return array Array of user objects with full details and avatar
     */
    public function getAssignableUsers($projectId)
    {
        // Get assignable user IDs (excludes project-viewer role)
        $members = $this->container['db']
            ->table('project_has_users')
            ->columns('user_id', 'role')
            ->eq('project_id', $projectId)
            ->neq('role', 'project-viewer')
            ->findAll();

        if (empty($members)) {
            return [];
        }

        $userIds = array_column($members, 'user_id');
        $projectRoles = array_column($members, 'role', 'user_id');

        return $this->getUsersByIds($userIds, $projectRoles);
    }

    /**
     * Get users by IDs with filtered fields
     *
     * @param array $userIds Array of user IDs
     * @param array $projectRoles Optional array of project roles keyed by user_id
     * @return array Array of user objects
     */
    private function getUsersByIds(array $userIds, array $projectRoles = [])
    {
        if (empty($userIds)) {
            return [];
        }

        $users = $this->container['db']
            ->table('users')
            ->in('id', $userIds)
            ->findAll();

        $result = [];
        foreach ($users as $user) {
            $filteredUser = [];
            foreach (self::USER_FIELDS as $field) {
                if (array_key_exists($field, $user)) {
                    $filteredUser[$field] = $user[$field];
                }
            }

            // Add project_role if available
            if (!empty($projectRoles) && isset($projectRoles[$user['id']])) {
                $filteredUser['project_role'] = $projectRoles[$user['id']];
            }

            // Always include avatar
            $filteredUser['avatar'] = $this->getAvatarData($user['id']);

            $result[] = $filteredUser;
        }

        return $result;
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
}
