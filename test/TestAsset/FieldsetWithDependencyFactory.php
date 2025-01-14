<?php

declare(strict_types=1);

namespace LaminasTest\Form\TestAsset;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class FieldsetWithDependencyFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $name, ?array $options = null)
    {
        $options = $options ?: [];

        $name = null;
        if (isset($options['name'])) {
            $name = $options['name'];
            unset($options['name']);
        }

        $form = new FieldsetWithDependency($name, $options);
        $form->setDependency(new InputFilter());

        return $form;
    }
}
