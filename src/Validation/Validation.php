<?php
/**
 * Created by PhpStorm.
 * User: Jan
 * Date: 26. 10. 2014
 * Time: 17:38
 */

namespace Trejjam\NetteLibs;

use Nette\Caching;

class Validation
{
	/**
	 * @var Caching\Cache
	 */
	private $cache;

	public function __construct(Caching\Cache $cache) {
		$this->cache=$cache;
	}

	/**
	 * @param string $rc Rodné číslo
	 * @return bool Je rodné číslo validní
	 */
	function rc($rc) {
		// "be liberal in what you receive"
		if (!preg_match('#^\s*(\d\d)(\d\d)(\d\d)[ /]*(\d\d\d)(\d?)\s*$#', $rc, $matches)) {
			return FALSE;
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
			return FALSE;
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
			/** @var \Edge\Ares\Ares $ares */
			/** @var \Edge\Ares\Container\Address $address */
			$address = $ares->fetchSubjectAddress($ic);

			$out=[
				"ico"=>$address->getIco(),
				"dic"=>$address->getDic(),
				"firma"=>$address->getFirma(),
				"ulice"=>$address->getUlice(),
				"cisloOrientacni"=>$address->getCisloOrientacni(),
				"cisloPopisne"=>$address->getCisloPopisne(),
				"cisloMesto"=>$address->getMesto(),
				"castObce"=>$address->getCastObce(),
				"psc"=>$address->getPsc(),
			];

			$this->setCacheIc($ic, $out);
		}
		catch (\Edge\Ares\Exception\ExceptionInterface $e) {
			// Do some error handling here.
			return FALSE;
		}

		return $out;
	}

	private function setCacheIc($ic, array $address) {
		$this->cache->save($ic, json_encode($address), [
			Caching\Cache::TAGS   => ["ico"],
			Caching\Cache::EXPIRE => '60 minutes',
		]);
	}
	private function getCacheIc($ic) {
		if (!is_null(
			$out=$this->cache->load($ic)
		)) {
			return json_decode($out);
		}
		return null;
	}
}