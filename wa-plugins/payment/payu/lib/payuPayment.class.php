<?php

	if(file_exists(__DIR__ . \DIRECTORY_SEPARATOR . 'payuSystem.php'))
		require_once __DIR__ . \DIRECTORY_SEPARATOR . 'payuSystem.php';

	/**
	 * Class payuPayment
	 * @property string price_currency
	 * @property string merchant
	 * @property string secretkey
	 * @property string debug_mode
	 * @property string lu_url
	 * @property string VAT
	 * @property string language
	 * @property string back_ref
	 * @property string auto_mode
	 */
	class payuPayment extends \waPayment implements \waIPayment, \waIPaymentCancel, \waIPaymentRefund, \waIPaymentRecurrent
	{

		/** Размелитель запроса */
		const APP_ID_SEP = '::';
		/** платеж авторизирован (для оплаты не через Visa/MasterCard/Maestro такой статус означает списание денежных средств) */
		const ORDERSTATUS_PAYMENT_AUTHORIZED = 'PAYMENT_AUTHORIZED';
		/** платёж завершён, деньги списаны со счёта клиента */
		const ORDERSTATUS_COMPLETE= 'COMPLETE';
		/** платёж отменен (отменена блокировка денежных средств на карте) */
		const ORDERSTATUS_REVERSED = 'REVERSED';
		/** сумма платежа возвращена */
		const ORDERSTATUS_REFUND = 'REFUND';
		/** аналогично PAYMENT_AUTHORIZED для Евросеть и связной */
		const ORDERSTATUS_PAYMENT_AUTHORIZED_ES = '-';
		/** Метод оплаты картой */
		const PAYMETHOD_CARD = 'Visa/MasterCard/Eurocard';
		/** Метод оплаты связной / евросеть */
		const PAYMETHOD_ES = 'EUROSET_SVYAZNOI';

		/**
		 * Получение код валюты
		 * @return string|string[] ISO3 currency code
		 */
	    public function allowedCurrency()
	    {
	        return $this->price_currency ? $this->price_currency : 'RUB';
	    }

		/**
		 * Генерация формы оплаты
		 * @param array $payment_form_data
		 * @param \waOrder $order_data
		 * @param bool $auto_submit
		 * @return string
		 */
		public function payment($payment_form_data, $order_data, $auto_submit = false)
	    {

			$order = \waOrder::factory($order_data);

		    $params = [
				'merchant' => $this->merchant,
	            'secretkey' => $this->secretkey,
	            'debug' => $this->debug_mode,
				'luUrl'=>$this->lu_url
	        ];

			$sendFields = [
				'MERCHANT' => $params['merchant'],
				'ORDER_REF' => (int) $order->id,
				'ORDER_DATE' => $order_data['data']['datetime'], // Y-m-d H:i:s : 2017-07-17 12:24:06
			];

			foreach($order->items as $item)
			{

				$sendFields['ORDER_PNAME'][] = \mb_substr($item['name'], 0, \PayuSystem::MAX_ITEM_NAME_LEN);
				$sendFields['ORDER_PCODE'][] = 2;
				$sendFields['ORDER_PRICE'][] = (double) $item['price'];
				$sendFields['ORDER_VAT'][] = $this->VAT;
				$sendFields['ORDER_QTY'][] = (int) $item['quantity'];
			}

		    $sendFields['PRICES_CURRENCY'] = $this->price_currency;
			$sendFields['ORDER_SHIPPING'] = (double) $order->shipping;
			$sendFields['LANGUAGE'] = $this->language;

			if ((int) $order->contact_id > 0)
			{

	            $contact = new \waContact((int) $order->contact_id);

				$sendFields = \array_merge(
					$sendFields,
					[
						'BILL_FNAME' => $contact->get('firstname'),
						'BILL_LNAME' => $contact->get('lastname'),
						'BILL_EMAIL' => $contact->get('email')[0]['value'],
						'BILL_PHONE' => $contact->get('phone')[0]['value'],
						'BILL_ADDRESS' => $order->billing_address['address'],
						'BILL_CITY' => $order->billing_address['city'],
					]
				);
			}

		    $sendFields['BILL_ADDRESS2'] = \implode(
		    	self::APP_ID_SEP,
			    [
			    	$this->app_id,
				    $this->merchant_id
			    ]
		    );

			if($this->auto_mode == 1) $sendFields['AUTOMODE'] = 1;
			if($this->back_ref != '') $sendFields['BACK_REF'] = $this->back_ref;

			$form = \PayuSystem::getInstance()->setOptions($params)->setData($sendFields)->LU();
		
	        $view = \wa()->getView();
	        $view->assign('form', $form);

	        return $view->fetch($this->path.'/templates/payment.html');
	    }

		/**
		 * Инициализация плагина оплаты
		 * @param array $request
		 * @return \waPayment
		 */
		protected function callbackInit($request)
		{

			list($this->app_id, $this->merchant_id) = \explode(
				self::APP_ID_SEP,
				$request['ADDRESS2']
			);

			return parent::callbackInit($request);
		}

		/**
		 * Обработка ответа платежной системы
		 * @param array $request
		 * @return array
		 */
		public function callbackHandler($request)
	    {

	    	$transaction_data = $this->formalizeData($request);

	    	$payAnswer = \PayuSystem::getInstance()->setOptions(
				[
					'merchant' => $this->merchant,
					'secretkey' => $this->secretkey,
				]
			)->IPN();
			
		    $this->checkRequestOrderStatus($request);

			if($payAnswer)
			{

				switch($this->checkRequestOrderStatus($request))
				{

					/** Оплата заказа */
					case self::CALLBACK_PAYMENT:

						$transaction_data = $this->saveTransaction(
							$transaction_data,
							$request
						);

						$this->execAppCallback(
							self::CALLBACK_PAYMENT,
							$transaction_data
						);
					break;

					/** Отмена заказа, возврат денег */
					case self::CALLBACK_REFUND:

						$transaction_data = $this->saveTransaction(
							$transaction_data,
							$request
						);

						$this->execAppCallback(
							self::CALLBACK_REFUND,
							$transaction_data
						);
					break;

					default:
					break;
				}
			}
			
		    echo \PayuSystem::getInstance()->getResponse();

			return [];
	    }

		/**
		 * Проверка кода авторизации
		 * @param array $request
		 * @return string
		 */
		private function checkRequestOrderStatus($request = [])
	    {

	    	$params = [
	    		'orderStatus' => $request['ORDERSTATUS'],
			    'payMethod' => $request['PAYMETHOD'],
		    ];

	    	/** Оплата заказа */
		    if($params['orderStatus'] == self::ORDERSTATUS_COMPLETE)
			    return self::CALLBACK_PAYMENT;

		    /** Оплата заказа */
		    if(
		    	$params['orderStatus'] == self::ORDERSTATUS_PAYMENT_AUTHORIZED
			    && $params['payMethod'] != self::PAYMETHOD_CARD
		    )
			    return self::CALLBACK_PAYMENT;

		    /** Отмена платежа, отказ блокировки */
		    if(
		    	$params['orderStatus'] == self::ORDERSTATUS_REFUND
			    || $params['orderStatus'] == self::ORDERSTATUS_REVERSED
		    )
		    	return self::CALLBACK_REFUND;

	    	return '';
	    }

		protected function formalizeData($transaction_raw_data)
	    {

			$transaction_data = parent::formalizeData($transaction_raw_data);

			$transaction_data['native_id'] = $ord = (int) $_POST['ORDER_REF'];
	        $transaction_data['order_id'] = $ord = (int) $_POST['REFNOEXT'];
	        $transaction_data['amount'] = $_POST['IPN_TOTALGENERAL'];
			$transaction_data['currency_id'] = $this->price_currency;

			return $transaction_data;
		}


		/**
		 * Отмена заказ / возврат
		 * @param array [string]mixed $transaction_raw_data['order_data']
		 * @param array [string]mixed $transaction_raw_data['transaction_type']
		 * @param array [string]mixed $transaction_raw_data['customer_data']
		 * @param array [string]mixed $transaction_raw_data['transaction']
		 * @param array [string]mixed $transaction_raw_data['refund_amount']
		 */

		public function cancelOrder($transaction_raw_data)
		{

			\PayuSystem::getInstance()->setOptions(
				[
					'merchant' => $this->merchant,
					'secretkey' => $this->secretkey,
				]
			)->IRN(
				[
					'ORDER_REF' => $transaction_raw_data['native_id'],
					'ORDER_AMOUNT' => $transaction_raw_data['amount'],
					'ORDER_CURRENCY' => $transaction_raw_data['currency_id'],
				]
			);
		}

		public function cancel($transaction_raw_data)
		{

			$this->cancelOrder($transaction_raw_data);
		}

		public function refund($transaction_raw_data)
		{

			$this->cancelOrder($transaction_raw_data);
		}

		public function recurrent($transaction_raw_data)
		{

			$this->cancelOrder($transaction_raw_data);
		}
	}