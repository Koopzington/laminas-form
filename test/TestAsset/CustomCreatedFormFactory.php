<?php

namespace LaminasTest\Form\TestAsset;

use DateTime;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class CustomCreatedFormFactory implements FactoryInterface
{
    private $creationOptions = [];

    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $options = $options ?: [];
        $creationString = 'now';

        if (isset($options['created'])) {
            $creationString = $options['created'];
            unset($options['created']);
        }

        $created = new DateTime($creationString);

        $name = null;
        if (isset($options['name'])) {
            $name = $options['name'];
            unset($options['name']);
        }

        $form = new CustomCreatedForm($created, $name, $options);
        return $form;
    }

    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, CustomCreatedForm::class, $this->creationOptions);
    }

    public function setCreationOptions(array $options)
    {
        $this->creationOptions = $options;
    }
}