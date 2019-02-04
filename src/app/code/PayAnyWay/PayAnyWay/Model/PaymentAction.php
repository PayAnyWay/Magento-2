<?php
/**
 * Created by PhpStorm.
 * User: tim
 * Date: 13.09.16
 * Time: 10:17
 */

namespace PayAnyWay\PayAnyWay\Model;


class PaymentAction
{
	/**
	 * Options getter
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return array(
			array('value' => 'https://www.payanyway.ru/assistant.htm', 'label'=>'www.payanyway.ru'),
			array('value' => 'https://demo.moneta.ru/assistant.htm', 'label'=>'demo.moneta.ru')
		);
	}
}
