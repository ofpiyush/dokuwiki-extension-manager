<?php
/**
 * Extension plugin - english language file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Christopher Smith <chris@jalakai.co.uk>
 */

// admin screen meny text
$lang['menu'] = 'Extension Manager';

// java script language strings
$lang['js']['confirm_del']      = 'Are you sure you want to delete this?';

// language strings for the extension plugin
$lang['tab_plugin']             = 'Plugins';
$lang['tab_template']           = 'Templates';
$lang['tab_search']             = 'Search & Install';
$lang['summary_plugin']         = 'You have %d plugins installed, %d enabled';
$lang['summary_template']       = 'You have %d templates installed';
$lang['summary_search']         = 'DokuWiki repository contains a total of %d extensions!';
// search box
$lang['search_plugin']          = 'Search among plugins at %s';
$lang['search_template']        = 'Search among templates at %s';
$lang['search_extension']       = 'Search among both plugins and templates at %s';

$lang['repo_reload']            = 'The search list auto-updates every %d days old';
$lang['btn_reload']             = 'Reload';

// download url box
$lang['urldownload_text']       = 'Install by URL';

// plugin tab headings
$lang['header_plugin_installed']  = 'Installed Plugins';
$lang['header_plugin_protected']  = 'Protected Plugins';
$lang['text_plugin_protected']    = 'These plugins are protected and can not be disabled and/or deleted from the plugin and template manager.';

// template tab headings
$lang['header_template_installed']= 'Installed Templates';

// search tab headings
$lang['header_search_results']  = 'Search results for "%s"';
$lang['not_found']              = 'The term "%s" was not found';
$lang['no_result']              = 'Please try with a simpler query or <a href="%s" title="%s" />browse all plugins</a>';
$lang['browse']                 = 'Browse all plugins';

// extension list
$lang['tpl_search']             = 'Search for a new Template';
$lang['btn_info']               = 'Info';
$lang['btn_update']             = 'Update';
$lang['btn_delete']             = 'Delete';
$lang['enable']                 = 'Enable';
//$lang['btn_go']                 = "Go"; not necessary if using buttons
$lang['btn_disable']            = 'Disable';
$lang['btn_settings']           = 'Settings';
$lang['btn_download']           = 'Download';
$lang['btn_reinstall']          = 'Re-install';
$lang['btn_disdown']            = 'Download as Disabled';
$lang['btn_dependown']          = 'Download with dependencies';
$lang['btn_enable']             = 'Save';
$lang['select_all']             = 'Select All';
$lang['select_none']            = 'Select None';
//$lang['please_choose']          = '-Please Choose-'; not necessary if using buttons
$lang['bundled']                = 'bundled';


$lang['homepage_link']          = '%s documentation';
$lang['installed']              = 'Installed:';
$lang['lastupdate']             = 'Last updated:';
$lang['source']                 = 'Source:';
$lang['unknown']                = '<em>unknown</em>';
$lang['installed_version']      = 'Installed version:';
$lang['install_date']           = 'Installed on:';
$lang['available_version']      = 'Available version:';
$lang['depends']                = 'Depends on:';
$lang['similar']                = 'Similar to:';
$lang['conflicts']              = 'Conflicts with:';

// ..ing = header message
// ..ed = success message


$lang['updating']               = 'Updating ...';
$lang['updated']                = 'Plugin %s updated successfully';
$lang['tempupdated']            = 'Template %s successfully updated';
$lang['reinstalled']            = 'Plugin %s re-installed successfully';
$lang['tempreinstalled']        = 'Template %s re-installed successfully';
$lang['updates']                = 'The following plugins have been updated successfully';
$lang['update_none']            = 'No updates found.';
$lang['already_installed']      = 'Already installed';
$lang['deleting']               = 'Deleting ...';
$lang['deleted']                = 'Plugin %s deleted.';
$lang['template_deleted']       = 'Template %s deleted';

$lang['downloading']            = 'Downloading ...';
$lang['downloaded']             = 'Plugin %s installed successfully';
$lang['tempdownloaded']         = "Template %s installed successfully";
$lang['downloads']              = 'The following plugins have been installed successfully:';
$lang['download_none']          = 'No plugins found, or there has been an unknown problem during downloading and installing.';
// Notices
$lang['autogen_info']           = "Auto generated and saved info.txt for <em>%s</em>";
//plugin types
$lang['all']                    = 'All';
$lang['syntax']                 = 'Syntax';
$lang['admin']                  = 'Admin';
$lang['action']                 = 'Action';
$lang['render']                 = 'Render';
$lang['helper']                 = 'Helper';

// info titles
$lang['plugin']                 = 'Plugin';
$lang['components']             = 'Components:';
$lang['noinfo']                 = 'This plugin returned no information, it may be invalid.';
$lang['name']                   = 'Name:';
$lang['date']                   = 'Date:';
$lang['type']                   = 'Type:';
$lang['desc']                   = 'Description:';
$lang['author']                 = 'Author:';
$lang['www']                    = 'Web:';
$lang['tags']                   = 'Tags:';
// error messages
$lang['not_writable']           = 'DokuWiki can not write to the folder';
$lang['update_error']           = 'There was an error while updating';
$lang['reinstall_error']        = 'There was an error while re-installing';
$lang['repocache_error']        = "There was an error retrieving the plugin list from the dokuwiki.org server, please force reload later";
$lang['repoxmlformat_error']    = 'Repository XML unformatted';
$lang['security_issue']         = '<strong>Security Issue:</strong> %s';
$lang['security_warning']       = '<strong>Security Warning:</strong> %s';
$lang['update_available']       = '<strong>Update:</strong> New version %s is available.';
$lang['wrong_folder']           = '<strong>Plugin installed incorrectly:</strong> Rename plugin directory "%s" to "%s".';
$lang['url_change']             = '<strong>URL changed:</strong> Download URL has changed since last download.<br />New: %s<br />Old: %s';
$lang['no_url']                 = 'No download URL';
$lang['no_manager']             = 'Could not find manager.dat file';
$lang['error']                  = 'An unknown error occurred';
$lang['error_download']         = 'Unable to download the plugin file: %s';
$lang['error_badurl']           = 'Suspect bad url - unable to determine file name from the url';
$lang['error_dircreate']        = 'Unable to create temporary folder to receive download';
$lang['error_decompress']       = 'The plugin manager was unable to decompress the downloaded file This maybe as a result of a bad download, in which case you should try again; or the compression format may be unknown, in which case you will need to download and install the plugin manually';
$lang['error_copy']             = 'There was a file copy error while attempting to install files for plugin <em>%s</em>: the disk could be full or file access permissions may be incorrect. This may have resulted in a partially installed plugin and leave your wiki installation unstable';
$lang['error_delete']           = 'There was an error while attempting to delete plugin <em>%s</em> The most probably cause is insufficient file or directory access permissions';
$lang['template_error_delete']  = 'Template %s could not be deleted';
$lang['enabled']                = 'Plugin %s enabled';
$lang['notenabled']             = 'Plugin %s could not be enabled, check file permissions';
$lang['disabled']               = 'Plugin %s disabled';
$lang['notdownloaded']          = "<em>%s</em> could not be downloaded";
$lang['notdisabled']            = 'Plugin %s could not be disabled, check file permissions';
$lang['packageinstalled']       = 'Plugin package (%d plugin(s): %s) successfully installed';

//Setup VIM: ex: et ts=4 :
