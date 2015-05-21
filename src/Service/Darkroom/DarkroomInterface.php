<?php
namespace TekBooth\Service\Darkroom;

use TekBooth\Service\GoPro\File;

interface DarkroomInterface
{
    public function addPhoto($session, File $file, $number);

    public function upload(File $file, $content);

    public function caption($session, $caption);

    public function addSession($session, $number);
}