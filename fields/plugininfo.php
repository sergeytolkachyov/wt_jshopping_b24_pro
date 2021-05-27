<?php
/**
 * @package     WT JoomShopping B24 PRO
 * @version     2.3.0
 * @Author 		Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since 		2.3.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use \Joomla\CMS\Factory;
FormHelper::loadFieldClass('spacer');

class JFormFieldPlugininfo extends JFormFieldSpacer
{

	protected $type = 'plugininfo';

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
		$info="";
		$doc = Factory::getDocument();
		$doc->addStyleDeclaration("
			.wt-b24-plugin-info{
				box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); 
				padding:1rem; 
				margin-bottom: 2rem;
				display:flex;
				
			}
			.plugin-info-img{
			    margin-right:auto;
			    max-width: 100%;
			}
			.webhook-info {
                display:flex;
                flex-direction:column;
                align-itmes:center;
                justify-content:center
                margin-left: auto;
			}
			.webhook-info span {
			    text-align:center;
			}
			.webhook-info img{
			    width:64px;
			    margin: 0 auto;
			   }
		");

		$wtb24_plugin_info = simplexml_load_file(JPATH_SITE."/plugins/system/wt_jshopping_b24_pro/wt_jshopping_b24_pro.xml");

		if(PluginHelper::isEnabled('system', 'wt_jshopping_b24_pro') === true)
		{
			$plugin          = PluginHelper::getPlugin('system', 'wt_jshopping_b24_pro');
			$params          = (!empty($plugin->params) ? json_decode($plugin->params) : '');
			$crm_host        = (!empty($params->crm_host) ? $params->crm_host : '');
			$webhook_secret  = (!empty($params->crm_webhook_secret) ? $params->crm_webhook_secret : '');
			$crm_assigned_id = (!empty($params->crm_assigned) ? $params->crm_assigned : '');

			if (!empty($crm_host) && !empty($webhook_secret) && !empty($crm_assigned_id))
			{
				include_once(JPATH_SITE . "/plugins/system/wt_jshopping_b24_pro/lib/crest.php");

				$resultBitrix24 = CRest::call("profile", ["id" => $crm_assigned_id]);


				if (isset($resultBitrix24["result"]))
				{
					if ($resultBitrix24["result"]["ADMIN"] == 1){
						$is_admin = Text::_('JYES');
					} else{
						$is_admin =	Text::_('JNO');
					}
					$webhook_info = '<div class="webhook-info">
                        <span><img class="img-polaroid" src="'.$resultBitrix24["result"]["PERSONAL_PHOTO"].'"/></span>
                        <p><strong>Assigned:</strong> '.$resultBitrix24["result"]["NAME"].' '.$resultBitrix24["result"]["LAST_NAME"].'</p> 
                        <p><strong>Assigned ID:</strong> '.$resultBitrix24["result"]["ID"].'</p>
                        <p><strong>Is admin:</strong> '.$is_admin.'</p>
                    </div>';

                } elseif (isset($resultBitrix24['error'])){
					JError::raiseWarning('',"<strong>Bitrix 24 Error:</strong> ".$resultBitrix24['error']." ".$resultBitrix24['error_description']);
					$webhook_info = '<div class="alert alert-danger webhook-info"><strong>Bitrix 24 Error</strong> <p>'.$resultBitrix24['error'].'</p><p>'.$resultBitrix24['error_description'].'</p></div>';
				}
			}else{
				$webhook_info = '<div class="alert alert-danger ">Not connected to Bitrix 24</div>';
            }
		}





		?>
		<div class="wt-b24-plugin-info">
            <div class="plugin-info-img">
			    <img class="plugin-info-img" src="../plugins/system/wt_jshopping_b24_pro/img/bitrix24-logo.jpg"/>
            </div>
			<div style="padding: 0px 15px;">
				<span class="label label-success">v.<?php echo $wtb24_plugin_info->version; ?></span>
				<?php echo Text::_("PLG_WT_JSHOPPING_B24_PRO_DESC2"); ?>
			</div>
            <?php echo $webhook_info;?>
		</div>
<?php

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
?>