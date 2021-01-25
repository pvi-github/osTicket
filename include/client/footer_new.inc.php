<?php

include INCLUDE_DIR . 'ajax.config.php';
$_api = new ConfigAjaxAPI();
$_config_api = $_api->client(false);

$_showlang = false;
$lang = "";
if (($lang = Internationalization::getCurrentLanguage()) && $lang != 'en_US') { 
    $showlang=true;
}
$_copyright = __('Copyright © ') . date('Y') . " " . Format::htmlchars((string) $ost->company ?: 'osTicket.com') . " - " . __('All rights reserved.');

$_helpdesk_software = __('Helpdesk software');
$_powered_by = __('Powered by');

$_GLOBALS['page_data'] = array_merge($_GLOBALS['page_data'], [
   'config_api' => $_config_api,
    'showlang' => $_showlang,
    'lang' => $lang,
    'copyright' => $_copyright,
    'helpdesk_software' => $_helpdesk_software,
    'powered_by' => $_powered_by
]);
