<?php

	return [
		'name' => 'Возврат платежей через PayU',
		'description' => 'Возврат денежный средств при удаление оплаченных заказов через систему PayU',
		'version' => '1.0',
		'handlers' => [
			'order_action.delete' => 'orderActionDelete',
		]
	];