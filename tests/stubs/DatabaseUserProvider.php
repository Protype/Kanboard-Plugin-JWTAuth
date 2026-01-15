<?php

namespace Kanboard\User;

if (!class_exists('Kanboard\User\DatabaseUserProvider')) {
    class DatabaseUserProvider
    {
        private $userInfo;

        public function __construct(array $userInfo)
        {
            $this->userInfo = $userInfo;
        }

        public function getId()
        {
            return $this->userInfo['id'] ?? null;
        }

        public function getUsername()
        {
            return $this->userInfo['username'] ?? null;
        }

        public function getName()
        {
            return $this->userInfo['name'] ?? null;
        }

        public function getEmail()
        {
            return $this->userInfo['email'] ?? null;
        }

        public function getRole()
        {
            return $this->userInfo['role'] ?? null;
        }

        public function getInternalId()
        {
            return $this->userInfo['id'] ?? null;
        }

        public function getExternalId()
        {
            return $this->userInfo['username'] ?? null;
        }

        public function getExternalIdColumn()
        {
            return 'username';
        }

        public function isUserCreationAllowed()
        {
            return false;
        }
    }
}
