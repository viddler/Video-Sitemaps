<?
set_time_limit(0);
include '/path/to/your/viddler-api-wrapper';
include 'video-sitemap.php';

$sm = new Video_Sitemap('YOUR API KEY', 'YOUER VIDDLER USERNAME', 'YOUR VIDDLER PASSWORD', 'logs/');
$sm->create('sitemaps/');
$sm->stats();