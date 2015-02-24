<?php

/**
 * This file is part of the DbChangelog package
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Lovec\DbChangelog\DI;

use Flame\Modules\Providers\IPresenterMappingProvider;
use Kdyby\Events\DI\EventsExtension;
use Nette\DI\CompilerExtension;
use Nette\DirectoryNotFoundException;
use Nette\Utils\AssertionException;
use Nette\Utils\Validators;


class ChangelogExtension extends CompilerExtension implements IPresenterMappingProvider
{

	/**
	 * @var array
	 */
	private $defaults = [
		'dir' => '%appDir%/../changelog',
		'table' => 'changelog'
	];


	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$this->validateConfigParameters($config);

		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('manager'))
			->setClass('Lovec\DbChangelog\ChangelogManager')
			->setArguments(array($config['dir']));

		$builder->addDefinition($this->prefix('model'))
			->setClass('Lovec\DbChangelog\Model\Changelog')
			->setArguments(array($config['table']));

		$builder->addDefinition($this->prefix('component.add'))
			->setImplement('Lovec\DbChangelog\Components\AddToChangelog\AddToChangelogControlFactory');

		$builder->addDefinition($this->prefix('event.onRequest'))
			->setClass('Lovec\DbChangelog\Events\OnRequest')
			->addTag(EventsExtension::TAG_SUBSCRIBER);

		$builder->addDefinition($this->prefix('router'))
			->setClass('Lovec\DbChangelog\Router\RouterFactory');
	}


	public function beforeCompile()
	{
		$eventExtension = $this->compiler->getExtensions('Kdyby\Events\DI\EventsExtension');
		if (empty($eventExtension)) {
			throw new \Exception("Register 'Kdyby\\Events\\DI\\EventsExtension' to your config.neon");
		}
	}


	/**
	 * {@inheritdoc}
	 */
	public function getPresenterMapping()
	{
		return ['DbChangelog' => 'Lovec\DbChangelog\App\Presenters\*Presenter'];
	}


	/**
	 * @param array $config
	 * @throws AssertionException
	 * @throws \Exception
	 */
	private function validateConfigParameters($config)
	{
		Validators::assertField($config, 'dir', 'string');
		Validators::assertField($config, 'table', 'string');

		if ( ! is_dir($config['dir'])) {
			throw new DirectoryNotFoundException('Dir "' . $config['dir'] . '" not found! Create it.');
		}
		if ( ! is_writeable($config['dir'])) {
			throw new \Exception('Dir "' . $config['dir'] . '" is not writeable.');
		}
	}

}