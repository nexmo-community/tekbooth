<?php
namespace TekBooth\Service\GoPro;
use PHPHtmlParser\Dom;

class Client
{
    const BACPAC = 'bacpac';
    const CAMERA = 'camera';

    const MODE_VIDEO = '00';
    const MODE_PHOTO = '01';
    const MODE_BURST = '02';
    const MODE_LAPSE = '03';
    const MODE_TIMER = '04';
    const MODE_HDMI  = '05';

    const FILTER_PHOTO = 'JPG';
    const FILTER_VIDEO = 'MP4';
    const FILTER_THUMB = 'THM';
    const FILTER_LOREZ = 'LRV';

    /**
     * GoPro Password
     * @var string
     */
    protected $password;

    /**
     * IP Address of Device
     * @var string
     */
    protected $ip;

    /**
     * Port for Control
     * @var string
     */
    protected $control;

    /**
     * Port for Web Miniserver
     * @var string
     */
    protected $web;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    public function __construct($password, $ip = '10.5.5.9', $control = '80', $web = '8080')
    {
        $this->password = $password;
        $this->ip = $ip;
        $this->control = $control;
        $this->web = $web;
    }

    public function power($on = true)
    {
        $this->command(
            self::BACPAC,
            'PW',
            ['t' => $this->password, 'p' => ($on ? chr(01) : chr(00))]
        );

        return true;
    }

    public function setDate($date = null)
    {
        if(is_null($date)){
            $date = new \DateTime();
        } elseif(!($date instanceof \DateTime)){
            $date = new \DateTime($date);
        }

        $args = explode('-', $date->format('y-n-j-G-i-s'));
        $args = array_map('chr', $args);
        $args = implode('', $args);

        $this->command(
            self::CAMERA,
            'TM',
            ['t' => $this->password, 'p' => $args]
        );
    }

    public function setMode($mode)
    {
        $result = $this->command(
            self::CAMERA,
            'CM',
            ['t' => $this->password, 'p' => chr($mode)]
        );

        $code = ord($result);

        return true;
    }

    public function setPhotoResolution($resolution)
    {
        $result = $this->command(
            self::CAMERA,
            'PR',
            ['t' => $this->password, 'p' => chr($resolution)]
        );

        $code = ord($result);

        return true;
    }

    public function shutter()
    {
        $result = $this->command(
            self::BACPAC,
            'SH',
            ['t' => $this->password, 'p' => chr(01)]
        );

        $code = ord($result);

        return true;
    }

    public function stop()
    {
        $result = $this->command(
            self::BACPAC,
            'SH',
            ['t' => $this->password, 'p' => chr(00)]
        );

        $code = ord($result);

        return true;
    }

    public function getModel()
    {
        return new Model($this->command(self::CAMERA, 'cv'));
    }

    public function command($path, $command, $args = [])
    {
        $request = $this->getHttpClient()->createRequest('GET', 'http://' . implode('/', [$this->ip . ':' . $this->control, $path, $command]));
        $request->getQuery()->merge($args);

        $response = $this->getHttpClient()->send($request);

        if($response->getStatusCode() !== 200){
            throw new \RuntimeException('bad response from camera: ' . $response->getBody()->getContents());
        }

        return $response->getBody()->getContents();
    }

    public function getDirectories()
    {
        $result = $this->getHttpClient()->get('http://' . $this->ip . ':' . $this->web . '/DCIM');

        $dom = new Dom();
        $dom->load($result->getBody()->getContents());
        $dirs = [];
        foreach($dom->find('tbody a') as $link){
            $dirs[] = $link->getAttribute('href');
        }

        return $dirs;
    }

    /**
     * @param null $filter
     * @return File[]
     */
    public function getFiles($filter = null)
    {
        $files = [];
        foreach($this->getDirectories() as $path){
            $result = $this->getHttpClient()->get('http://' . $this->ip . ':' . $this->web . '/DCIM/' . $path);
            $dom = new Dom();
            $dom->load($result->getBody()->getContents());

            foreach($dom->find('tbody a') as $link){
                $file = new File($path, $link->getAttribute('href'));

                if($filter AND $filter !== $file->getType()){
                    continue;
                }

                $files[] = $file;
            }
        }
        return $files;
    }

    /**
     * @param null $filter
     * @return File
     */
    public function getLastFile($filter = null)
    {
        $files = $this->getFiles($filter);

        return array_reduce($files, function(File $last = null, File $file){
            if(!$last){
                return $file;
            }

            if($last->getSequence() > $file->getSequence()){
                return $last;
            }

            return $file;
        });
    }

    public function download(File $file)
    {
        return $this->getHttpClient()->get('http://' . $this->ip . ':' . $this->web . '/DCIM/' . $file->getPath())->getBody();
    }

    public function setHttpClient(\GuzzleHttp\Client $client)
    {
        $this->client = $client;
    }

    public function getHttpClient()
    {
        if(!$this->client){
            $this->setHttpClient(new \GuzzleHttp\Client());
        }

        return $this->client;
    }


}