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
				padding: 3px 5px;
				align-items:center;
			}
			.thirdpartyintegration-logo {
				height:32px;
				float:left; 
				margin-right: 5px;
			}
			
			.thirdpartyintegration.success {
				border: 1px solid #2F6F2F;
				background-color:#dfffdf;
			}
			.thirdpartyintegration.error {
				border: 1px solid #bd362f;
				background-color:#ffdddb;
			}
		");
		if(file_exists(JPATH_SITE."/plugins/jshoppingcheckout/quickorder/quickorder.xml")){
			$nevigen_quick_order = simplexml_load_file(JPATH_SITE."/plugins/jshoppingcheckout/quickorder/quickorder.xml");
			if($nevigen_quick_order->author == "Nevigen.com" && $nevigen_quick_order->authorUrl == "https://nevigen.com/"){

				$info .= "<div class='thirdpartyintegration success'><img class='thirdpartyintegration-logo' src='https://nevigen.com/images/corpstyle/logo.png'/>
							<div class='media-body'><strong>".$nevigen_quick_order->author."'s</strong> plugin <strong>".$nevigen_quick_order->name." v.".$nevigen_quick_order->version."</strong> detected. <a href='".$nevigen_quick_order->authorUrl."' target='_blank'>".$nevigen_quick_order->authorUrl."</a> <a href='mailto:".$nevigen_quick_order->authorEmail."' target='_blank'>".$nevigen_quick_order->authorEmail."</a></div>
						</div>";
			}
		}

		if(file_exists(JPATH_SITE."/plugins/system/radicalform/radicalform.xml")){
			$radicalform = simplexml_load_file(JPATH_SITE."/plugins/system/radicalform/radicalform.xml");
			if($radicalform->author == "Progreccor" && $radicalform->authorUrl == "https://hika.su"){

				$radicalform_min_version = "2.5.1";
				$radicalform_version_compare = version_compare($radicalform_min_version,$radicalform->version,"<=");
				if($radicalform_version_compare  == true){
					$bg_color_css_class = "success";
				}else{
					$bg_color_css_class = "error";
				}


				$info .= "<div class='thirdpartyintegration ".$bg_color_css_class."'><img class='thirdpartyintegration-logo' src='https://hika.su/images/favicon.png'/>
							<div class='media-body'><strong>".$radicalform->author."'s</strong> plugin <strong>".Text::_($radicalform->name)." v.".$radicalform->version."</strong> detected. <a href='".$radicalform->authorUrl."' target='_blank'>".$radicalform->authorUrl."</a> <a href='mailto:".$radicalform->authorEmail."' target='_blank'>".$radicalform->authorEmail."</a> <a href='https://hika.su/rasshireniya/radical-form' class='btn btn-small btn-success' target='_blank'>Documentation</a> ".(($radicalform_version_compare == false)? "<br/><span class='label label-important'>Required Radical Form v.".$radicalform_min_version." or higher! Update it, please!</span>" : "")."</div>
						</div>						
						<hr/>
						<p>Use Bitrix24 fields names for your form fields names. For, example, use <strong>EMAIL[]</strong> or <strong>PHONE[]</strong> for multiple emails or phones in your form. Use <strong>EMAIL</strong> or <strong>PHONE</strong> for a single.</p>
						<p>Use <strong>UF_CRM_</strong> fields to send form data to Bitrix24 custom fields.</p>
						<div class=\"accordion\" id=\"accordion2\">
						  <div class=\"accordion-group\">
						    <div class=\"accordion-heading\">
						      <a class=\"accordion-toggle\" data-toggle=\"collapse\" data-parent=\"#accordion2\" href=\"#collapseOne\">
						        HTML-form example code for Radical Form and WT JoomShopping Bitrix24 PRO
						      </a>
						    </div>
						    <div id=\"collapseOne\" class=\"accordion-body collapse\">
						      <div class=\"accordion-inner\">
						       <h4>Example HTML-form for RadicalForm and WT JoomShopping Bitrix24 PRO</h4>
									<code>
									&lt;form&gt;<br/>
									&lt;input name=\"TITLE\" type=\"text\" class=\"form-control required\" placeholder=\"Lead TITLE here\" /&gt;<br/> 
									&lt;input name=\"NAME\" type=\"text\" class=\"form-control required\" placeholder=\"NAME\" /&gt; <br/>
									&lt;input name=\"SECOND_NAME\" type=\"text\" class=\"form-control required\" placeholder=\"SECOND_NAME\" /&gt;<br/> 
									&lt;input name=\"PHONE[]\" type=\"phone\" class=\"form-control required\" placeholder=\"Phone 1\" /&gt; <br/>
									&lt;input name=\"EMAIL[]\" type=\"email\" class=\"form-control required\" placeholder=\"E-mail 1\" /&gt; <br/>
									&lt;input name=\"EMAIL[]\" type=\"email\" class=\"form-control required\" placeholder=\"E-mail 2\" /&gt; <br/>
									&lt;input name=\"PHONE[]\" type=\"phone\" class=\"form-control required\" placeholder=\"Phone 2\" /&gt; <br/>
									&lt;textarea name=\"COMMENTS\" class=\"form-control required\" placeholder=\"COMMENTS\" /&gt; &lt;/textarea&gt;<br/>
									&lt;input name=\"UF_CRM_0000000000\" type=\"text\" class=\"form-control required\" placeholder=\"SECOND_NAME\" /&gt;<br/>
									&lt;button class=\"btn btn-primary rf-button-send\"&gt;Call me!&lt;/button&gt;<br/>
									&lt;/form&gt;<br/>
									</code>
						      </div>
						    </div>
						  </div>
						</div>					
						
						";
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