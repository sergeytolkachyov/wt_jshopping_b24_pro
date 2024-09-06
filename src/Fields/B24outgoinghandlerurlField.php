<?php
/**
 * @package       WT JShopping Bitrix 24 PRO
 * @version     3.2.0
 * @Author 		Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2021 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since 		2.5.0
 */
namespace Joomla\Plugin\System\Wt_jshopping_b24_pro\Fields;
defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\SpacerField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

FormHelper::loadFieldClass('spacer');
class B24outgoinghandlerurlField extends SpacerField
{

	protected $type = 'B24outgoinghandlerurl';

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
		$this_site_token = md5(Uri::root());
		$b24_outgoing_handler_url = Uri::root()."?option=com_ajax&plugin=wt_jshopping_b24_pro&group=system&format=raw&token=".$this_site_token;
		return "<code>".$b24_outgoing_handler_url."</code>";
	}

	/**
	 * @return  string  The field label markup.
	 *
	 * @since   1.7.0
	 */
	protected function getLabel()
	{
		return Text::_("PLG_WT_JSHOPPING_B24_PRO_B24_OUTGOING_HANDLER_URL");
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
