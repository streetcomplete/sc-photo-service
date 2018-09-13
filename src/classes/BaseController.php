<?php
namespace StreetComplete;

use Interop\Container\ContainerInterface;

class BaseController
{
    protected $container;

    // https://www.slimframework.com/docs/v3/objects/router.html#using-an-invokable-class
    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    // database helper function
    public function db()
    {
        return $this->container->get('db');
    }

    // settings helper function
    public function settings($setting)
    {
        return $this->container->get('settings')[$setting];
    }
}
