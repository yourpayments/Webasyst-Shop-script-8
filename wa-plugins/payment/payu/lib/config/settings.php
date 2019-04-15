<?php

	return [
		'merchant' => [
	        'value' => 'PAYUTEST',
	        'title' => 'Идентификатор мерчанта',
	        'description' => '',
	        'control_type' => waHtmlControl::INPUT,
	    ],
		'secretkey' => [
	        'value' => 'test',
	        'title' => 'Секретный ключ',
	        'description' => '',
	        'control_type' => waHtmlControl::INPUT,
	    ],
		'lu_url' => [
	        'value' => 'https://secure.payu.ru/order/lu.php',
	        'title' => 'Ссылка перехода на страницу платежной системы',
	        'description'  => '',
	        'control_type' => waHtmlControl::INPUT,
	    ],
		'back_ref' => [
	        'value' => 'NO',
	        'title' => 'Ссылка возврата клиента',
	        'description' => 'Если поставить значение NO, клиент останется в системе PayU. Если сделать поле пустым - Клиент вернется по дефолтной ссылке',
	        'control_type' => waHtmlControl::INPUT,
	    ],
		'price_currency' => [
	        'value' => 'RUB',
	        'title' => 'Валюта платежа',
	        'description' => 'Это значение должно соответствовать валюте вашего мерчанта',
	        'control_type' => waHtmlControl::INPUT,
	    ],
		'debug_mode' => [
	        'value' => '1',
	        'title' => 'Включить режим отладки',
	        'description' => '1 - отладка включена, 0 - отладка выключена',
	        'control_type' => waHtmlControl::INPUT,
	    ],
		'language' => [
	        'value' => 'RU',
	        'title' => 'Язык страницы платежной системы',
	        'description' => 'Доступны ( RU, EN, RO, DE, FR, IT, ES )',
	        'control_type' => waHtmlControl::INPUT,
	    ],
		'VAT' => [
	        'value' => '0',
	        'title' => 'НДС',
	        'description' => 'Если 0 - без НДС',
	        'control_type' => waHtmlControl::INPUT,
	    ],
	];
