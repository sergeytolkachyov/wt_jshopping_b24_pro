<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Version;

/**
 * Script file of HelloWorld component.
 *
 * The name of this class is dependent on the component being installed.
 * The class name should have the component's name, directly followed by
 * the text InstallerScript (ex:. com_helloWorldInstallerScript).
 *
 * This class will be called by Joomla!'s installer, if specified in your component's
 * manifest file, and is used for custom automation actions in its installation process.
 *
 * In order to use this automation script, you should reference it in your component's
 * manifest file as follows:
 * <scriptfile>script.php</scriptfile>
 *
 * @package     Joomla.Administrator
 * @subpackage  com_helloworld
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
class plgSystemWt_jshopping_b24_proInstallerScript
{
    /**
     * This method is called after a component is installed.
     *
     * @param  \stdClass $installer - Parent object calling this method.
     *
     * @return void
     */
    public function install($installer)
    {

    }

    /**
     * This method is called after a component is uninstalled.
     *
     * @param  \stdClass $installer - Parent object calling this method.
     *
     * @return void
     */
    public function uninstall($installer) 
    {

		
    }

    /**
     * This method is called after a component is updated.
     *
     * @param  \stdClass $installer - Parent object calling object.
     *
     * @return void
     */
    public function update($installer) 
    {

    }

    /**
     * Runs just before any installation action is performed on the component.
     * Verifications and pre-requisites should run in this function.
     *
     * @param  string    $type   - Type of PreFlight action. Possible values are:
     *                           - * install
     *                           - * update
     *                           - * discover_install
     * @param  \stdClass $installer - Parent object calling object.
     *
     * @return void
     */
    public function preflight($type, $installer) 
    {
	    $version = new Version;

	    // only for Joomla 3.x

	    if (version_compare($version->getShortVersion(), '4.0', '<')) {

		    Factory::getApplication()->enqueueMessage('&#128546; <strong>WT JShopping Bitrix 24 PRO</strong> plugin doesn\'t support Joomla versions <span class="alert-link">lower 4</span>. Your Joomla version is <span class="badge badge-important">'.$version->getShortVersion().'</span>','error');
		    return false;

	    }
    }
	


    /**
     * Runs right after any installation action is performed on the component.
     *
     * @param  string    $type   - Type of PostFlight action. Possible values are:
     *                           - * install
     *                           - * update
     *                           - * discover_install
     * @param  \stdClass $installer - Parent object calling object.
     *
     * @return void
     */
    function postflight($type, $installer)
    {
	    $smile = '';
	    if($type != 'uninstall')
	    {
		    $smiles    = ['&#9786;', '&#128512;', '&#128521;', '&#128525;', '&#128526;', '&#128522;', '&#128591;'];
		    $smile_key = array_rand($smiles, 1);
		    $smile     = $smiles[$smile_key];
	    }

	    $element = strtoupper($installer->getElement());
		echo "
		<style>	.thirdpartyintegration {
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
		</style>
		<div class='row bg-white m-3 p-3 shadow-sm border'>
		<div class='col-12 col-lg-8'>
		<h2>".$smile." ".Text::_("PLG_".$element."_AFTER_".strtoupper($type))." <br/>".Text::_("PLG_".$element)."</h2>
		".Text::_("PLG_".$element."_DESC");
		
		
			echo Text::_("PLG_".$element."_WHATS_NEW");


		    $thirdpartyextensions="";
		    if(file_exists(JPATH_SITE."/plugins/jshoppingcheckout/quickorder/quickorder.xml")){
			    $nevigen_quick_order = simplexml_load_file(JPATH_SITE."/plugins/jshoppingcheckout/quickorder/quickorder.xml");
			    if($nevigen_quick_order->author == "Nevigen.com" && $nevigen_quick_order->authorUrl == "https://nevigen.com/"){

				    $thirdpartyextensions .=  "<div class='thirdpartyintegration success'><img class='thirdpartyintegration-logo' src='https://nevigen.com/images/corpstyle/logo.png'/>
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


				    $thirdpartyextensions .=   "<div class='thirdpartyintegration ".$bg_color_css_class."'><img class='thirdpartyintegration-logo' src='https://hika.su/images/favicon.png'/>
								<div class='media-body'><strong>".$radicalform->author."'s</strong> plugin <strong>".Text::_($radicalform->name)." v.".$radicalform->version."</strong> detected. <a href='".$radicalform->authorUrl."' target='_blank'>".$radicalform->authorUrl."</a> <a href='mailto:".$radicalform->authorEmail."' target='_blank'>".$radicalform->authorEmail."</a> <a href='https://hika.su/rasshireniya/radical-form' class='btn btn-small btn-success' target='_blank'>Documentation</a> ".(($radicalform_version_compare == false)? "<br/><span class='label label-important'>Required Radical Form v.".$radicalform_min_version." or higher! Update it, please!</span>" : "")."</div>
							</div>						
						
							";
			    }
		    }

		    if(file_exists(JPATH_SITE."/plugins/jshoppingcheckout/quickorder/quickorder.xml") || file_exists(JPATH_SITE."/plugins/system/radicalform/radicalform.xml")){
			    echo "<h4>Supported third-party extensions was found</h4>".$thirdpartyextensions;

		    }

		echo "</div>
		<div class='col-12 col-lg-4 d-flex flex-column justify-content-start'>
		<img width='200px' src='https://web-tolk.ru/web_tolk_logo_wide.png'>
		<p>Joomla Extensions</p>
		<p class='btn-group'>
			<a class='btn btn-sm btn-outline-primary' href='https://web-tolk.ru' target='_blank'>https://web-tolk.ru</a>
			<a class='btn btn-sm btn-outline-primary' href='mailto:info@web-tolk.ru'><i class='icon-envelope'></i> info@web-tolk.ru</a>
		</p>
		<p><a class='btn btn-info' href='https://t.me/joomlaru' target='_blank'>Joomla Russian Community in Telegram</a></p>
		
		".Text::_("PLG_".$element."_MAYBE_INTERESTING")."
		</div>


		";		
	
    }
}