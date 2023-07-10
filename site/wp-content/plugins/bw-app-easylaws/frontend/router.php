<?php
    $action = trim(strtolower(get_query_var('action')));
    switch($action){
        case 'home':
            include(__DIR__.'/theme/templates/dashboard.php');
        break;
        case 'search':
            include(__DIR__.'/theme/templates/search.php');
        break;
        case 'question':
        case 'q':
            include(__DIR__.'/theme/templates/question.php');
        break;
        case 'subjects':
            include(__DIR__.'/theme/templates/subjects.php');
        break;
        case 'subject':
            include(__DIR__.'/theme/templates/subject.php');
        break;
        case 'tags':
            include(__DIR__.'/theme/templates/tags.php');
        break;
        case 'tag':
            include(__DIR__.'/theme/templates/tag.php');
        break;
        case 'contact':
            include(__DIR__.'/theme/templates/contact.php');
        break;
        case 'request':
            include(__DIR__.'/theme/templates/request.php');
        break;
        case 'profile':
            include(__DIR__.'/theme/templates/profile.php');
        break;
        case 'favorites':
            include(__DIR__.'/theme/templates/favorites.php');
        break;
        case 'history':
            include(__DIR__.'/theme/templates/history.php');
        break;
        case 'logout':
            wapi()->logout();
            wp_redirect( site_url() );
            exit;
        break;
    }
