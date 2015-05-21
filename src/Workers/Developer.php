<?php
namespace TekBooth\Workers;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use TekBooth\Service\Darkroom\GithubDarkroom;
use TekBooth\Service\GoPro\Client as GoPro;


/**
 * Developer process 'develops' the photos. Observing the camera filesystem, finding new photos and uploading them.
 *
 * Needs the storage service and the camera service.
 */
class Developer
{
    /**
     * @var GoPro
     */
    protected $gopro;

    /**
     * @var GithubDarkroom
     */
    protected $darkroom;

    /**
     * @var array
     */
    protected $list;

    /**
     * @var Imagine
     */
    protected $imagine;

    /**
     * @var \Imagine\Gd\Image|\Imagine\Image\ImageInterface
     */
    protected $watermark;

    public function __construct(GoPro $gopro, GithubDarkroom $githubDarkroom, Imagine $imagine, $watermark)
    {
        $this->gopro = $gopro;
        $this->darkroom = $githubDarkroom;
        $this->imagine = $imagine;
        $this->watermark = $imagine->open($watermark);
    }

    public function setup()
    {
        error_log('getting current upload list');
        $this->list = $this->darkroom->getPhotoList();
    }

    public function __invoke($daemon)
    {
        error_log('checking gopro files');

        $imagine = $this->imagine;
        $new = [];
        foreach($this->gopro->getFiles(GoPro::FILTER_PHOTO) as $file){
            if(in_array($file->getSequence(), $this->list)){
                continue;
            }

            $new[] = $file;
        }

        error_log('found files: ' . count($new));

        foreach($new as $file){
            error_log('downloading photo: ' . $file->getSequence());
            $content = $this->gopro->download($file);
            error_log('opening photo: ' . $file->getSequence());
            $photo = $imagine->load($content);

            error_log('resizing watermark');
            $watermark = $this->watermark->copy();

            $photoWidth = $photo->getSize();
            $waterSize = $watermark->getSize();
            $newSize = $waterSize->widen($photoWidth->getWidth() - 1);
            $watermark->resize($newSize);

            error_log('adding watermark');
            $pos = new Point(0, $photo->getSize()->getHeight() - $watermark->getSize()->getHeight());
            $photo->paste($watermark, $pos);

            error_log('uploading photo');
            $this->darkroom->upload($file, $photo->get('jpg'));

            error_log('resizing photo');
            $photo->resize(new Box($photo->getSize()->getWidth()/4, $photo->getSize()->getHeight()/4));

            error_log('uploading thumb');
            $this->darkroom->upload($file, $photo->get('jpg'), 'th');

            unset($photo);
            unset($watermark);

            $this->list[] = $file->getSequence();
        }

        if(!count($new)){
            error_log('no new files, waiting a bit');
            sleep(60);
        }
    }

}