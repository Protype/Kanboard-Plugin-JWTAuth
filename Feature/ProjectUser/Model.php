<?php

namespace Kanboard\Plugin\KanproBridge\Feature\ProjectUser;

/**
 * Project User Model
 *
 * Provides extended user information for project members and assignable users.
 * Unlike Kanboard's native getProjectUsers/getAssignableUsers which return
 * only {user_id: username} mapping, these methods return full user objects.
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
     * Get extended user data for project members
     *
     * Returns full user objects for all project members (all roles).
     *
     * @param int $projectId Project ID
     * @return array Array of user objects with full details
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
     *
     * @param int $projectId Project ID
     * @return array Array of user objects with full details
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

            $result[] = $filteredUser;
        }

        return $result;
    }
}
