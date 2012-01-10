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
$lang['download_disabled']      = 'Download disabled in configuration settings';
$lang['tab_plugin']             = 'Plugins';
$lang['tab_template']           = 'Templates';
$lang['tab_search']             = 'Search & Install';
$lang['updates_available']      = '%d update(s) available';
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
$lang['manual_install']         = 'manual install';

$lang['homepage_link']          = 'Documentation';
$lang['bugs_features']          = 'Bugs and feature requests';
$lang['installed']              = 'Installed:';
$lang['lastupdate']             = 'Last updated:';
$lang['source']                 = 'Source:';
$lang['unknown']                = '<em>unknown</em>';
$lang['installed_version']      = 'Installed version:';
$lang['install_date']           = 'Your last update:';
$lang['available_version']      = 'Available version:';
$lang['depends']                = 'Depends on:';
$lang['similar']                = 'Similar to:';
$lang['conflicts']              = 'Conflicts with:';

$lang['msg_tpl_deleted']        = 'Template %s deleted';
$lang['msg_tpl_notdeleted']     = 'Template %s could not be deleted';
$lang['msg_deleted']            = 'Plugin %s deleted';
$lang['msg_notdeleted']         = 'Plugin %s could not be deleted';

$lang['msg_tpl_enabled']        = 'Template %s enabled';
$lang['msg_tpl_notenabled']     = 'Template %s could not be enabled, check file permissions';
$lang['msg_enabled']            = 'Plugin %s enabled';
$lang['msg_notenabled']         = 'Plugin %s could not be enabled, check file permissions';

$lang['msg_disabled']           = 'Plugin %s disabled';
$lang['msg_notdisabled']        = 'Plugin %s could not be disabled, check file permissions';

$lang['msg_url_failed']         = 'URL [%s] could not be downloaded.<br /> %s';
$lang['msg_download_failed']    = 'Plugin %s could not be downloaded.<br /> %s';
$lang['msg_download_success']   = 'Plugin %s installed successfully';
$lang['msg_tpl_download_failed']  = 'Template %s could not be downloaded.<br /> %s';
$lang['msg_tpl_download_success'] = 'Template %s installed successfully';
$lang['msg_download_pkg_success']     = '%s extension package successfully installed (%s)';
$lang['msg_tpl_download_pkg_success'] = '%s extension package successfully installed (%s)';

$lang['msg_update_success']     = 'Plugin %s successfully updated';
$lang['msg_update_failed']      = 'Update of plugin %s failed.<br /> %s';
$lang['msg_tpl_update_success'] = 'Template %s successfully updated';
$lang['msg_tpl_update_failed']  = 'Update of template %s failed.<br /> %s';
$lang['msg_update_pkg_success']     = '%s extension package successfully updated (%s)';
$lang['msg_tpl_update_pkg_success'] = '%s extension package successfully updated (%s)';

$lang['msg_reinstall_success']  = 'Plugin %s re-installed successfully';
$lang['msg_reinstall_failed']   = 'Failed to re-install plugin %s.<br /> %s';
$lang['msg_tpl_reinstall_success'] = 'Template %s re-installed successfully';
$lang['msg_tpl_reinstall_failed']  = 'Failed to re-install template %s.<br /> %s';
$lang['msg_reinstall_pkg_success']     = '%s extension package successfully reinstalled (%s)';
$lang['msg_tpl_reinstall_pkg_success'] = '%s extension package successfully reinstalled (%s)';

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
$lang['already_installed']      = 'Already installed';
$lang['not_writable']           = 'DokuWiki can not write to the folder';
$lang['repocache_error']        = "There was an error retrieving the plugin list from the dokuwiki.org server, please force reload later";
$lang['repoxmlformat_error']    = 'Repository XML unformatted';
$lang['security_issue']         = '<strong>Security Issue:</strong> %s';
$lang['security_warning']       = '<strong>Security Warning:</strong> %s';
$lang['update_available']       = '<strong>Update:</strong> New version %s is available.';
$lang['wrong_folder']           = '<strong>Plugin installed incorrectly:</strong> Rename plugin directory "%s" to "%s".';
$lang['url_change']             = '<strong>URL changed:</strong> Download URL has changed since last download.<br />New: %s<br />Old: %s';
$lang['gitmanaged']             = 'Git source control';
$lang['bundled_source']         = 'Bundled with DokuWiki source';
$lang['no_url']                 = 'No download URL';
$lang['no_manager']             = 'Could not find manager.dat file';

$lang['error_badurl']           = 'URL ends with slash - unable to determine file name from the url';
$lang['error_dircreate']        = 'Unable to create temporary folder to receive download';
$lang['error_download']         = 'Unable to download the file: %s';
$lang['error_decompress']       = 'Unable to decompress the downloaded file. This maybe as a result of a bad download, in which case you should try again; or the compression format may be unknown, in which case you will need to download and install manually';
$lang['error_findfolder']       = 'Unable to identify extension directory, you need to download and install manually';
$lang['error_copy']             = 'There was a file copy error while attempting to install files for directory <em>%s</em>: the disk could be full or file access permissions may be incorrect. This may have resulted in a partially installed plugin and leave your wiki installation unstable';

//Setup VIM: ex: et ts=4 :
