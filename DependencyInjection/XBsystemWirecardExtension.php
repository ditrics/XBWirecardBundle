<?php

namespace XBsystem\WirecardBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class XBsystemWirecardExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

//		$processor = new Processor();
//		$configuration = new Configuration();
//		$config = $processor->processConfiguration($configuration, $configs);

		$container->setParameter('wirecard_user', $config['user']);
		$container->setParameter('wirecard_passkey', $config['passkey']);
		$container->setParameter('wirecard_checkouturl', $config['checkoutUrl']);
		$container->setParameter('wirecard_test', $config['test']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

	public function getAlias(){

		return'x_bsystem_wirecard';
	}
}
