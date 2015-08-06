<?php
/**
 * Created by PhpStorm.
 * User: Jan
 * Date: 15. 11. 2014
 * Time: 11:25
 */

namespace Trejjam\Validation;

use Nette,
	Tracy,
	Tracy\Bar,
	Tracy\Debugger,
	Nette\Utils\Html;


class ValidationPanel extends Nette\Object implements Tracy\IBarPanel
{
	/** @var int logged time */
	private $totalTime = 0;

	private $messages = [];

	/**
	 * Renders HTML code for custom tab.
	 * @return string
	 */
	function getTab()
	{
		$img = Html::el('img', array(
			'height' => '16px',
			'src'    => 'data:image/png;base64,' . base64_encode(file_get_contents(__DIR__ . '/ares.png'))
		));
		$tab = Html::el('span', array('title' => 'ARES'))->add($img);
		$title = Html::el()->setText('ARES');
		if ($this->messages) {
			$title->setText(count($this->messages) . ' load' . ($this->totalTime ? sprintf(' / %0.1f ms', $this->totalTime * 1000) : ''));
		}

		return (string)$tab->add($title);
	}

	/**
	 * Renders HTML code for custom panel.
	 * @return string
	 */
	function getPanel()
	{
		if (!$this->messages) {
			return NULL;
		}
		ob_start();
		$esc = callback('Nette\Templating\Helpers::escapeHtml');
		$click = class_exists('\Tracy\Dumper')
			? function ($o, $c = FALSE) {
				return \Tracy\Dumper::toHtml($o, array('collapse' => $c));
			}
			: callback('\Tracy\Helpers::clickableDump');
		require __DIR__ . '/panel.phtml';

		return ob_get_clean();
	}

	public function logAres(Validation $validation, $result)
	{
		$this->totalTime += $result["time"];
		$this->messages[] = $result;
	}

	/**
	 * @param Validation $connection
	 * @return ValidationPanel
	 */
	public function register(Validation $validation)
	{
		$validation->onAres[] = array($this, 'logAres');

		self::getDebuggerBar()->addPanel($this);

		return $this;
	}

	/**
	 * @return Bar
	 */
	private static function getDebuggerBar()
	{
		return method_exists('Tracy\Debugger', 'getBar') ? Debugger::getBar() : NULL;
	}
} 