<?php
namespace Craft;

class SproutEmail_CommerceOnOrderCompleteEvent extends SproutEmailBaseEvent
{
	/**
	 * Returns the qualified event name to use when registering with craft()->on
	 * 
	 * @return string
	 */
	public function getName()
	{
		return 'commerce_orders.onOrderComplete';
	}

	/**
	 * Returns the event title to use when displaying a label or similar use case
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return Craft::t('When an order is completed.');
	}

	/**
	 * Returns a short description of this event
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Triggers when an order is completed.');
	}

	/**
	 * Returns the data passed in by the triggered event
	 *
	 * @param Event $event
	 *
	 * @return mixed
	 */
	public function prepareParams(Event $event)
{
	return array(
		'value'      => $event->params['order']
	);
}

	/**
	 * Returns a rendered html string to use for capturing user input
	 *
	 * @return string
	 */
	public function getOptionsHtml($context = array())
	{
		$context['statuses'] = $this->getAllTransactionStatuses();

		return craft()->templates->render('sproutemail/_events/orderComplete', $context);
	}

	/**
	 * Returns the value that should be saved to options for the notification (registered event)
	 *
	 * @return mixed
	 */
	public function prepareOptions()
	{
		return array(
			'commerceStatuses' =>  craft()->request->getPost('commerceStatuses')
		);
	}

	/**
	 * Returns whether or not the entry meets the criteria necessary to trigger the event
	 *
	 * @param mixed      $options
	 * @param EntryModel $entry
	 * @param array      $params
	 *
	 * @return bool
	 */
	public function validateOptions($options, Commerce_OrderModel  $order, array $params = array())
	{

		if(!empty($options['commerceStatuses']))
		{
			// Get first transaction which is the current transaction
			if (!in_array($order->transactions[0]->status, $options['commerceStatuses']))
			{
				return false;
			}
		}

		return true;
	}

	public function getAllTransactionStatuses()
	{
		$statuses = [
			Commerce_TransactionRecord::STATUS_PENDING,
			Commerce_TransactionRecord::STATUS_REDIRECT,
			Commerce_TransactionRecord::STATUS_SUCCESS,
			Commerce_TransactionRecord::STATUS_FAILED
		];
		$options = array();
		if(!empty($statuses))
		{
			foreach($statuses as $status)
			{
				array_push(
					$options, array(
						'label' => ucwords($status),
						'value' => $status
					)
				);
			}
		}

		return $options;
	}
}