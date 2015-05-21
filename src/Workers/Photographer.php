<?php
namespace TekBooth\Workers;
use Pubnub\Pubnub;
use TekBooth\Service\Darkroom\GithubDarkroom;
use TekBooth\Service\GoPro\Client as GoPro;

/**
 * Photographer actually takes the photos. Waiting for a message that includes a 'session' id, once the photo is taken,
 * the image filename (not the file itself) is sent to storage. The Developer is expected to actually process the image
 * and upload.
 *
 * Needs the storage service, the message service, and the camera service.
 */

class Photographer
{
    /**
     * @var GoPro
     */
    protected $gopro;

    /**
     * @var Pubnub
     */
    protected $pubnub;

    /**
     * @var GithubDarkroom
     */
    protected $darkroom;

    public function __construct(GoPro $gopro, Pubnub $pubnub, GithubDarkroom $githubDarkroom)
    {
        $this->gopro = $gopro;
        $this->pubnub = $pubnub;
        $this->darkroom = $githubDarkroom;
    }

    public function __invoke($daemon)
    {
        $camera = $this->gopro;
        $darkroom = $this->darkroom;

        error_log('subscribed to channel');
        $this->pubnub->subscribe('tekbooth', function($data) use ($camera, $darkroom, $daemon){
            //grab session id and number
            $data['message'] = array_merge([
                'count' => 1,
                'mode'  => 'photo',
                'delay' => 0
            ],$data['message']);

            $session = $data['message']['session'];
            $number  = $data['message']['number'];
            $mode    = $data['message']['mode'];
            $count   = $data['message']['count'];
            $delay   = $data['message']['delay'];

            if(!$session){
                error_log('no session');
                return $daemon->run;
            }

            error_log('message data: ' . json_encode($data['message']));

            if($delay){
                sleep($delay);
            }

            //take photo
            switch($mode){
                case 'photo':
                default:
                    error_log('taking photo');
                    $camera->shutter();
                    sleep(2);
                    break;
            }

            $last = $camera->getLastFile($camera::FILTER_PHOTO);
            error_log('got last file: ' . $last);

            error_log('adding photo to session: ' . $last);
            $darkroom->addPhoto($session, $last, $number);

            return $daemon->run;
        });
    }

    public function setup()
    {
        $this->gopro->setMode(GoPro::MODE_PHOTO);
        sleep(3);
        $this->gopro->setPhotoResolution(06);
        sleep(3);
    }
}