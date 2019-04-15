<?php


	class shopPayuPlugin extends waPlugin
	{

		const PLUGIN_NAME = 'payu';

		function orderActionDelete($params)
		{


			if ($params['action_id'] !== 'delete') return;

			$order = (new \shopOrderModel())->getOrder((int) $params['order_id']);

			if(
				!isset($order['params'])
				|| !isset($order['params']['payment_plugin'])
				|| $order['params']['payment_plugin'] !== self::PLUGIN_NAME
			)
				return;

			$transactions = (new \waTransactionModel())->getByFields(
				[
					'order_id' => (int) $params['order_id'],
				]
			);

			if (!\is_array($transactions) || (int) \count($transactions) == 0) return;

			/** @var array $transaction */
			foreach ($transactions as $transaction)
			{

				if (
					isset($transaction['merchant_id'])
					&& (int) $transaction['merchant_id'] > 0
					&& isset($transaction['native_id'])
					&& \mb_strlen($transaction['native_id']) > 0
					&& isset($transaction['amount'])
					&& \mb_strlen($transaction['amount']) > 0
					&& isset($transaction['currency_id'])
					&& \mb_strlen($transaction['currency_id']) > 0
					&& isset($transaction['plugin'])
					&& $transaction['plugin'] === self::PLUGIN_NAME
				)
				{

					$params['merchant_id'] = (int) $transaction['merchant_id'];
					$params['transaction'] = $transaction;
				}
			}

			$model = new \waModel();
			/** @var \waDbResultSelect $result */
			$result = $model->query('SELECT * FROM shop_plugin_settings where `id` = \''. (int) $params['merchant_id'].'\'');

			/** @var waDbResultIterator $iterator */
			$iterator = $result->getIterator();
			$iterator->rewind();

			while($iterator->valid())
			{

				$params['merchant'][$iterator->current()['name']] = $iterator->current()['value'];
				$iterator->next();
			}

			if(
				isset($params['merchant']['merchant'])
				&& \mb_strlen($params['merchant']['merchant']) > 0
				&& isset($params['merchant']['secretkey'])
				&& \mb_strlen($params['merchant']['secretkey']) > 0
				&& \file_exists(__DIR__ . '/../../../../../wa-plugins/payment/payu/lib/payuSystem.php')
			)
			{

				require_once (__DIR__ . '/../../../../../wa-plugins/payment/payu/lib/payuSystem.php');

				\PayuSystem::getInstance()->setOptions(
					[
						'merchant' => $params['merchant']['merchant'],
						'secretkey' => $params['merchant']['secretkey'],
					]
				)->IRN(
					[
						'ORDER_REF' => $params['transaction']['native_id'],
						'ORDER_AMOUNT' => $params['transaction']['amount'],
						'ORDER_CURRENCY' => $params['transaction']['currency_id'],
					]
				);
			}
		}
	}