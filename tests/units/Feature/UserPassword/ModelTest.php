<?php

namespace Kanboard\Plugin\KanproBridge\Tests\Units\Feature\UserPassword;

use Kanboard\Plugin\KanproBridge\Tests\Units\Base;
use Kanboard\Plugin\KanproBridge\Feature\UserPassword\Model;

/**
 * UserPassword Model Unit Tests
 */
class ModelTest extends Base
{
    // ========================================
    // change() Tests
    // ========================================

    /**
     * Test change with valid current password
     */
    public function testChangeWithValidCurrentPassword(): void
    {
        $this->addUser(1, 'alice', 'oldpassword');
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->change('oldpassword', 'newpassword');

        $this->assertTrue($result);

        // Verify new password works
        $user = $this->userStorage[1];
        $this->assertTrue(password_verify('newpassword', $user['password']));
    }

    /**
     * Test change with invalid current password returns false
     */
    public function testChangeWithInvalidCurrentPasswordReturnsFalse(): void
    {
        $this->addUser(1, 'alice', 'oldpassword');
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->change('wrongpassword', 'newpassword');

        $this->assertFalse($result);

        // Verify old password still works
        $user = $this->userStorage[1];
        $this->assertTrue(password_verify('oldpassword', $user['password']));
    }

    /**
     * Test change with empty current password returns false
     */
    public function testChangeWithEmptyCurrentPasswordReturnsFalse(): void
    {
        $this->addUser(1, 'alice', 'oldpassword');
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->change('', 'newpassword');

        $this->assertFalse($result);
    }

    /**
     * Test change with empty new password returns false
     */
    public function testChangeWithEmptyNewPasswordReturnsFalse(): void
    {
        $this->addUser(1, 'alice', 'oldpassword');
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->change('oldpassword', '');

        $this->assertFalse($result);
    }

    // ========================================
    // reset() Tests
    // ========================================

    /**
     * Test admin can reset any user's password
     */
    public function testAdminCanResetAnyUserPassword(): void
    {
        $this->addUser(1, 'alice', 'oldpassword');
        $this->setUserSession([
            'id' => 99,
            'username' => 'admin',
            'role' => 'app-admin',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->reset(1, 'adminsetpassword');

        $this->assertTrue($result);

        // Verify new password works
        $user = $this->userStorage[1];
        $this->assertTrue(password_verify('adminsetpassword', $user['password']));
    }

    /**
     * Test non-admin cannot reset other user's password
     */
    public function testNonAdminCannotResetOtherUserPassword(): void
    {
        $this->addUser(1, 'alice', 'oldpassword');
        $this->addUser(2, 'bob', 'bobpassword');
        $this->setUserSession([
            'id' => 2,
            'username' => 'bob',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->reset(1, 'hackedpassword');

        $this->assertFalse($result);

        // Verify old password still works
        $user = $this->userStorage[1];
        $this->assertTrue(password_verify('oldpassword', $user['password']));
    }

    /**
     * Test reset with empty password returns false
     */
    public function testResetWithEmptyPasswordReturnsFalse(): void
    {
        $this->addUser(1, 'alice', 'oldpassword');
        $this->setUserSession([
            'id' => 99,
            'username' => 'admin',
            'role' => 'app-admin',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->reset(1, '');

        $this->assertFalse($result);
    }

    /**
     * Test reset for non-existent user returns false
     */
    public function testResetForNonExistentUserReturnsFalse(): void
    {
        $this->setUserSession([
            'id' => 99,
            'username' => 'admin',
            'role' => 'app-admin',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->reset(999, 'newpassword');

        $this->assertFalse($result);
    }
}
