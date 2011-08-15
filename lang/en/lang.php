<?php
/**
 * english language file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Christopher Smith <chris@jalakai.co.uk>
 */

$lang['menu'] = 'Plugin and Template Manager';

// custom language strings for the plugin
$lang['download']               = "Download and install a new plugin";
$lang['manage']                 = "Installed Plugins";
$lang['protected_head']         = 'Protected Plugins';
$lang['protected_desc']         = 'These plugins are protected and can not be disabled and/or deleted from the plugin and template manager.';
$lang['tpl_search']             = 'Search for a new Template';
$lang['search_plugin']          = 'Search for a new Plugin or Template';
$lang['search_results']         = 'Search results for "%s"';
$lang['tpl_manage']             = 'Installed Templates';
$lang['template']               = 'Template';
$lang['install']                = 'Install';
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
$lang['btn_enable']             = 'Save';
$lang['browse']                 = 'Browse all plugins';
$lang['select_all']             = 'Select All';
$lang['select_none']            = 'Select None';
$lang['confirm_del']            = 'Are you sure you want to delete this?';
//$lang['please_choose']          = '-Please Choose-'; not necessary if using buttons
$lang['bundled']                = 'bundled';

$lang['url']                    = 'URL';

$lang['installed']              = 'Installed:';
$lang['lastupdate']             = 'Last updated:';
$lang['source']                 = 'Source:';
$lang['unknown']                = 'unknown';
$lang['version']                = 'Version:';
$lang['depends']                = 'Depends on';
$lang['similar']                = 'Similar to';
$lang['conflicts']              = 'Conflicts with';

// ..ing = header message
// ..ed = success message


$lang['updating']               = 'Updating ...';
$lang['updated']                = 'Plugin %s updated successfully';
$lang['tempupdated']            = 'Template %s successfully updated';
$lang['reinstalled']            = 'Plugin %s re-installed successfully';
$lang['tempreinstalled']        = 'Template %s re-installed successfully';
$lang['updates']                = 'The following plugins have been updated successfully';
$lang['update_none']            = 'No updates found.';
$lang['update_available']       = '<strong>Newer Version:</strong> <em>%s</em> is available.';
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
$lang['repo_reload']            = 'The search list auto-updates every %d days. But you can, <a href="%s" title ="Reload repository cache">force reload repository cache</a>';
$lang['url_change']             = "The download URL for <strong>%s</strong> has been changed to <br /> %s <br /> from <br/> %s on the DokuWiki server. <br />The new URL will be used next time you update or re-install <strong>%s.</strong><br />Click <em>%s</em> to see the URL again next to <em>%s</em><br />To prevent accidental overwriting, make sure the directory %s is <strong>not</strong> writeable by your web server";
//plugin types
$lang['all']                    = 'All';
$lang['syntax']                 = 'Syntax';
$lang['admin']                  = 'Admin';
$lang['action']                 = 'Action';
$lang['render']                 = 'Render';
$lang['helper']                 = 'Helper';

// info titles
$lang['plugin']                 = 'Plugin:';
$lang['components']             = 'Components';
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
$lang['security_issue']         = 'Security Issue:';
$lang['security_warning']       = 'Security Warning:';
$lang['no_result']              = 'Please try with a simpler query or <a href="%s" title="%s" />browse all plugins</a>';
$lang['not_found']              = 'The term "%s" was not found';
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
