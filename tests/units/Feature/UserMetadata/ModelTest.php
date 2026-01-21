<?php

namespace Kanboard\Plugin\KanproBridge\Tests\Units\Feature\UserMetadata;

use Kanboard\Plugin\KanproBridge\Tests\Units\Base;
use Kanboard\Plugin\KanproBridge\Feature\UserMetadata\Model;

/**
 * UserMetadata Model Unit Tests
 */
class ModelTest extends Base
{
    /**
     * Test getAll returns empty array for user with no metadata
     */
    public function testGetAllReturnsEmptyArrayForNoMetadata(): void
    {
        $model = new Model($this->container);

        $result = $model->getAll(1);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test save and getAll for own metadata
     */
    public function testSaveAndGetAllForOwnMetadata(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Save metadata
        $result = $model->save(1, ['theme' => 'dark', 'language' => 'zh-tw']);
        $this->assertTrue($result);

        // Get all metadata
        $metadata = $model->getAll(1);
        $this->assertIsArray($metadata);
        $this->assertEquals('dark', $metadata['theme']);
        $this->assertEquals('zh-tw', $metadata['language']);
    }

    /**
     * Test get returns specific metadata value
     */
    public function testGetReturnsSpecificValue(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Save metadata
        $model->save(1, ['theme' => 'dark']);

        // Get specific metadata
        $result = $model->get(1, 'theme');
        $this->assertEquals('dark', $result);
    }

    /**
     * Test get returns default value when not found
     */
    public function testGetReturnsDefaultWhenNotFound(): void
    {
        $model = new Model($this->container);

        $result = $model->get(1, 'nonexistent', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    /**
     * Test exists returns true for existing metadata
     */
    public function testExistsReturnsTrueForExisting(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Save metadata
        $model->save(1, ['theme' => 'dark']);

        // Check exists
        $result = $model->exists(1, 'theme');
        $this->assertTrue($result);
    }

    /**
     * Test exists returns false for non-existing metadata
     */
    public function testExistsReturnsFalseForNonExisting(): void
    {
        $model = new Model($this->container);

        $result = $model->exists(1, 'nonexistent');
        $this->assertFalse($result);
    }

    /**
     * Test remove deletes metadata
     */
    public function testRemoveDeletesMetadata(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Save metadata
        $model->save(1, ['theme' => 'dark', 'language' => 'zh-tw']);

        // Remove one entry
        $result = $model->remove(1, 'theme');
        $this->assertTrue($result);

        // Verify removed
        $this->assertFalse($model->exists(1, 'theme'));

        // Other entry still exists
        $this->assertEquals('zh-tw', $model->get(1, 'language'));
    }

    /**
     * Test save updates existing metadata
     */
    public function testSaveUpdatesExistingMetadata(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'admin',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Save initial metadata
        $model->save(1, ['theme' => 'dark']);
        $this->assertEquals('dark', $model->get(1, 'theme'));

        // Update metadata
        $model->save(1, ['theme' => 'light']);
        $this->assertEquals('light', $model->get(1, 'theme'));
    }

    // ========================================
    // Permission Tests
    // ========================================

    /**
     * Test user can access own metadata
     */
    public function testUserCanAccessOwnMetadata(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // User can save their own metadata
        $result = $model->save(1, ['theme' => 'dark']);
        $this->assertTrue($result);

        // User can read their own metadata
        $metadata = $model->getAll(1);
        $this->assertIsArray($metadata);
    }

    /**
     * Test non-admin cannot access other user's metadata
     */
    public function testNonAdminCannotAccessOtherUserMetadata(): void
    {
        $this->setUserSession([
            'id' => 2,
            'username' => 'bob',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Non-admin cannot read other user's metadata
        $result = $model->getAll(1);
        $this->assertFalse($result);

        // Non-admin cannot save to other user's metadata
        $result = $model->save(1, ['theme' => 'dark']);
        $this->assertFalse($result);

        // Non-admin cannot get specific metadata
        $result = $model->get(1, 'theme');
        $this->assertFalse($result);

        // Non-admin cannot check exists for other user
        $result = $model->exists(1, 'theme');
        $this->assertNull($result);

        // Non-admin cannot remove other user's metadata
        $result = $model->remove(1, 'theme');
        $this->assertFalse($result);
    }

    /**
     * Test admin can access any user's metadata
     */
    public function testAdminCanAccessAnyUserMetadata(): void
    {
        // First, create metadata as user 1
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);
        $model->save(1, ['theme' => 'dark']);

        // Now switch to admin (user 99)
        $this->setUserSession([
            'id' => 99,
            'username' => 'admin',
            'role' => 'app-admin',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Admin can read other user's metadata
        $metadata = $model->getAll(1);
        $this->assertIsArray($metadata);
        $this->assertEquals('dark', $metadata['theme']);

        // Admin can save to other user's metadata
        $result = $model->save(1, ['language' => 'en']);
        $this->assertTrue($result);

        // Admin can get specific metadata
        $result = $model->get(1, 'language');
        $this->assertEquals('en', $result);

        // Admin can check exists
        $result = $model->exists(1, 'theme');
        $this->assertTrue($result);

        // Admin can remove metadata
        $result = $model->remove(1, 'theme');
        $this->assertTrue($result);
    }

    /**
     * Test changed_by and changed_on are recorded
     */
    public function testChangedByAndChangedOnAreRecorded(): void
    {
        $this->setUserSession([
            'id' => 1,
            'username' => 'alice',
            'role' => 'app-user',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);
        $timeBefore = time();

        // Save metadata
        $model->save(1, ['theme' => 'dark']);

        $timeAfter = time();

        // Retrieve raw data from storage
        $rawData = $this->userMetadataStorage[Model::TABLE][0] ?? null;

        $this->assertNotNull($rawData);
        $this->assertEquals(1, $rawData['changed_by']);
        $this->assertGreaterThanOrEqual($timeBefore, $rawData['changed_on']);
        $this->assertLessThanOrEqual($timeAfter, $rawData['changed_on']);
    }

    /**
     * Test admin save records admin as changed_by
     */
    public function testAdminSaveRecordsAdminAsChangedBy(): void
    {
        $this->setUserSession([
            'id' => 99,
            'username' => 'admin',
            'role' => 'app-admin',
        ]);
        $this->setupUserSession();

        $model = new Model($this->container);

        // Admin saves metadata for user 1
        $model->save(1, ['theme' => 'dark']);

        // Retrieve raw data from storage
        $rawData = $this->userMetadataStorage[Model::TABLE][0] ?? null;

        $this->assertNotNull($rawData);
        $this->assertEquals(99, $rawData['changed_by']);
        $this->assertEquals(1, $rawData['user_id']);
    }
}
