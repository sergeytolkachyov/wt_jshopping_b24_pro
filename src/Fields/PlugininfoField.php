<?php
/**
 * @package       WT JShopping Bitrix 24 PRO
 * @version       3.2.2
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2020 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since         2.3.0
 */

namespace Joomla\Plugin\System\Wt_jshopping_b24_pro\Fields;
defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\NoteField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use \Joomla\CMS\Factory;
use Joomla\Plugin\System\Wt_jshopping_b24_pro\Library\CRest;

//FormHelper::loadFieldClass('note');

class PlugininfoField extends NoteField
{

	protected $type = 'Plugininfo';

	/**
	 * Method to get the field input markup for a spacer.
	 * The spacer does not have accept input.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.7.0
	 */
	protected function getInput()
	{
		return ' ';
	}

	/**
	 * @return  string  The field label markup.
	 *
	 * @since   1.7.0
	 */
	protected function getLabel()
	{

		$wtb24_plugin_info = simplexml_load_file(JPATH_SITE . "/plugins/system/wt_jshopping_b24_pro/wt_jshopping_b24_pro.xml");
		$wa                = Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineStyle('
			#web_tolk_link {
			text-align: center;
			}
			#web_tolk_link::before{
				content: "";
			}
		');
		if (PluginHelper::isEnabled('system', 'wt_jshopping_b24_pro') === true)
		{
			$plugin          = PluginHelper::getPlugin('system', 'wt_jshopping_b24_pro');
			$params          = (!empty($plugin->params) ? json_decode($plugin->params) : '');
			$crm_host        = (!empty($params->crm_host) ? $params->crm_host : '');
			$webhook_secret  = (!empty($params->crm_webhook_secret) ? $params->crm_webhook_secret : '');
			$crm_assigned_id = (!empty($params->crm_assigned) ? $params->crm_assigned : '');

			if (!empty($crm_host) && !empty($webhook_secret) && !empty($crm_assigned_id))
			{

				$resultBitrix24 = CRest::call("profile", ["id" => $crm_assigned_id]);

				if (isset($resultBitrix24["result"]))
				{
					if ($resultBitrix24["result"]["ADMIN"] == 1)
					{
						$is_admin = Text::_('JYES');
					}
					else
					{
						$is_admin = Text::_('JNO');
					}
					$webhook_info = '<div class="webhook-info row">
                        <div class="col-1 overflow-hidden img-thumbnail" style="background: url(\'' . $resultBitrix24["result"]["PERSONAL_PHOTO"] . '\') no-repeat center; background-size:cover"></div>
                        <div class="col-11">
	                        <p><strong>Assigned:</strong> ' . $resultBitrix24["result"]["NAME"] . ' ' . $resultBitrix24["result"]["LAST_NAME"] . ' 
	                        <strong>Assigned ID:</strong> ' . $resultBitrix24["result"]["ID"] . ' <strong>Is admin:</strong> ' . $is_admin . '</p>
						</div>

                    </div>';

				}
				elseif (isset($resultBitrix24['error']))
				{
					Factory::getApplication()->enqueueMessage("<strong>Bitrix 24 Error:</strong> " . $resultBitrix24['error'] . " " . $resultBitrix24['error_description'], 'error');
					$webhook_info = '<div class="webhook-info"><strong>Bitrix 24 Error</strong> <p>' . $resultBitrix24['error'] . '</p><p>' . $resultBitrix24['error_description'] . '</p></div>';
				}
			}
			else
			{
				$webhook_info = '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i></span> <span class="text-danger">Not connected to Bitrix 24</span>';
			}
		}
		else
		{
			$webhook_info = '';
		}


		return $html = '</div>
		<div class="card container shadow-sm w-100 p-0">
			<div class="wt-b24-plugin-info row">
				<div class="col-2 d-flex justify-content-center align-items-center">
					<a href="https://web-tolk.ru" target="_blank" id="web_tolk_link" title="Go to https://web-tolk.ru">
							<svg width="200" height="50" viewBox="0 0 100 50" xmlns="http://www.w3.org/2000/svg">
								 <g>
								  <title>Go to https://web-tolk.ru</title>
								  <text font-weight="bold" xml:space="preserve" text-anchor="start" font-family="Helvetica, Arial, sans-serif" font-size="32" id="svg_3" y="36.085949" x="8.152073" stroke-opacity="null" stroke-width="0" stroke="#000" fill="#0fa2e6">Web</text>
								  <text font-weight="bold" xml:space="preserve" text-anchor="start" font-family="Helvetica, Arial, sans-serif" font-size="32" id="svg_4" y="36.081862" x="74.239105" stroke-opacity="null" stroke-width="0" stroke="#000" fill="#384148">Tolk</text>
								 </g>
							</svg>
				</a>
				</div>
				<div class="col-10">
					<div class="card-header bg-white p-1">
					<span class="badge bg-success">v.' . $wtb24_plugin_info->version . '</span>
					<span class="ms-auto"><svg width="150" height="27" viewBox="0 0 150 35" xmlns="http://www.w3.org/2000/svg">
						<g fill="none">
							<path d="M132.5 25.4h17.6v-3.9h-11.6c1.6-6.4 11.4-7.8 11.4-14.9 0-3.8-2.6-6.6-8.1-6.6-3.4 0-6.4 1-8.4 2l1.2 3.6c1.8-.9 3.9-1.7 6.5-1.7 2 0 3.9.9 3.9 3.2 0 5.2-11.5 5.6-12.5 18.3zm18.1-6.2h11.3v6.2h4.5v-6.2h3.8v-3.8h-3.8V0h-3.3l-12.5 16.2v3zm5.1-3.6 6.4-8.6c0 .7-.2 2.9-.2 4.9v3.6h-3c-.9 0-2.6.1-3.2.1z"
								  fill="#005893"></path>
							<path d="M4.7 21.6v-7.9h1.9c1.7 0 3.1.2 4.1.8 1 .6 1.6 1.6 1.6 3.2 0 2.7-1.6 3.9-5.4 3.9H4.7zM.1 25.4h6.7c7.5 0 10.2-3.3 10.2-7.9 0-3.1-1.3-5.2-3.6-6.4-1.8-1-4.1-1.3-6.9-1.3H4.7v-6h10.1L16 .1H0v25.3h.1zm20 0h4.4l5.7-8.2c1.1-1.5 1.9-3 2.4-3.8h.1c-.1 1.1-.2 2.5-.2 3.9v8H37v-18h-4.4l-5.7 8.2c-1 1.5-1.9 3-2.4 3.8h-.1c.1-1.1.2-2.5.2-3.9v-8h-4.5v18zm24.6 0h4.6V11.1h5.4l1.2-3.8H39.2v3.8h5.5v14.3zm12.8 9.1H62v-9.1c.9.3 1.8.4 2.8.4 5.7 0 9.4-3.9 9.4-9.5 0-5.8-3.4-9.5-9.9-9.5-2.5 0-4.9.5-6.9 1.1v26.6h.1zm4.5-13V10.9c.7-.2 1.3-.3 2.1-.3 3.3 0 5.4 1.8 5.4 5.7 0 3.5-1.7 5.7-5.1 5.7-.9 0-1.6-.2-2.4-.5zm14.9 3.9h4.4l5.7-8.2c1.1-1.5 1.9-3 2.4-3.8h.1c-.1 1.1-.2 2.5-.2 3.9v8h4.5v-18h-4.4l-5.7 8.2c-1 1.5-1.9 3-2.4 3.8h-.1c.1-1.1.2-2.5.2-3.9v-8h-4.5v18zm20.5 0h4.6v-7.5h2.7c.5 0 1 .5 1.6 1.7l2.3 5.8h4.9l-3.3-6.9c-.6-1.2-1.2-1.9-2.1-2.2v-.1c1.5-.9 1.7-3.5 2.6-4.8.3-.4.7-.6 1.3-.6.3 0 .7 0 1 .2V7.1c-.5-.2-1.4-.3-1.9-.3-1.6 0-2.6.6-3.3 1.6-1.5 2.2-1.5 6-3.7 6H102V7.3h-4.6v18.1zm26.3.4c2.5 0 4.8-.8 6.2-1.8l-1.3-3.1c-1.3.7-2.5 1.2-4.2 1.2-3.1 0-5.1-2-5.1-5.7 0-3.3 2-5.9 5.4-5.9 1.8 0 3.1.5 4.4 1.4V8c-1-.6-2.6-1.2-4.9-1.2-5.4 0-9.6 4-9.6 9.7 0 5.2 3.2 9.3 9.1 9.3z"
								  fill="#0BBBEF"></path>
							<path d="M185.1 19.2c4.9 0 8.9-4 8.9-8.9s-4-8.9-8.9-8.9-8.9 4-8.9 8.9c.1 4.9 4 8.9 8.9 8.9z"
								  stroke-width="1.769" stroke="#005893"></path>
							<path d="M190.7 10.3h-4.9V5.4h-1.3v6.2h6.2v-1.3z" fill="#005893"></path>
						</g>
					</svg></span>
					
					</div>
					<div class="card-body">
						' . Text::_('PLG_WT_JSHOPPING_B24_PRO_DESC2') . '
					</div>
					<div class="card-footer ps-4">
						' . $webhook_info . '
					</div>

				</div>
			</div>
		</div><div>
		';

	}

	/**
	 * Method to get the field title.
	 *
	 * @return  string  The field title.
	 *
	 * @since   1.7.0
	 */
	protected function getTitle()
	{
		return $this->getLabel();
	}

}
