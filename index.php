<?php
/*********************************************************************
    index.php

    Helpdesk landing page. Please customize it to fit your needs.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('client.inc.php');

require_once INCLUDE_DIR . 'class.page.php';

/*
 * Maybe put this into client.inc.php ? 
 */

require_once ROOT_DIR.'vendor/autoload.php';

include_once ROOT_DIR.'include/ost-config.php';

if ( defined('THEME')) {
    $_GLOBALS['theme']=THEME;
} else {
    $_GLOBALS['theme']='legacy';
}
    
if (! is_dir ( ROOT_DIR.'themes/'.$_GLOBALS['theme'].'/templates' )) {
    $_GLOBALS['theme']='legacy';
}

$_GLOBALS['loader'] = new \Twig\Loader\FilesystemLoader(ROOT_DIR.'themes/'.$_GLOBALS['theme'].'/templates');

$_GLOBALS['twig'] = new \Twig\Environment($_GLOBALS['loader'], [
    'cache' => ROOT_DIR.'data/cache/compilation_cache',
    'auto_reload' => true
]);

// the data to pass to twig 
$_GLOBALS['page_data'] = Array();

// filter for proper indentation of html output
$filter = new \Twig\TwigFilter('indent', function ($string, $number) {
    $spaces = str_repeat(' ', $number);
    return rtrim(preg_replace('#^(.+)$#m', sprintf('%1$s$1', $spaces), $string));
}, array('is_safe' => array('all')));

$_GLOBALS['twig']->addFilter($filter);

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
 
 
 /*
  * From footer.inc.php
  */
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


/*
 * End From footer.inc.php
 */
 
/*
 * Page content
 */

// Side bar buttons
$BUTTONS = isset($BUTTONS) ? $BUTTONS : true;
$_show_sidebar_button_open_ticket=false;
$_show_sidebar_buttons=false;
if ($BUTTONS) {
    $_show_sidebar_buttons=true;
    $_sidebar_button_open_ticket_label = __('Open a New Ticket');
    $_sidebar_button_check_ticket_label = __('Check Ticket Status');

    if ($cfg->getClientRegistrationMode() != 'disabled'
        || !$cfg->isClientLoginRequired()) {
            $_show_sidebar_button_open_ticket=true;
    }
} 

// Side bar content : featured questions
$_show_sidebar_featured_questions=false;
if ($cfg->isKnowledgebaseEnabled()
    && ($faqs = FAQ::getFeatured()->select_related('category')->limit(5))
    && $faqs->all()) {
    unset($_sidebar_featured_questions);
    $_show_sidebar_featured_questions=true;
    foreach ($faqs as $F) {
        $_sidebar_featured_questions[]=[
            'path' => ROOT_PATH."kb/faq.php?id=".urlencode($F->getId()),
            'question' => $F->getLocalQuestion()
        ];
    }
    $_sidebar_featured_questions_lang = __('Featured Questions');
}

// Side bar content : resources
$_show_sidebar_other_resources = false;
$resources = Page::getActivePages()->filter(array('type'=>'other'));
if ($resources->all()) {
    foreach ($resources as $page) {
        $_sidebar_other_resources[]=[
            'path' => ROOT_PATH."pages/".$page->getNameAsSlug(),
            'resource' => $page->getLocalName()
        ];
    }
    $_sidebar_other_resources_lang = __('Other Resources');
    $_show_sidebar_other_resources = true;

}

$_GLOBALS['page_data'] = array_merge($_GLOBALS['page_data'], [
        
    'show_sidebar_button_open_ticket' => $_show_sidebar_button_open_ticket,
    'show_sidebar_buttons' => $_show_sidebar_buttons,
    'sidebar_button_open_ticket_label' => $_sidebar_button_open_ticket_label,
    'sidebar_button_check_ticket_label' => $_sidebar_button_check_ticket_label,
    
    'show_sidebar_featured_questions' => $_show_sidebar_featured_questions,
    'sidebar_featured_questions_lang' => $_sidebar_featured_questions_lang,
    'sidebar_featured_questions' => $_sidebar_featured_questions,
    
    'show_sidebar_other_resources' => $_show_sidebar_other_resources,
    'sidebar_other_resources_lang' => $_sidebar_other_resources_lang,
    'sidebar_other_resources' => $_sidebar_other_resources,

]);

// Search form
$_show_search=false;
if ($cfg && $cfg->isKnowledgebaseEnabled()) {
    $_show_search=true;
    $_search_action="kb/faq.php";
    $_search_placeholder=__('Search our knowledge base');
    $_search_by_lang=__('Search');
}

$_GLOBALS['page_data'] = array_merge($_GLOBALS['page_data'], [
    
    'show_search' => $_show_search,
    'search_action' => $_search_action,
    'search_placeholder' => $_search_placeholder,
    'search_by_lang' => $_search_by_lang
]);

// Landing page content
if($cfg && ($page = $cfg->getLandingPage())) {
    $_landing_content = $page->getBodyWithImages();
} else {
    // FIXME : Remove h1 which is hardcoded there
    $_landing_content = '<h1>'.__('Welcome to the Support Center').'</h1>';
}

$_GLOBALS['page_data'] = array_merge($_GLOBALS['page_data'], [

    'landing_content' => $_landing_content
]);

// Knowledge base
$_show_kb=false;
if($cfg && $cfg->isKnowledgebaseEnabled()){
    //FIXME: provide ability to feature or select random FAQs ??
    $cats = Category::getFeatured();
    if ($cats->all()) {
        $_show_kb=true;
        $_kb_title=__('Featured Knowledge Base Articles');
        foreach ($cats as $C) {
            unset($_kb_articles);        
            foreach ($C->getTopArticles() as $F) {
                $_kb_articles[]=[
                    'path' => ROOT_PATH.'kb/faq.php?id='.$F->getId(),
                    'label' => $F->getQuestion(),
                    'teaser' => $F->getTeaser()
                ];
            }
            $_kb_content[]=[
                'name' => $C->getName(),
                'articles' => $_kb_articles
			];
        }
    }
}

$_GLOBALS['page_data'] = array_merge($_GLOBALS['page_data'], [

    'show_kb' => $_show_kb,
    'kb_title' => $_kb_title,
    'kb_content' => $_kb_content
    
]);

/*
 * End Page content
 */


/*
 * Render page with Twig
 */

echo $_GLOBALS['twig']->render('index.html', $_GLOBALS['page_data']);
