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

require(CLIENTINC_DIR.'header_new.inc.php');
 
require(CLIENTINC_DIR.'footer_new.inc.php');

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
