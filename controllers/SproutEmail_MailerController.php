<?php

namespace Craft;

class SproutEmail_MailerController extends BaseController
{
	/**
	 * Renders the Mailer Edit template
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionEditSettingsTemplate(array $variables = array())
	{
		$mailerId = isset($variables['mailerId']) ? $variables['mailerId'] : null;
		$settings = isset($variables['settings']) ? $variables['settings'] : null;

		if (!$mailerId)
		{
			throw new HttpException(404, Craft::t('No mailer id was provided'));
		}

		$mailer = sproutEmail()->mailers->getMailerByName($mailerId);

		if (!$mailer)
		{
			throw new HttpException(404, Craft::t('No mailer was found with that id'));
		}

		if (!$settings)
		{
			$settings = $mailer->getSettings();
		}

		$this->renderTemplate('sproutemail/settings/mailers/edit', array(
			'mailer'   => $mailer,
			'settings' => $settings
		));
	}

	/**
	 * Validate and save settings across mailers
	 *
	 * @throws HttpException
	 */
	public function actionSaveSettings()
	{
		$this->requirePostRequest();

		$settings = null;

		$mailerId = craft()->request->getRequiredPost('mailerId');
		$mailer   = sproutEmail()->mailers->getMailerByName($mailerId);

		if ($mailer)
		{
			$record = sproutEmail()->mailers->getMailerRecordByName($mailer->getId());

			// Create record for new mailer setting
			if (!$record) {
			    $record = new SproutEmail_MailerRecord();
                $record->name = $mailer->getId();
            }

			if ($record)
			{
				$record->setAttribute('settings', $mailer->prepSettings());

				if ($record->validate())
				{
					if ($record->save(false))
					{
						craft()->userSession->setNotice(Craft::t('Settings successfully saved.'));

						$this->redirectToPostedUrl($record);
					}
				}
			}
		}

		craft()->userSession->setError(Craft::t('Unable to save settings.'));

		craft()->urlManager->setRouteVariables(array(
			'settings' => $settings
		));
	}

	/**
	 * Provides a way for mailers to render content to perform actions inside a a modal window
	 *
	 * @throws HttpException
	 */
	public function actionGetPrepareModal()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$mailer         = craft()->request->getRequiredPost('mailer');
		$emailId        = craft()->request->getRequiredPost('emailId');
		$campaignTypeId = craft()->request->getRequiredPost('campaignTypeId');

		$modal = sproutEmail()->mailers->getPrepareModal($mailer, $emailId, $campaignTypeId);

		$this->returnJson($modal->getAttributes());
	}
}
