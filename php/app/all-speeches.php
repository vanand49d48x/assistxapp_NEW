<?php
global $config;
// if disabled by admin
if(!get_option('enable_text_to_speech', 0)) {
    page_not_found();
}

if(isset($current_user['id']))
{
    if (!isset($_GET['page']))
        $page = 1;
    else
        $page = $_GET['page'];

    $limit = 25;

    $orm = ORM::for_table($config['db']['pre'] . 'ai_speeches')
        ->where('user_id', $_SESSION['user']['id'])
        ->order_by_desc('id');

    $total = $orm->count();

    $voices = get_ai_voices();
    $rows = $orm
        ->limit($limit)
        ->offset(($page - 1) * $limit)
        ->find_many();

    $speeches = array();
    foreach ($rows as $row) {
        $speeches[$row['id']]['id'] = $row['id'];
        $speeches[$row['id']]['title'] = $row['title'];
        $speeches[$row['id']]['text'] = strip_tags($row['text']);
        $speeches[$row['id']]['text_short'] = strlimiter(strip_tags($row['text']), 100);
        $speeches[$row['id']]['file_url'] = $config['site_url'] . 'storage/ai_audios/' . $row['file_name'];
        $speeches[$row['id']]['characters'] = $row['characters'];
        $speeches[$row['id']]['date'] = date('d M, Y', strtotime($row['created_at']));
        $speeches[$row['id']]['time'] = date('H:i:s', strtotime($row['created_at']));


        $speeches[$row['id']]['language'] = $voices[$row['language']];
        $speeches[$row['id']]['voice'] = $voices[$row['language']]['voices'][$row['voice_id']];
    }

    $pagging = pagenav($total, $page, $limit, $link['ALL_SPEECHES']);

    $start = date('Y-m-01');
    $end = date_create(date('Y-m-t'))->modify('+1 day')->format('Y-m-d');

    $total_character_used = get_user_option($_SESSION['user']['id'], 'total_text_to_speech_used', 0);

    $membership = get_user_membership_detail($_SESSION['user']['id']);
    $characters_limit = $membership['settings']['ai_text_to_speech_limit'];

    HtmlTemplate::display('all-speeches', array(
        'speeches' => $speeches,
        'pagging' => $pagging,
        'show_paging' => (int)($total > $limit),
        'total_character_used' => $total_character_used,
        'characters_limit' => $characters_limit
    ));
} else {
    headerRedirect($link['LOGIN']);
}