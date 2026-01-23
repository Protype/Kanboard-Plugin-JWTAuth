<?php

namespace Kanboard\Plugin\KanproBridge\Tests\Units\Feature\ProjectUser;

use Kanboard\Plugin\KanproBridge\Tests\Units\Base;
use Kanboard\Plugin\KanproBridge\Feature\ProjectUser\Model;

/**
 * ProjectUser Model Unit Tests
 */
class ModelTest extends Base
{
    // ========================================
    // getProjectUsers() Tests
    // ========================================

    /**
     * Test get project users returns all members with full data
     */
    public function testGetProjectUsersReturnsAllMembers(): void
    {
        // Add users
        $this->addUserWithProfile(1, 'alice', 'password', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'role' => 'app-user',
            'is_active' => 1,
        ]);
        $this->addUserWithProfile(2, 'bob', 'password', [
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'role' => 'app-user',
            'is_active' => 1,
        ]);
        $this->addUserWithProfile(3, 'charlie', 'password', [
            'name' => 'Charlie',
            'email' => 'charlie@example.com',
            'role' => 'app-admin',
            'is_active' => 1,
        ]);

        // Add project members
        $this->addProjectMember(1, 1, 'project-manager');
        $this->addProjectMember(1, 2, 'project-member');
        $this->addProjectMember(1, 3, 'project-viewer');

        $model = new Model($this->container);

        $result = $model->getProjectUsers(1);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // Find Alice
        $alice = $this->findUserInResult($result, 1);
        $this->assertNotNull($alice);
        $this->assertEquals('alice', $alice['username']);
        $this->assertEquals('Alice', $alice['name']);
        $this->assertEquals('alice@example.com', $alice['email']);
        $this->assertEquals('app-user', $alice['role']);
        $this->assertEquals(1, $alice['is_active']);
        $this->assertEquals('project-manager', $alice['project_role']);

        // Find Bob
        $bob = $this->findUserInResult($result, 2);
        $this->assertNotNull($bob);
        $this->assertEquals('bob', $bob['username']);
        $this->assertEquals('project-member', $bob['project_role']);

        // Find Charlie (viewer)
        $charlie = $this->findUserInResult($result, 3);
        $this->assertNotNull($charlie);
        $this->assertEquals('charlie', $charlie['username']);
        $this->assertEquals('project-viewer', $charlie['project_role']);
    }

    /**
     * Test get project users returns empty array for project with no members
     */
    public function testGetProjectUsersReturnsEmptyForNoMembers(): void
    {
        $model = new Model($this->container);

        $result = $model->getProjectUsers(999);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test get project users excludes sensitive fields
     */
    public function testGetProjectUsersExcludesSensitiveFields(): void
    {
        $this->addUserWithProfile(1, 'alice', 'secretpassword', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);
        $this->addProjectMember(1, 1, 'project-member');

        $model = new Model($this->container);

        $result = $model->getProjectUsers(1);

        $this->assertCount(1, $result);
        $user = $result[0];

        // Should not contain password
        $this->assertArrayNotHasKey('password', $user);

        // Should contain expected fields
        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('username', $user);
        $this->assertArrayHasKey('name', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertArrayHasKey('role', $user);
        $this->assertArrayHasKey('is_active', $user);
        $this->assertArrayHasKey('project_role', $user);
    }

    // ========================================
    // getAssignableUsers() Tests
    // ========================================

    /**
     * Test get assignable users excludes viewers
     */
    public function testGetAssignableUsersExcludesViewers(): void
    {
        // Add users
        $this->addUserWithProfile(1, 'alice', 'password', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);
        $this->addUserWithProfile(2, 'bob', 'password', [
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ]);
        $this->addUserWithProfile(3, 'charlie', 'password', [
            'name' => 'Charlie',
            'email' => 'charlie@example.com',
        ]);

        // Add project members with different roles
        $this->addProjectMember(1, 1, 'project-manager');
        $this->addProjectMember(1, 2, 'project-member');
        $this->addProjectMember(1, 3, 'project-viewer');  // Should be excluded

        $model = new Model($this->container);

        $result = $model->getAssignableUsers(1);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // Should include alice and bob
        $userIds = array_column($result, 'id');
        $this->assertContains(1, $userIds);
        $this->assertContains(2, $userIds);
        $this->assertNotContains(3, $userIds);  // Viewer excluded
    }

    /**
     * Test get assignable users returns empty for project with only viewers
     */
    public function testGetAssignableUsersReturnsEmptyForOnlyViewers(): void
    {
        $this->addUserWithProfile(1, 'viewer1', 'password');
        $this->addUserWithProfile(2, 'viewer2', 'password');

        $this->addProjectMember(1, 1, 'project-viewer');
        $this->addProjectMember(1, 2, 'project-viewer');

        $model = new Model($this->container);

        $result = $model->getAssignableUsers(1);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test get assignable users returns full user data with project role
     */
    public function testGetAssignableUsersReturnsFullDataWithProjectRole(): void
    {
        $this->addUserWithProfile(1, 'alice', 'password', [
            'name' => 'Alice Smith',
            'email' => 'alice@example.com',
            'role' => 'app-user',
            'is_active' => 1,
        ]);
        $this->addProjectMember(1, 1, 'project-manager');

        $model = new Model($this->container);

        $result = $model->getAssignableUsers(1);

        $this->assertCount(1, $result);
        $user = $result[0];

        $this->assertEquals(1, $user['id']);
        $this->assertEquals('alice', $user['username']);
        $this->assertEquals('Alice Smith', $user['name']);
        $this->assertEquals('alice@example.com', $user['email']);
        $this->assertEquals('app-user', $user['role']);
        $this->assertEquals(1, $user['is_active']);
        $this->assertEquals('project-manager', $user['project_role']);
    }

    /**
     * Test get assignable users returns empty for non-existent project
     */
    public function testGetAssignableUsersReturnsEmptyForNonExistentProject(): void
    {
        $model = new Model($this->container);

        $result = $model->getAssignableUsers(999);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ========================================
    // Helper Methods
    // ========================================

    /**
     * Find a user in result array by ID
     */
    private function findUserInResult(array $result, int $userId): ?array
    {
        foreach ($result as $user) {
            if ($user['id'] === $userId) {
                return $user;
            }
        }
        return null;
    }
}
