<?php

/* 
 * from header.inc.php
 */
$title=($cfg && is_object($cfg) && $cfg->getTitle())
    ? $cfg->getTitle() : 'osTicket :: '.__('Support Ticket System');
$signin_url = ROOT_PATH . "login.php"
    . ($thisclient ? "?e=".urlencode($thisclient->getEmail()) : "");
$signout_url = ROOT_PATH . "logout.php?auth=".$ost->getLinkToken();

header("Content-Type: text/html; charset=UTF-8");
header("Content-Security-Policy: frame-ancestors ".$cfg->getAllowIframes().";");

if (($lang = Internationalization::getCurrentLanguage())) {
    $langs = array_unique(array($lang, $cfg->getPrimaryLanguage()));
    $langs = Internationalization::rfc1766($langs);
    header("Content-Language: ".implode(', ', $langs));
}

$_GLOBALS['page_data'] = array_merge($_GLOBALS['page_data'], [
    'title' => $title,
    
    'root_dir' => ROOT_DIR,
    'root_path' => ROOT_PATH,
    'asset_path' => ASSETS_PATH,

    'logo_title' => __('Support Center'),
    'logo_path' => ROOT_PATH.'logo.php',
    'site_title' => $ost->getConfig()->getTitle(),

]);

/*
 * Bar message
 */
$_bar_message=false;
$_bar_message_class="";
$_bar_message_text="";
if($ost->getError()) {
    $_bar_message=true;
    $_bar_message_class="error_bar";
    $_bar_message_text=$ost->getError();
} elseif($ost->getWarning()) {
    $_bar_message=true;
    $_bar_message_class="warning_bar";
    $_bar_message_text=$ost->$ost->getWarning();
} elseif($ost->getNotice()) {
    $_bar_message=true;
    $_bar_message_class="notice_bar";
    $_bar_message_text=$ost->$ost->$ost->getNotice();
}

$_GLOBALS['page_data'] = array_merge($_GLOBALS['page_data'], [
	'bar_message' => $_bar_message,
    'bar_message_class' => $_bar_message_class,
    'bar_message_text' => $_bar_message_text,
]);

/*
 * Menu
 */
if ($thisclient && is_object($thisclient) && $thisclient->isValid()
    && !$thisclient->isGuest()) {
    $_sep=false;
    $_user_diplay_name=Format::htmlchars($thisclient->getName());
    $_sep="true";
    $_navigation[]=['path'=>ROOT_PATH.'profile.php', 'label' => __('Profile'), 'sep' => $_sep];
    $_navigation[]=['path'=>ROOT_PATH.'tickets.php', 'label' => sprintf(__('Tickets <b>(%d)</b>'), $thisclient->getNumTickets()), 'sep' => $_sep];
    $_navigation[]=['path'=>$signout_url, 'label' => __('Sign Out'), 'sep' => $_sep];
} elseif($nav) {
    $_sep=false;
    if ($cfg->getClientRegistrationMode() == 'public') { 
        $_navigation[]=['path'=>'', 'label' => __('Guest User'), 'sep' => ""];
        $_sep=true;
    }
    if ($thisclient && $thisclient->isValid() && $thisclient->isGuest()) {
        $_navigation[]=['path'=>$signout_url, 'label' => __('Sign Out'), 'sep' => $_sep];
    }
    elseif ($cfg->getClientRegistrationMode() != 'disabled') {
        $_navigation[]=['path'=>$signin_url, 'label' => __('Sign In'), 'sep' => $_sep];
    }
} 

$_GLOBALS['page_data'] = array_merge($_GLOBALS['page_data'], [

    'user_diplay_name' => $_user_diplay_name,
    'navigation' => $_navigation,
]);

/*
 * Languages & flags
 */
if (($all_langs = Internationalization::getConfiguredSystemLanguages())
    && (count($all_langs) > 1)) {
    $qs = array();
    parse_str($_SERVER['QUERY_STRING'], $qs);
    foreach ($all_langs as $code=>$info) {
        list($lang, $locale) = explode('_', $code);
        $qs['lang'] = $code;
        
        $_languages[]=[
            'class' => 'flag-'.strtolower($info['flag'] ?: $locale ?: $lang),
            'flag' => $locale ?: $info['flag'] ?: $lang,
            'path' => '?'.http_build_query($qs),
            'label' => Internationalization::getLanguageDescription($code)];
    }
}

$_GLOBALS['page_data'] = array_merge($_GLOBALS['page_data'], [
    
    'languages' => $_languages,
]);

/*
 * Navigation menu
 */
$_show_menu=false;
if($nav){
    if($nav && ($navs=$nav->getNavLinks()) && is_array($navs)){
        $_show_menu=true;
        foreach($navs as $name =>$nav) {
            $_menu[]=[
            'class1' => $nav['active']?'active':'',
            'class2' => $name,
            'path' => (ROOT_PATH.$nav['href']),
            'label' => $nav['desc'],"\n"];
        }
    }
}

$_GLOBALS['page_data'] = array_merge($_GLOBALS['page_data'], [
    
    'show_menu' => $_show_menu,
    'menu' => $_menu,
]);

/*
 * Error messages
 */
$_show_msg=false;
if($errors['err']) {
    $_show_msg=true;
    $_msg_id="msg_error";
    $_msg=$errors['err'];
}elseif($msg) {
    $_show_msg=true;
    $_msg_id="msg_notice";
    $_msg=$msg;
}elseif($warn) {
	$_show_msg=true;
    $_msg_id="msg_warning";
    $_msg= $warn;
}

$_GLOBALS['page_data'] = array_merge($_GLOBALS['page_data'], [
        
    'show_msg' => $_show_msg,
    'msg_id' => $_msg_id,
    'msg' => $_msg,
]);

/*
 * End From header.inc.php
 */
 
