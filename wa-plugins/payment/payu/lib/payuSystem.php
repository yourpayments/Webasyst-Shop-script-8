<?php

	class PayuSystem
	{

		/** @var string  */
		private $luUrl = 'https://secure.payu.ru/order/lu.php';
		/** @var string  */
		private $button = '<input value="Оплатить заказ на сайте «PayU»" type="submit">';
		/** @var bool  */
		private $debug = 0;
		/** @var string  */
		private $showinputs = "hidden";
		/** @var bool  */
		private $isWinEncode = false;
		/** @var self */
		private static $instance = null;
		/** @var string */
		private static $merchant = '';
		/** @var string */
		private static $key = '';
		/** @var bool  */
		private $isEncode = true;
		/** @var array */
		private $data = [];
		/** @var array  */
		private $dataArr = [];
		/** @var string  */
		private $answer = '';
		/** @var array  */
		private $LUcell = [
			'MERCHANT' => 1,
			'ORDER_REF' => 0,
			'ORDER_DATE' => 1,
			'ORDER_PNAME' => 1,
			'ORDER_PGROUP' => 0,
			'ORDER_PCODE' => 1,
			'ORDER_PINFO' => 0,
			'ORDER_PRICE' => 1,
			'ORDER_QTY' => 1,
			'ORDER_VAT' => 1,
			'ORDER_SHIPPING' => 1, 'PRICES_CURRENCY' => 1
		];
		/** @var array  */
		private $IPNcell = ['IPN_PID', 'IPN_PNAME', 'IPN_DATE', 'ORDERSTATUS'];
		/** @var array $cells */
		public $cells = [];

		/** Максимальная длинна заголовка товара */
		const MAX_ITEM_NAME_LEN = 155;
		/** URL для IRN Звпроса */
		const IRN_PAY_URL = 'https://secure.payu.ru/order/irn.php';

		/**
		 * @ignore
		 * PayuSystem constructor.
		 */
		private function __construct(){}

		/**
		 * @ignore
		 */
		private function __clone(){}

		/**
		 * Получение ответа платежной системы
		 * @return string
		 */
		public function __toString()
		{
			return $this->answer === '' ? '' : $this->answer;
		}

		/**
		 * Получение инстанта
		 * @return \PayuSystem
		 */
		public static function getInstance()
		{

			if(self::$instance === null)
				self::$instance = new self();

			return self::$instance;
		}

		/**
		 * Установка параметров системы
		 * @param array $params
		 * @return self $this
		 */
		function setOptions($params = array() )
		{

			if (
				!isset( $params['merchant'])
				|| !isset( $params['secretkey'])
				|| \mb_strlen($params['merchant']) == 0
				|| \mb_strlen($params['secretkey']) == 0
			)
				die("No params");

			self::$merchant = $params['merchant'];
			self::$key = $params['secretkey'];

			unset(
				$params['merchant'],
				$params['secretkey']
			);

			if((int) \count($params) == 0 ) return $this;

			foreach ($params as $k => $v) $this->$k = $v;

			return $this;
		}

		/**
		 * @param array $array
		 * @return self $this
		 */
		function setData($array = null )
		{

			if ($array === null )   die('wrong data');

			$this->dataArr = $array;

			return $this;
		}

		/**
		 * Генерация строки подписи
		 * @param null $data
		 * @return string
		 */
		function Signature($data = null )
		{
			$string = '';
			
			foreach ($data as $v) $string .= (\is_array($v)) ? $this->convertArray($v) : $this->convertString($v);
			
			if ($this->debug > 0) {
				$string .= '4TRUE';
			}
			
			return \hash_hmac("md5", $string, self::$key);
		}

		/**
		 * Конверстация строки (если надо - перекодирование)
		 * @param string $string
		 * @return string
		 */
		private function convertString($string)
		{
			//if ($this->isEncode)    $string = \iconv("windows-1251", "utf-8",  $string);
			return \mb_strlen($string, '8bit') . $string;
		}

		/**
		 * Конверртация массива
		 * @param array $array
		 * @return string
		 */
		private function convertArray($array)
		{

	        $return = '';
	        foreach ($array as $v) $return .= $this->convertString($v);
	        return $return;
		}

		/**
		 * Генерация формы на оплату
		 * @return $this
		 */
		public function LU()
		{

			/** @var array $request */
			$request = &$this->dataArr;
			$request['MERCHANT'] = self::$merchant;

			if($this->isWinEncode) $this->isEncode = true;
			if(!isset($request['ORDER_DATE']))
				$request['ORDER_DATE'] = date("Y-m-d H:i:s");

			$request['TESTORDER'] = $this->debug == 1 ? 'TRUE' : 'FALSE';
			$request['DEBUG'] = $this->debug;

			$request['ORDER_HASH'] = $this->Signature($this->checkArray($request));
			$this->answer = $this->genereteForm($request);

			return $this;
		}

		/**
		 * Проверка ввод данных
		 * @param array $data
		 * @return array
		 */
		private function checkArray($data)
		{
			/** @var array $ret */
			$ret = [];

			foreach($this->LUcell as $k => $v)
			{
				if (isset($data[$k]))
					$ret[$k] = $data[$k];
				elseif ($v == 1)
					die("$k is not set");
			}

			return $ret;
		}


		/**
		 * Генерация кода формы
		 * @param array $data
		 * @return string
		 */
		private function genereteForm($data)
		{

			$data['BILL_COUNTRYCODE'] = 'RU';

			$form = '<form method="post" action="'.$this->luUrl.'" id="payment-form" accept-charset="utf-8">';
			foreach ($data as $k => $v) $form .= $this->makeString($k, $v);
			return $form . $this->button."</form>";
		}


		/**
		 * Генерация inpu элементов формы
		 * @param string $name
		 * @param string|array $val
		 * @return string
		 */
		private function makeString ($name, $val )
		{

			$string = '';

			if(!\is_array($val))
				return '<input type="'.$this->showinputs.'" name="'.$name.'" value="'.\htmlspecialchars($val).'">'."\n";

			foreach ($val as $v)
				$string .= $this->makeString( $name.'[]', $v);

			return $string;
		}

		/**
		 * Обработка IPN запроса / ответа платежной системы
		 * @return bool
		 */
		public function IPN()
		{

			$result = $_POST;
			$isEncode = $this->isEncode;
			$this->isEncode = false;

			foreach ($this->IPNcell as $name)
				if (!isset($result[$name]))
					return false;

			$this->cells = $this->IPNcell;
			$hash = $result['HASH'];

			unset($result['HASH']);

			$sign = $this->Signature($result);

			if ($hash != $sign) return false;

			$datetime = \date('YmdHis');

			$sign = $this->Signature(
				[
					'IPN_PID' => $result[ 'IPN_PID' ][0],
					'IPN_PNAME' => $result[ 'IPN_PNAME' ][0],
					'IPN_DATE' => $result[ 'IPN_DATE' ],
					'DATE' => $datetime
				]
			);

			$this->answer = '<!-- <EPAYMENT>'.$datetime.'|'.$sign.'</EPAYMENT> -->';
			$this->isEncode = $isEncode;

			return true;
		}

		/**
		 * @deprecated
		 * @param string $type
		 * @return bool|string
		 */
		function checkBackRef( $type = "http")
		{
			$path = $type.'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
			$tmp = explode("?", $path);
			$url = $tmp[0].'?';
			$params = array();
			foreach ($_GET as $k => $v)
			{
				if ( $k != "ctrl" ) $params[] = $k.'='.rawurlencode($v);
			}
			$url = $url.implode("&", $params);
			$arr = array($url);
			$sign = $this->Signature( $arr );
			$this->answer = ( $sign === $_GET['ctrl'] ) ? true : false;
			return $this->answer;
		}

		/**
		 * Возврат денег
		 * @param array $fields
		 */
		public function IRN($fields = [])
		{

			$fields = [
				'MERCHANT' => self::$merchant,
				'ORDER_REF' => $fields['ORDER_REF'],
				'ORDER_AMOUNT' => $fields['ORDER_AMOUNT'],
				'ORDER_CURRENCY' => $fields['ORDER_CURRENCY'],
				'IRN_DATE' => \date('Y-m-d H:i:s'),
			];

			$fields['ORDER_HASH'] = $this->Signature($fields);

			$ch = \curl_init(self::IRN_PAY_URL);
			\curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			\curl_setopt($ch, CURLOPT_POST, true);
			\curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			\curl_exec($ch);
			\curl_close($ch);
		}


		/**
		 * Получение ответа для платежной системы
		 * @return string
		 */
		public function getResponse()
		{
			return $this->answer;
		}
	}