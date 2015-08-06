<?php

require __DIR__ . '/../vendor/autoload.php';

if (!class_exists('Tester\Assert')) {
	echo "Install Nette Tester using `composer update --dev`\n";
	exit(1);
}

Tester\Environment::setup();

$configurator = new Nette\Configurator;
$configurator->setDebugMode(FALSE);
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
			 ->addDirectory(__DIR__ . '/../src')
			 ->register();

$configurator->onCompile[] = function ($configurator, \Nette\DI\Compiler $compiler) {
	$ext = new Trejjam\Validation\DI\ValidationExtension();
	$compiler->addExtension('validation', $ext);
};

//$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
//$configurator->addConfig(__DIR__ . '/../app/config/config.local.neon');
return $configurator->createContainer();
