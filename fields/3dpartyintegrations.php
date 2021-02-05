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
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Factory;
FormHelper::loadFieldClass('spacer');
class JFormField3dpartyintegrations extends JFormFieldSpacer
{

	protected $type = '3dpartyintegrations';

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
			.thirdpartyintegration {
				display:flex;
				border: 1px solid #2F6F2F;
				background-color:#dfffdf;
				padding: 3px 5px;
				align-items:center;
			}
			.thirdpartyintegration-logo {
				height:32px;
				float:left; 
				margin-right: 5px;
			}
		");
		if(file_exists(JPATH_SITE."/plugins/jshoppingcheckout/quickorder/quickorder.xml")){
			$nevigen_quick_order = simplexml_load_file(JPATH_SITE."/plugins/jshoppingcheckout/quickorder/quickorder.xml");
			if($nevigen_quick_order->author == "Nevigen.com" && $nevigen_quick_order->authorUrl == "https://nevigen.com/"){

				$info = "<div class='thirdpartyintegration'><img class='thirdpartyintegration-logo' src='https://nevigen.com/images/corpstyle/logo.png'/>
					<div class='media-body'><strong>".$nevigen_quick_order->author."'s</strong> plugin <strong>".$nevigen_quick_order->name." v.".$nevigen_quick_order->version."</strong> detected. <a href='".$nevigen_quick_order->authorUrl."' target='_blank'>".$nevigen_quick_order->authorUrl."</a> <a href='mailto:".$nevigen_quick_order->authorEmail."' target='_blank'>".$nevigen_quick_order->authorEmail."</a></div>
				</div>";
			}
		}
		return $info;
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

	/**
	 * Method to get a control group with label and input.
	 *
	 * @param   array  $options  Options to be passed into the rendering of the field
	 *
	 * @return  string  A string containing the html for the control group
	 *
	 * @since   3.7.3
	 */
	public function renderField($options = array())
	{
		$options['class'] = empty($options['class']) ? 'field-spacer' : $options['class'] . ' field-spacer';

		return parent::renderField($options);
	}
}
?>