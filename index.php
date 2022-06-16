<?php
// why rehike when you already have hitchhiker?
// hahahahaahahhhahaha :))))

ob_start();
$root = $_SERVER['DOCUMENT_ROOT'];
set_include_path($root);

if (isset($_COOKIE['VISITOR_INFO1_LIVE'])) {
    $visitor = $_COOKIE['VISITOR_INFO1_LIVE'];
} else {
    $visitor = 'QRe0LmmEJyY'; // DEBUG
    setcookie("VISITOR_INFO1_LIVE", $visitor);
}

$templateRoot = '/template/hitchhiker';

$yt = (object) [];
$template = '';

include ('boot.php');
include ('defaultExperiments.php');
include ('resourceConstants.php');

// Post boot events
Rehike\ContextManager::$visitorData = $visitor;

// * Set signin state
Rehike\Signin\AuthManager::use($yt);

// differentiates pages
require ('router.php');

// initialises twig
include ('fatalHandler.php');

// lazy spf check
if (isset($_GET['spf'])) {
    $yt->spf = true;
    $__spfState = $_GET['spf'];
    $__spfUrl = preg_replace('/.spf='.$_GET['spf'].'/', '', $_SERVER['REQUEST_URI']);
}

$yt->url = $__spfUrl ?? $_SERVER['REQUEST_URI'];

if (isset($_COOKIE['wide'])) {
    $yt -> theaterMode = $_COOKIE['wide'];
} else {
    $yt -> theaterMode = "0";
    $_COOKIE['wide'] = "0";
}

//$yt->spfEnabled = false; // DEBUG
echo $twig->render($template . '.twig', [$yt]);
$timec = round(microtime(true) * 1000);
//ob_end_clean(); echo $timec - $timeb; die();

use \SpfPhp\SpfPhp;
const SPF_NAV = 'navigate';
const SPF_NB = 'navigate-back';
const SPF_NF = 'navigate-forward';
const SPF_LOAD = 'load';
if (isset($__spfState) && 
    ($__spfState == SPF_NAV ||
     $__spfState == SPF_NB ||
     $__spfState == SPF_NF)
) {
    $yt->spfIdListeners = [
        '@body<class>',
        'player-unavailable<class>',
        'debug',
        'early-body',
        'appbar-content<class>',
        'alerts',
        'content',
        '@page<class>',
        'header',
        'ticker-content',
        'player-playlist<class>',
        '@player<class>'
    ];
    $yt->spfUrl = $__spfUrl;
}
if (isset($yt->spf) && $yt->spf && http_response_code() == 200) { // isset to prevent warning; http_response_code to prevent broken spf 404 page
    $_htmlBuffer = ob_get_clean();
    header('Content-Type: application/json');

    $spfResponse = @SpfPhp::build(
        $_htmlBuffer,
        $yt->spfIdListeners,
        (object) [
            'skipSerialisation' => true
        ]
    );
    if (isset($yt->spfUrl)) $spfResponse->url = $yt->spfUrl;
    if (isset($yt->spfName)) $spfResponse->name = $yt->spfName;

    if (isset($yt->playerResponse)) {
        $spfResponse->data = (object) ['swfcfg' => (object) ['args' => (object) [
            'raw_player_response' => null,
            'raw_watch_next_response' => null
        ]]];
        $spfResponse->data->swfcfg->args->raw_player_response = $yt->playerResponse;
        $spfResponse->data->swfcfg->args->raw_watch_next_response = json_decode($yt->rawWatchNextResponse);

        if (isset($yt->page->playlist)) {
            $spfResponse->data->swfcfg->args->is_listed = '1';
            $spfResponse->data->swfcfg->args->list = $yt->playlistId;
            $spfResponse->data->swfcfg->args->videoId = $yt->videoId;
        }
    }

    echo json_encode($spfResponse);
} else {
    ob_end_flush();
}