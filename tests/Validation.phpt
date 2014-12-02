<?php

namespace Test;

use Nette,
	Tester,
	Tester\Assert;

$container = require __DIR__ . '/bootstrap.php';


class ValidationTest extends Tester\TestCase
{
	private $container;
	/**
	 * @var \Trejjam\Validation
	 */
	private $validation;

	function __construct(Nette\DI\Container $container) {
		$this->container = $container;
	}

	function setUp() {
		$this->validation = $this->container->getService("validation.validation");
	}

	function testRc() {
		Assert::true($this->validation->rc("780123/3540"));
		Assert::true($this->validation->rc("7801233540"));
		Assert::true($this->validation->rc("841027/4114"));
		Assert::true($this->validation->rc("8410274114"));
		Assert::true($this->validation->rc("021231/9393"));
		Assert::true($this->validation->rc("0212319393"));
		Assert::true($this->validation->rc("010101/111"));
		Assert::true($this->validation->rc("010101111"));
		Assert::true($this->validation->rc("053113/5099"));
		Assert::true($this->validation->rc("0531135099"));
		Assert::true($this->validation->rc("068118/6066"));
		Assert::true($this->validation->rc("0681186066"));
		Assert::true($this->validation->rc("575707/0000"));

		Assert::false($this->validation->rc("575707/0001"));
		Assert::false($this->validation->rc("780123/3541"));
		Assert::false($this->validation->rc("7801233541"));

		Assert::exception(function () {
			$this->validation->rc("780123/354/0");
		}, "InvalidArgumentException", "RČ has bad format");
	}

	function testIc() {
		Assert::true($this->validation->ic("25596641"));
		Assert::true($this->validation->ic("27604977"));

		Assert::false($this->validation->ic("27604976"));

		Assert::exception(function () {
			$this->validation->ic("780123/3540");
		}, "InvalidArgumentException", "IČ has bad format");
	}
	function testAresIc() {
		Assert::equal((object)array(
			'ico'             => '27604977',
			'dic'             => 'CZ27604977',
			'firma'           => 'Google Czech Republic, s.r.o.',
			'ulice'           => 'Stroupežnického',
			'cisloOrientacni' => '17',
			'cisloPopisne'    => '3191',
			'mesto'           => 'Praha',
			'castObce'        => 'Smíchov',
			'psc'             => '15000',
		), $this->validation->aresIc("27604977"));

		Assert::exception(function () {
			$this->validation->ic("780123/3540");
		}, "InvalidArgumentException", "IČ has bad format");
	}

	function testAresIcNoCache() {
		$validation=new \Trejjam\Validation();

		Assert::equal((object)array(
			'ico'             => '27604977',
			'dic'             => 'CZ27604977',
			'firma'           => 'Google Czech Republic, s.r.o.',
			'ulice'           => 'Stroupežnického',
			'cisloOrientacni' => '17',
			'cisloPopisne'    => '3191',
			'mesto'           => 'Praha',
			'castObce'        => 'Smíchov',
			'psc'             => '15000',
		), $validation->aresIc("27604977"));

		Assert::exception(function () use ($validation) {
			$validation->ic("780123/3540");
		}, "InvalidArgumentException", "IČ has bad format");
	}
}

$test = new ValidationTest($container);
$test->run();