<?php
/**
 * Created by PhpStorm.
 * User: Jan
 * Date: 26. 10. 2014
 * Time: 17:38
 */

namespace Trejjam;

use Nette,
	Nette\Caching,
	Tracy\Debugger;

class Validation extends Nette\Object
{
	/** @var array of function(Validation $validation, ResultSet|Exception $result); Occurs after data load */
	public $onAres;

	private $panel;

	/**
	 * @var Caching\Cache
	 */
	private $cache;

	private $timeout;

	public function __construct(Caching\Cache $cache = NULL) {
		$this->cache=$cache;
	}

	/**
	 * @internal
	 * @param ValidationPanel $panel
	 */
	public function injectPanel(ValidationPanel $panel) {
		$this->panel = $panel->register($this);
	}

	public function setTimeout($timeout) {
		$this->timeout= $timeout;
	}

	/**
	 * @param string $rc Rodné číslo
	 * @return bool Je rodné číslo validní
	 */
	function rc($rc) {
		// "be liberal in what you receive"
		if (!preg_match('#^\s*(\d\d)(\d\d)(\d\d)[ /]*(\d\d\d)(\d?)\s*$#', $rc, $matches)) {
			throw new \InvalidArgumentException("RČ has bad format");
		}

		list(, $year, $month, $day, $ext, $c) = $matches;

		// do roku 1954 přidělovaná devítimístná RČ nelze ověřit
		if ($c === '') {
			return $year < 54;
		}

		// kontrolní číslice
		$mod = ($year . $month . $day . $ext) % 11;
		if ($mod === 10) $mod = 0;
		if ($mod !== (int)$c) {
			return FALSE;
		}

		// kontrola data
		$year += $year < 54 ? 2000 : 1900;

		// k měsíci může být připočteno 20, 50 nebo 70
		if ($month > 70 && $year > 2003) $month -= 70;
		elseif ($month > 50) $month -= 50;
		elseif ($month > 20 && $year > 2003) $month -= 20;

		if (!checkdate($month, $day, $year)) {
			return FALSE;
		}

		// cislo je OK
		return TRUE;
	}
	/**
	 * @param string $ic IČO
	 * @return bool Je IČO validní
	 */
	function ic($ic) {
		// "be liberal in what you receive"
		$ic = preg_replace('#\s+#', '', $ic);

		// má požadovaný tvar?
		if (!preg_match('#^\d{8}$#', $ic)) {
			throw new \InvalidArgumentException("IČ has bad format");
		}

		// kontrolní součet
		$a = 0;
		for ($i = 0; $i < 7; $i++) {
			$a += $ic[$i] * (8 - $i);
		}

		$a = $a % 11;

		if ($a === 0) $c = 1;
		elseif ($a === 10) $c = 1;
		elseif ($a === 1) $c = 0;
		else $c = 11 - $a;

		$valid = (int)$ic[7] === $c;

		return $valid;
	}
	/**
	 * @param $ic
	 * @return bool|array
	 */
	function aresIc($ic) {
		if (!$this->ic($ic)) return false;

		if (!is_null(
			$address=$this->getCacheIc($ic)
		)) {
			return $address;
		}

		$parser = new \Edge\Ares\Parser\AddressParser();
		$provider = new \Edge\Ares\Provider\HttpProvider();
		$ares = new \Edge\Ares\Ares($parser, $provider);

		try {
			Debugger::timer('ares-curl');

			/** @var \Edge\Ares\Ares $ares */
			/** @var \Edge\Ares\Container\Address $address */
			$address = $ares->fetchSubjectAddress($ic);

			$out=(object)[
				"ico"=>$address->getIco(),
				"dic"=>$address->getDic(),
				"firma"=>$address->getFirma(),
				"ulice"=>$address->getUlice(),
				"cisloOrientacni"=>$address->getCisloOrientacni(),
				"cisloPopisne"=>$address->getCisloPopisne(),
				"mesto"=>$address->getMesto(),
				"castObce"=>$address->getCastObce(),
				"psc"=>$address->getPsc(),
			];

			$this->onAres($this, [
				"time"=> Debugger::timer('ares-curl'),
				"ic"=>$ic,
				"data"=>$out
			]);

			$this->setCacheIc($ic, $out);
		}
		catch (\Edge\Ares\Exception\ExceptionInterface $e) {
			// Do some error handling here.

			$this->onAres($this, [
				"time" => Debugger::timer('ares-curl'),
				"ic"   => $ic,
				"exception" => $e
			]);

			return FALSE;
		}

		return $out;
	}

	private function useCache() {
		return !is_null($this->cache);
	}
	private function setCacheIc($ic, \stdClass $address) {
		if (!$this->useCache()) return;

		$this->cache->save($ic, json_encode($address), [
			Caching\Cache::TAGS   => ["ico"],
			Caching\Cache::EXPIRE => $this->timeout,
		]);
	}
	private function getCacheIc($ic) {
		if (!$this->useCache()) return null;

		if (!is_null(
			$out=$this->cache->load($ic)
		)) {
			return json_decode($out);
		}
		return null;
	}
}