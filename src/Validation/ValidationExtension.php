<?php
/**
 * Created by PhpStorm.
 * User: Jan
 * Date: 26. 10. 2014
 * Time: 17:38
 */

namespace Trejjam\DI;

use Nette;

class ValidationExtension extends Nette\DI\CompilerExtension
{
	public $defaults = [
		'cache' => [
			"use"=>true,
			"name"=> "ares",
			"timeout"=> "60 minutes"
		]
	];


	public function loadConfiguration() {
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$validation = $builder->addDefinition('validation')
							  ->setClass('Trejjam\Validation');
		//$builder->addDefinition($this->prefix('validation'))

		if ($config["cache"]["use"]) {
			$builder->addDefinition($this->prefix("cache"))
					->setClass('Nette\Caching\Cache')
					->setArguments(['@cacheStorage', $config["cache"]["name"]])
					->setAutowired(FALSE);

			$validation->setArguments([$this->prefix("@cache")])
					   ->addSetup("setTimeout", ["timeout" => $config["cache"]["timeout"]]);
		}
		$validation->setInject(FALSE);
	}
}