<?php
	require_once('../InstagramAutoPilot.php');

    $iap = new InstagramAutoPilot;

    $accounts = array("kimkardashian", "daquan", "fuckjerry", "thefatjewish", "theestallion", "meekmill", "lilbaby_1", "lilyachty", "dolantwins", "chicklet.hf", "blameitonkway", "9gag", "jackwhitehall ", "sarahandersencomics ", "thegoodquote", "jude_devir", "hoodclips", "h3h3productions");
    $tags = array("------------", '-----------');
    $comment = array("Cool!", 'Nice!', "Amazing!", 'Awesome!', "Gorgeous!", 'So sweet!', "So funny!", 'Fantastic!', "Wooow!");

    $iap->init("ACCOUNT_NAME", "ACCOUNT_PASSWORD", true, true, false, $accounts, $tags, $comment);
?>
