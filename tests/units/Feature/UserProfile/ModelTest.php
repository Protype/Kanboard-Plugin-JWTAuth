<?php

namespace Kanboard\Plugin\KanproBridge\Tests\Units\Feature\UserProfile;

use Kanboard\Plugin\KanproBridge\Tests\Units\Base;
use Kanboard\Plugin\KanproBridge\Feature\UserProfile\Model;

/**
 * UserProfile Model Unit Tests
 */
class ModelTest extends Base
{
    // ========================================
    // get() Tests
    // ========================================

    /**
     * Test user can get their own profile
     */
    public function testUserCanGetOwnProfile(): void
    {
        $this->addUserWithProfile(1, 'alice', 'password', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'theme' => 'dark',
            'timezone' => 'Asia/Taipei',
            'language' => 'zh_TW',
            'filter' => 'status:open',
            'role' => 'app-user',
            'is_active' => 1,
        ]);
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->get(1);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('alice', $result['username']);
        $this->assertEquals('Alice', $result['name']);
        $this->assertEquals('alice@example.com', $result['email']);
        $this->assertEquals('dark', $result['theme']);
        $this->assertEquals('Asia/Taipei', $result['timezone']);
        $this->assertEquals('zh_TW', $result['language']);
        $this->assertEquals('status:open', $result['filter']);
        $this->assertEquals('app-user', $result['role']);
        $this->assertEquals(1, $result['is_active']);
    }

    /**
     * Test admin can get any user's profile
     */
    public function testAdminCanGetAnyUserProfile(): void
    {
        $this->addUserWithProfile(1, 'alice', 'password', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);
        $this->setUserSession([
            'id' => 99,
            'username' => 'admin',
            'role' => 'app-admin',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->get(1);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('alice', $result['username']);
        $this->assertEquals('Alice', $result['name']);
    }

    /**
     * Test non-admin cannot get other user's profile
     */
    public function testNonAdminCannotGetOtherUserProfile(): void
    {
        $this->addUserWithProfile(1, 'alice', 'password');
        $this->addUserWithProfile(2, 'bob', 'password');
        $this->setUserSession([
            'id' => 2,
            'username' => 'bob',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->get(1);

        $this->assertFalse($result);
    }

    /**
     * Test get returns false for non-existent user
     */
    public function testGetReturnsNullForNonExistentUser(): void
    {
        $this->setUserSession([
            'id' => 99,
            'username' => 'admin',
            'role' => 'app-admin',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->get(999);

        $this->assertFalse($result);
    }

    // ========================================
    // update() Tests
    // ========================================

    /**
     * Test user can update their own profile
     */
    public function testUserCanUpdateOwnProfile(): void
    {
        $this->addUserWithProfile(1, 'alice', 'password', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'theme' => 'light',
        ]);
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->update(1, [
            'name' => 'Alice Updated',
            'email' => 'alice.updated@example.com',
            'theme' => 'dark',
            'timezone' => 'Asia/Tokyo',
            'language' => 'ja_JP',
            'filter' => 'status:closed',
        ]);

        $this->assertTrue($result);

        // Verify updates
        $user = $this->userStorage[1];
        $this->assertEquals('Alice Updated', $user['name']);
        $this->assertEquals('alice.updated@example.com', $user['email']);
        $this->assertEquals('dark', $user['theme']);
        $this->assertEquals('Asia/Tokyo', $user['timezone']);
        $this->assertEquals('ja_JP', $user['language']);
        $this->assertEquals('status:closed', $user['filter']);
    }

    /**
     * Test admin can update any user's profile
     */
    public function testAdminCanUpdateAnyUserProfile(): void
    {
        $this->addUserWithProfile(1, 'alice', 'password', [
            'name' => 'Alice',
        ]);
        $this->setUserSession([
            'id' => 99,
            'username' => 'admin',
            'role' => 'app-admin',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->update(1, [
            'name' => 'Alice Admin Updated',
        ]);

        $this->assertTrue($result);

        $user = $this->userStorage[1];
        $this->assertEquals('Alice Admin Updated', $user['name']);
    }

    /**
     * Test non-admin cannot update other user's profile
     */
    public function testNonAdminCannotUpdateOtherUserProfile(): void
    {
        $this->addUserWithProfile(1, 'alice', 'password', [
            'name' => 'Alice',
        ]);
        $this->addUserWithProfile(2, 'bob', 'password');
        $this->setUserSession([
            'id' => 2,
            'username' => 'bob',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->update(1, [
            'name' => 'Hacked Name',
        ]);

        $this->assertFalse($result);

        // Verify name was not changed
        $user = $this->userStorage[1];
        $this->assertEquals('Alice', $user['name']);
    }

    /**
     * Test update filters out non-allowed fields
     */
    public function testUpdateFiltersNonAllowedFields(): void
    {
        $this->addUserWithProfile(1, 'alice', 'password', [
            'role' => 'app-user',
            'is_active' => 1,
        ]);
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Try to update non-allowed fields (role, is_active, password)
        $result = $model->update(1, [
            'name' => 'Alice Updated',
            'role' => 'app-admin',          // Not allowed
            'is_active' => 0,               // Not allowed
            'password' => 'newpassword',    // Not allowed
        ]);

        $this->assertTrue($result);

        $user = $this->userStorage[1];
        $this->assertEquals('Alice Updated', $user['name']);
        $this->assertEquals('app-user', $user['role']);       // Unchanged
        $this->assertEquals(1, $user['is_active']);           // Unchanged
        // Password should still be the original hashed password
        $this->assertTrue(password_verify('password', $user['password']));
    }

    /**
     * Test update with only non-allowed fields returns false
     */
    public function testUpdateWithOnlyNonAllowedFieldsReturnsFalse(): void
    {
        $this->addUserWithProfile(1, 'alice', 'password');
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->update(1, [
            'role' => 'app-admin',
            'is_active' => 0,
        ]);

        $this->assertFalse($result);
    }

    /**
     * Test update with empty values array returns false
     */
    public function testUpdateWithEmptyValuesReturnsFalse(): void
    {
        $this->addUserWithProfile(1, 'alice', 'password');
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->update(1, []);

        $this->assertFalse($result);
    }

    /**
     * Test update for non-existent user returns false
     */
    public function testUpdateForNonExistentUserReturnsFalse(): void
    {
        $this->setUserSession([
            'id' => 99,
            'username' => 'admin',
            'role' => 'app-admin',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->update(999, [
            'name' => 'Test',
        ]);

        $this->assertFalse($result);
    }

    /**
     * Test user can update username
     */
    public function testUserCanUpdateUsername(): void
    {
        $this->addUserWithProfile(1, 'alice', 'password');
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->update(1, [
            'username' => 'alice_new',
        ]);

        $this->assertTrue($result);

        $user = $this->userStorage[1];
        $this->assertEquals('alice_new', $user['username']);
    }
}
