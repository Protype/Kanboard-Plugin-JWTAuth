<?php

namespace Kanboard\Controller;

if (!class_exists('Kanboard\Controller\BaseController')) {
    abstract class BaseController
    {
        protected $container;

        public function __construct($container)
        {
            $this->container = $container;
        }

        public function __get($name)
        {
            return $this->container[$name] ?? null;
        }
    }
}
