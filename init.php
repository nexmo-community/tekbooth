<?php
chdir(__DIR__);
require_once 'vendor/autoload.php';

$pimple = new \Pimple\Container();

$pimple['config'] = function($c) {
    return include 'config/config.php';
};

$pimple['camera'] = function($c) {
    $config = $c['config'];
    return new \TekBooth\Service\GoPro\Client($config['gopro']['password']);
};

$pimple['pubnub'] = function($c) {
    $config = $c['config'];
    return new \Pubnub\Pubnub($config['pubnub']);
};

$pimple['github'] = function($c) {
    $config = $c['config'];
    $github = new \Github\Client();
    $github->authenticate($config['github']['key'], null, $github::AUTH_HTTP_TOKEN);
    return $github;
};

$pimple['darkroom'] = function($c) {
    $config = $c['config'];
    $github = $c['github'];

    return new TekBooth\Service\Darkroom\GithubDarkroom(
        $github,
        'templates/set.html',
        'templates/photo.html',
        $config['github']['repo'],
        $config['github']['url'],
        $config['github']['branch']
    );
};

$pimple['photographer'] = function($c) {
    $photographer = new \TekBooth\Workers\Photographer($c['camera'], $c['pubnub'], $c['darkroom']);
    return new \TekBooth\Daemon\ClosureDaemon($photographer, [$photographer, 'setup']);
};

$pimple['developer'] = function($c) {
    $developer = new \TekBooth\Workers\Developer($c['camera'], $c['darkroom'], new \Imagine\Gd\Imagine(), 'templates/watermark.png');
    return new \TekBooth\Daemon\ClosureDaemon($developer, [$developer, 'setup']);
};

$pimple['sms'] = function($c) {
    return new \Nexmo\Sms($c['config']['nexmo']);
};

$pimple['assistant'] = function($c) {
    $config = $c['config'];
    return new \TekBooth\Controller\Assistant($c['pubnub'], $c['sms'], $config['nexmo']['from'], './vxml', $config['github']['url']);
};

return $pimple;