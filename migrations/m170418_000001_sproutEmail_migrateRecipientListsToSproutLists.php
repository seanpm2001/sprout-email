<?php

namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m170418_000001_sproutEmail_migrateRecipientListsToSproutLists extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		//	Check if Sprout Lists is installed
		$sproutLists = craft()->plugins->getPlugin('sproutLists', false);

		// Check if Sprout Email has Recipient Lists
		$recipientLists = craft()->db->createCommand()
			->select('*')
			->from('sproutemail_defaultmailer_recipientlists')
			->queryAll();

		// If both true, then
		if ($sproutLists && $sproutLists->isInstalled && count($recipientLists))
		{
			$count      = 0;
			$listsByKey = array();

			// Migrate Recipient Lists to craft_sproutlists_lists
			foreach ($recipientLists as $recipientList)
			{
				$list         = new SproutLists_ListModel();
				$list->name   = $recipientList['name'];
				$list->handle = $recipientList['handle'];

				sproutLists()->lists->saveList($list);

				$listsByKey[$recipientList['id']]                 = $recipientList;
				$listsByKey[$recipientList['id']]['newElementId'] = $list->id;

				$count++;
			}

			// Get existing Subscribers
			$subscribers = craft()->db->createCommand()
				->select('*')
				->from('sproutemail_defaultmailer_recipients')
				->queryAll();

			// Get existing Subscriptions
			$subscriptions = craft()->db->createCommand()
				->select('*')
				->from('sproutemail_defaultmailer_recipientlistrecipients')
				->queryAll();

			$count            = 0;
			$subscribersByKey = array();

			// Migrate Recipients to craft_sproutlists_subscriptions
			foreach ($subscribers as $subscriber)
			{
				$subscriberModel            = new SproutLists_SubscriberModel();
				$subscriberModel->email     = $subscriber['email'];
				$subscriberModel->firstName = $subscriber['firstName'];
				$subscriberModel->lastName  = $subscriber['lastName'];

				sproutLists()->subscribers->saveSubscriber($subscriberModel);

				$subscribersByKey[$subscriber['id']]                 = $subscriber;
				$subscribersByKey[$subscriber['id']]['newElementId'] = $subscriberModel->id;

				$count++;
			}

			// Migrate Subscriptions to craft_sproutlists_subscriptions
			foreach ($subscriptions as $subscription)
			{
				$subscriptionModel                  = new SproutLists_SubscriberModel();
				$subscriptionModel->id              = $subscribersByKey[$subscription['recipientId']]['newElementId'];
				$subscriptionModel->subscriberLists = array(
					0 => $listsByKey[$subscription['recipientListId']]['newElementId']
				);

				sproutLists()->subscriptions->saveSubscriptions($subscriptionModel);
			}

			sproutLists()->subscribers->updateTotalSubscribersCount();

			// Check sproutemail_campaigns_entries_recipientlists
			// and update 'emailId' to reference listId in the 'list' column for listSettings
			$notificationEmails = craft()->db->createCommand()
				->select('*')
				->from('sproutemail_notificationemails')
				->queryAll();

			foreach ($notificationEmails as $notificationEmail)
			{
				$oldListIds = JsonHelper::decode($notificationEmail['listSettings']);
				$newListIds = array();

				if (isset($oldListIds['listIds']) and count($oldListIds['listIds']))
				{
					foreach ($oldListIds['listIds'] as $oldListId)
					{
						if (isset($listsByKey[$oldListId]['newElementId']))
						{
							$newListIds[] = $listsByKey[$oldListId]['newElementId'];
						}
					}

					$listSettings = array(
						'listIds' => $newListIds
					);

					craft()->db->createCommand()->update('sproutemail_notificationemails', array(
						'listSettings' => JsonHelper::encode($listSettings)
					),
						'id= :id', array(':id' => $notificationEmail['id'])
					);
				}
			}

			//{"listIds":["865", "866"]}

		}
	}
}
