<?php

namespace Kanboard\Core\Security;

if (!interface_exists('Kanboard\Core\Security\PasswordAuthenticationProviderInterface')) {
    interface PasswordAuthenticationProviderInterface
    {
        public function getName();
        public function authenticate();
        public function getUser();
        public function setUsername($username);
        public function setPassword($password);
    }
}
