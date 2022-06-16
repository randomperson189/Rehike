<?php
use \Rehike\Request;

$yt->spfEnabled = true;
$yt->useModularCore = true;
$template = 'feed/guide_builder';
$yt->modularCoreModules = ['www/feed'];
$yt->page = (object) [];
$yt->enableFooterCopyright = true;

if(!isset($yt->spf) or $yt->spf == false) {
    require "mod/getGuide.php";
}

Request::innertubeRequest("feed", "browse", (object)[
    "browseId" => "FEguide_builder"
]);
$response = Request::getInnertubeResponses()["feed"];

$timeb = round(microtime(true) * 1000);
$ytdata = json_decode($response);

$yt->page->data = $response;

$shelvesList = $ytdata->contents->twoColumnBrowseResultsRenderer->
    tabs[0]->tabRenderer->content->sectionListRenderer->contents;

$yt->page->shelvesList = $shelvesList;