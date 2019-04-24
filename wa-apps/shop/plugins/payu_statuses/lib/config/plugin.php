<?php

	return array (
		'name' => 'Статусы платежей системы PayU',
		'description' => 'Получение статусов платежей PayU',
		'vendor' => 1201031,
		'version' => '1.0.0',
		'img' => 'img/payu-logo-webasyst.png',
		'handlers' => [
			'order_action.delete' => 'orderActionDelete',
		]
	);