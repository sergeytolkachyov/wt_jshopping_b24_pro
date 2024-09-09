<?php
/**
 * @package       WT JShopping Bitrix 24 PRO
 * @version       3.2.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2020 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since         3.2.0
 */

namespace Joomla\Plugin\System\Wt_jshopping_b24_pro\Fields;

\defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Form\Field\NoteField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

class PreprocessingfileslistField extends NoteField
{

	protected $type = 'Preprocessingfileslist';

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
		$preprocess_folder = JPATH_SITE . '/plugins/system/wt_jshopping_b24_pro/src/Custompreprocess';
		$files = Folder::files($preprocess_folder,'.php');
		$html = '<h3>'.Text::_('PLG_WT_JSHOPPING_B24_PRO_ENABLE_CUSTOM_PREPROCESSINGFILESLIST').'</h3><p>'.Text::sprintf('PLG_WT_JSHOPPING_B24_PRO_ENABLE_CUSTOM_PREPROCESSINGFILESLIST_DESC', $preprocess_folder).'</p>';
		$html .= '<table class="table table-hover">
			<thead>
				<tr>
				<th>'.Text::_('PLG_WT_JSHOPPING_B24_PRO_ENABLE_CUSTOM_PREPROCESSINGFILESLIST_TABLE_FILE').'</th>
				<th>'.Text::_('PLG_WT_JSHOPPING_B24_PRO_ENABLE_CUSTOM_PREPROCESSINGFILESLIST_TABLE_FILE_DATE_MODIFIED').'</th>
				</tr>
			</thead>
			<tbody>';
		if($files)
		{
			foreach($files as $file)
			{
				$html .= '<tr><td>'.$file.'</td><td>'.HTMLHelper::date(filemtime($preprocess_folder.'/'.$file), 'DATE_FORMAT_LC6').'</td></tr>';
			}
		}
		$html .= '</tbody></table>';

		return $html;
	}

	/**
	 * @return  string  The field label markup.
	 *
	 * @since   1.7.0
	 */
	protected function getLabel()
	{
		return ' ';
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
