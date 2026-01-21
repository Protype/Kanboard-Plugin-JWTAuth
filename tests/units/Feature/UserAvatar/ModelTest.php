<?php

namespace Kanboard\Plugin\KanproBridge\Tests\Units\Feature\UserAvatar;

use Kanboard\Plugin\KanproBridge\Tests\Units\Base;
use Kanboard\Plugin\KanproBridge\Feature\UserAvatar\Model;

/**
 * UserAvatar Model Unit Tests
 */
class ModelTest extends Base
{
    /**
     * Valid PNG image (1x1 pixel, base64 encoded)
     */
    private $validPngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    /**
     * Valid JPEG image (1x1 pixel, base64 encoded)
     */
    private $validJpegBase64 = '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBEQCEAwEPwAB//9k=';

    /**
     * Test get returns null when no avatar exists
     */
    public function testGetReturnsNullWhenNoAvatar(): void
    {
        $model = new Model($this->container);

        $result = $model->get(1);

        $this->assertNull($result);
    }

    /**
     * Test upload with valid PNG image
     */
    public function testUploadValidPngImage(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->upload(1, $this->validPngBase64);

        $this->assertTrue($result);
    }

    /**
     * Test upload with valid JPEG image
     */
    public function testUploadValidJpegImage(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->upload(1, $this->validJpegBase64);

        $this->assertTrue($result);
    }

    /**
     * Test upload and get returns base64 image
     */
    public function testUploadAndGetReturnsBase64(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Upload
        $model->upload(1, $this->validPngBase64);

        // Get
        $result = $model->get(1);

        $this->assertNotNull($result);
        $this->assertEquals($this->validPngBase64, $result);
    }

    /**
     * Test upload with invalid base64 returns false
     */
    public function testUploadInvalidBase64ReturnsFalse(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->upload(1, 'not-valid-base64!!!');

        $this->assertFalse($result);
    }

    /**
     * Test upload with non-image data returns false
     */
    public function testUploadNonImageReturnsFalse(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Valid base64 but not an image
        $textBase64 = base64_encode('Hello World');

        $result = $model->upload(1, $textBase64);

        $this->assertFalse($result);
    }

    /**
     * Test remove deletes avatar
     */
    public function testRemoveDeletesAvatar(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Upload first
        $model->upload(1, $this->validPngBase64);
        $this->assertNotNull($model->get(1));

        // Remove
        $result = $model->remove(1);
        $this->assertTrue($result);

        // Verify removed
        $this->assertNull($model->get(1));
    }

    // ========================================
    // Permission Tests
    // ========================================

    /**
     * Test user can upload own avatar
     */
    public function testUserCanUploadOwnAvatar(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        $result = $model->upload(1, $this->validPngBase64);
        $this->assertTrue($result);
    }

    /**
     * Test user can get own avatar
     */
    public function testUserCanGetOwnAvatar(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);
        $model->upload(1, $this->validPngBase64);

        $result = $model->get(1);
        $this->assertNotNull($result);
    }

    /**
     * Test user can remove own avatar
     */
    public function testUserCanRemoveOwnAvatar(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);
        $model->upload(1, $this->validPngBase64);

        $result = $model->remove(1);
        $this->assertTrue($result);
    }

    /**
     * Test non-admin cannot access other user's avatar
     */
    public function testNonAdminCannotAccessOtherUserAvatar(): void
    {
        // First, upload avatar as user 1
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);
        $model->upload(1, $this->validPngBase64);

        // Switch to user 2 (non-admin)
        $this->setUserSession([
            'id' => 2,
            'username' => 'bob',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Non-admin cannot get other user's avatar
        $result = $model->get(1);
        $this->assertNull($result);

        // Non-admin cannot upload to other user's avatar
        $result = $model->upload(1, $this->validPngBase64);
        $this->assertFalse($result);

        // Non-admin cannot remove other user's avatar
        $result = $model->remove(1);
        $this->assertFalse($result);
    }

    /**
     * Test admin can access any user's avatar
     */
    public function testAdminCanAccessAnyUserAvatar(): void
    {
        // First, upload avatar as user 1
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);
        $model->upload(1, $this->validPngBase64);

        // Switch to admin (user 99)
        $this->setUserSession([
            'id' => 99,
            'username' => 'admin',
            'role' => 'app-admin',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Admin can get other user's avatar
        $result = $model->get(1);
        $this->assertNotNull($result);

        // Admin can upload to other user's avatar
        $result = $model->upload(1, $this->validJpegBase64);
        $this->assertTrue($result);

        // Admin can remove other user's avatar
        $result = $model->remove(1);
        $this->assertTrue($result);
    }
}
