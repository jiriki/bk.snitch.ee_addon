<?php

/**
 * Snitch
 *
 * This extension enables you to send email notifications on create, edit, and delete
 *
 * @package   Snitch
 * @author    Brandon Kelly <me@brandon-kelly.com>
 * @link      http://brandon-kelly.com/apps/snitch
 * @copyright Copyright (c) 2008-2009 Brandon Kelly
 * @license   http://creativecommons.org/licenses/by-sa/3.0/   Attribution-Share Alike 3.0 Unported
 * --
 * Update concept for being able to choose which weblogs snitch will report on.  Added string field
 * for user to enter the short_name of weblogs(s), seperated by comma's and Snitch would only execute
 * the send notification IF edit/add/delete occured in a weblog entered.
 *
 * Also verified force auto-update to be disabled.
 *
 * Currently 9/22/09 it doesn't work.  Settings page is setup and successfull enabling, BUT on update/add/delete,
 * user recieves Server 500.  So I'm guessing logic is broke, probably in how I'm messing with arrays.
 */
class Snitch
{
	/**
	 * Extension Settings
	 *
	 * @var array
	 */
	var $settings		= array();
	
	/**
	 * Extension Name
	 *
	 * @var string
	 */
	var $name			= 'Snitch';
	
	/**
	 * Extension Class Name
	 *
	 * @var string
	 */
	var $class_name		= 'Snitch';
	
	/**
	 * Extension Version
	 *
	 * @var string
	 * --
	 * increased version number by 0.0.1
	 */
	var $version		= '1.1.1';
	
	/**
	 * Extension Description
	 *
	 * @var string
	 */
	var $description	= 'Send email notifications on create, edit, and delete';
	
	/**
	 * Extension Settings Exist
	 *
	 * If set to 'y', a settings page will be shown in the Extensions Manager
	 *
	 * @var string
	 */
	var $settings_exist	= 'y';
	
	/**
	 * Documentation URL
	 *
	 * @var string
	 */
	var $docs_url		= 'http://brandon-kelly.com/apps/snitch?utm_campaign=snitch_em';
	
	
	
	/**
	 * Extension Constructor
	 *
	 * @param array   $settings
	 * @since version 1.0.0
	 */
	function Snitch($settings=array())
	{
		$this->settings = $this->get_site_settings($settings);
	}
	
	
	/**
	 * Get All Settings
	 *
	 * @return array   All extension settings
	 * @since  version 1.1.0
	 */
	function get_all_settings()
	{
		global $DB;

		$query = $DB->query("SELECT settings
		                     FROM exp_extensions
		                     WHERE class = '{$this->class_name}'
		                       AND settings != ''
		                     LIMIT 1");

		return $query->num_rows
			? unserialize($query->row['settings'])
			: array();
	}



	/**
	 * Get Default Settings
	 * 
	 * @return array   Default settings for site
	 * @since 1.1.0
	 * ----
	 * added - 'weblog_picks' value, set to empty string
	 */
	function get_default_settings()
	{
		$settings = array(
			'notify_on_create'   => 'y',
			'notify_on_update'   => 'y',
			'notify_on_delete'   => 'y',
			'skip_self'          => 'n',
			'email_tit_template' => 'Entry {action}: {entry_title}',
			'email_msg_template' => "Weblog:  {weblog_name}\n"
			                      . "Title:  {entry_title}\n"
			                      . "ID:  {entry_id}\n"
			                      . "Status:  {entry_status}\n"
			                      . "Performed by:  {name}\n"
			                      . "E-mail:  {email}\n\n"
			                      . "URL:  {url}",
			
			'check_for_extension_updates' => 'y',
			'weblog_picks' => '',
		);

		return $settings;
	}



	/**
	 * Get Site Settings
	 *
	 * @param  array   $settings   Current extension settings (not site-specific)
	 * @return array               Site-specific extension settings
	 * @since  version 1.1.0
	 */
	function get_site_settings($settings=array())
	{
		global $PREFS;
		
		$site_settings = $this->get_default_settings();
		
		$site_id = $PREFS->ini('site_id');
		if (isset($settings[$site_id]))
		{
			$site_settings = array_merge($site_settings, $settings[$site_id]);
		}

		return $site_settings;
	}
	
	
	
	/**
	 * Settings Form
	 *
	 * Construct the custom settings form.
	 *
	 * @param  array   $current   Current extension settings (not site-specific)
	 * @see    http://expressionengine.com/docs/development/extensions.html#settings
	 * @since  version 1.1.0
	 */
	function settings_form($current)
	{
	    $current = $this->get_site_settings($current);

	    global $DB, $DSP, $LANG, $IN, $PREFS;

		// Breadcrumbs

		$DSP->crumbline = TRUE;

		$DSP->title = $LANG->line('extension_settings');
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities'))
		            . $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager', $LANG->line('extensions_manager')))
		            . $DSP->crumb_item($this->name);

	    $DSP->right_crumb($LANG->line('disable_extension'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_extension_confirm'.AMP.'which=disable'.AMP.'name='.$IN->GBL('name'));

		// Donations button

		$DSP->body = '';
		
		// Donations button
	    $DSP->body .= '<div style="float:right;">'
	                . '<a style="display:block; margin:-2px 10px 0 0; padding:5px 0 5px 70px; width:190px; height:15px; font-size:12px; line-height:15px;'
	                . ' background:url(http://brandon-kelly.com/images/shared/donations.png) no-repeat 0 0; color:#000; font-weight:bold;"'
	                . ' href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2181794" target="_blank">'
	                . $LANG->line('donate')
	                . '</a>'
	                . '</div>'

		// Form header

		           . "<h1>{$this->name} <small>{$this->version}</small> Beta - Weblog Selection</h1>"

		           . $DSP->form_open(
		                                 array(
		                                     'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings',
		                                     'name'   => 'settings_example',
		                                     'id'     => 'settings_example'
		                                 ),
		                                 array(
		                                     'name' => strtolower($this->class_name)
		                                 )
		                             )

		// Notifications

		           . $DSP->table_open(
		                                  array(
		                                      'class'  => 'tableBorder',
		                                      'border' => '0',
		                                      'style'  => 'margin-top:18px; width:100%'
		                                  )
		                              )

		           . $DSP->tr()
		           . $DSP->td('tableHeading', '', '2')
		           . $LANG->line('notify_title')
		           . $DSP->td_c()
		           . $DSP->tr_c()

		           . $DSP->tr()
		           . $DSP->td('', '', '2')
		           . '<div class="box" style="border-width:0 0 1px 0; margin:0; padding:10px 5px"><p>'.$LANG->line('notify_info').'</p></div>'
		           . $DSP->td_c()
		           . $DSP->tr_c()

		             // notify_on_create
		           . $DSP->tr()
		           . '<td class="tableCellOne" style="width:60%; padding-top:8px; vertical-align:top;">'
		           . $DSP->qdiv('defaultBold', $LANG->line('notify_on_create_label'))
		           . $DSP->td_c()
		           . $DSP->td('tableCellOne')
		           . $DSP->input_select_header('notify_on_create')
		           . $DSP->input_select_option('y', $LANG->line('yes'), ($current['notify_on_create'] == 'y' ? 'y' : ''))
		           . $DSP->input_select_option('n', $LANG->line('no'),  ($current['notify_on_create'] != 'y' ? 'y' : ''))
		           . $DSP->input_select_footer()
		           . $DSP->td_c()
		           . $DSP->tr_c()
		           
		             // notify_on_update
		           . $DSP->tr()
		           . '<td class="tableCellTwo" style="width:60%; padding-top:8px; vertical-align:top;">'
		           . $DSP->qdiv('defaultBold', $LANG->line('notify_on_update_label'))
		           . $DSP->td_c()
		           . $DSP->td('tableCellTwo')
		           . $DSP->input_select_header('notify_on_update')
		           . $DSP->input_select_option('y', $LANG->line('yes'), ($current['notify_on_update'] == 'y' ? 'y' : ''))
		           . $DSP->input_select_option('n', $LANG->line('no'),  ($current['notify_on_update'] != 'y' ? 'y' : ''))
		           . $DSP->input_select_footer()
		           . $DSP->td_c()
		           . $DSP->tr_c()
		           
		             // notify_on_delete
		           . $DSP->tr()
		           . '<td class="tableCellOne" style="width:60%; padding-top:8px; vertical-align:top;">'
		           . $DSP->qdiv('defaultBold', $LANG->line('notify_on_delete_label'))
		           . $DSP->td_c()
		           . $DSP->td('tableCellOne')
		           . $DSP->input_select_header('notify_on_delete')
		           . $DSP->input_select_option('y', $LANG->line('yes'), ($current['notify_on_delete'] == 'y' ? 'y' : ''))
		           . $DSP->input_select_option('n', $LANG->line('no'),  ($current['notify_on_delete'] != 'y' ? 'y' : ''))
		           . $DSP->input_select_footer()
		           . $DSP->td_c()
		           . $DSP->tr_c()
		           
		             // skip_self
		           . $DSP->tr()
		           . $DSP->td('', '', '2')
		           . '<div class="box" style="border-width:0 0 1px 0; margin:0; padding:10px 5px"><p>'.$LANG->line('skip_self_info').'</p></div>'
		           . $DSP->td_c()
		           . $DSP->tr_c()
		           
		           . $DSP->tr()
		           . '<td class="tableCellTwo" style="width:60%; padding-top:8px; vertical-align:top;">'
		           . $DSP->qdiv('defaultBold', $LANG->line('skip_self_label'))
		           . $DSP->td_c()
		           . $DSP->td('tableCellTwo')
		           . $DSP->input_select_header('skip_self')
		           . $DSP->input_select_option('y', $LANG->line('yes'), ($current['skip_self'] == 'y' ? 'y' : ''))
		           . $DSP->input_select_option('n', $LANG->line('no'),  ($current['skip_self'] != 'y' ? 'y' : ''))
		           . $DSP->input_select_footer()
		           . $DSP->td_c()
		           . $DSP->tr_c()

		           . $DSP->table_c()

// >>>>>>>>>>>>>> ADDING WEBLOG PICKER
		// Weblog Selection

		           . $DSP->table_open(%