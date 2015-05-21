<?php
namespace TekBooth\Service\Darkroom;

use Github\Client;
use PHPHtmlParser\Dom;
use TekBooth\Service\GoPro\File;

class GithubDarkroom implements DarkroomInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * The Page Template
     * @var string
     */
    protected $page;

    /**
     * The Photo Template
     * @var string
     */
    protected $photo;

    /**
     * @var string
     */
    protected $repo;

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $web;

    /**
     * @var string
     */
    protected $branch;

    public function __construct(Client $client, $page, $photo, $repo, $web, $branch = 'gh-pages')
    {
        $this->client   = $client;
        $this->page     = $page;
        $this->photo    = $photo;
        $this->branch   = $branch;
        $this->web      = $web;

        list($this->user, $this->repo) = explode('/', $repo);
    }

    public function addPhoto($session, File $file, $number)
    {
        $path = $session . '.html';
        $update = $this->client->api('repo')->contents()->exists($this->user, $this->repo, $path, $this->branch);

        if($update){
            $set = new \PHPHtmlParser\Dom();
            $set->load($this->client->api('repo')->contents()->download($this->user, $this->repo, $path, $this->branch));
            $info = $this->client->api('repo')->contents()->show($this->user, $this->repo, $path, $this->branch);
        } else {
            $set = new \PHPHtmlParser\Dom();
            $set->loadFromFile($this->page);
        }

        $div = $set->find('#photos')[0];

        $photo = new \PHPHtmlParser\Dom();
        $photo->loadFromFile($this->photo);

        $img = $photo->find('img')[0];
        $img->setAttribute('src', $this->getPhotoFilename($file));

        $div->addChild($photo->root);

        $images = $div->find('img');
        $count = count($images);

        if($count <= 4){
            $meta = new Dom();
            $meta->load('<meta name="twitter:image' . --$count . '" content="' . $this->web . $this->getPhotoFilename($file, 'th') . '">');
            $set->find('head', 0)->addChild($meta->root);
        }

        $content = \Mihaeu\HtmlFormatter::format((string) $set);
        $content = preg_replace("#\n\s*\n#", "\n", $content);

        if($update){
            $response = $this->client->api('repo')->contents()->update($this->user, $this->repo, $path, $content, 'Adding Photo ' . PHP_EOL . $file, $info['sha'], $this->branch);
        } else {
            $response = $this->client->api('repo')->contents()->create($this->user, $this->repo, $path, $content, 'Adding Page and Photo ' . PHP_EOL . $file, $this->branch);
            $this->addSession($session, $number);
        }
    }

    public function publish($session)
    {
        $path = $session . '.html';
        $update = $this->client->api('repo')->contents()->exists($this->user, $this->repo, $path, $this->branch);

        if(!$update){
            return;
        }

        $set = new \PHPHtmlParser\Dom();
        $set->load($this->client->api('repo')->contents()->download($this->user, $this->repo, $path, $this->branch));
        $info = $this->client->api('repo')->contents()->show($this->user, $this->repo, $path, $this->branch);

        $set->find('#placeholder')[0]->setAttribute('style', 'display: none;');
        $set->find('#photos')[0]->setAttribute('style', '');

        $content = \Mihaeu\HtmlFormatter::format((string) $set);
        $content = preg_replace("#\n\s*\n#", "\n", $content);

        $response = $this->client->api('repo')->contents()->update($this->user, $this->repo, $path, $content, 'Publishing Photos for ' . $session, $info['sha'], $this->branch);
    }

    public function upload(File $file, $content, $size = null)
    {
        $path = $this->getPhotoFilename($file, $size);
        $update = $this->client->api('repo')->contents()->exists($this->user, $this->repo, $path, $this->branch);
        if($update){
            return;
        }

        $response = $this->client->api('repo')->contents()->create($this->user, $this->repo, $path, $content, 'Adding Photo' . PHP_EOL . $file, $this->branch);
    }

    public function addSession($session, $number)
    {
        $owner = md5($number);
        $path = 'data.json';

        //fetch or create index json
        $update = $this->client->api('repo')->contents()->exists($this->user, $this->repo, $path, $this->branch);

        if($update){
            $data = $this->client->api('repo')->contents()->download($this->user, $this->repo, $path, $this->branch);
            $data = json_decode($data, true);
            $info = $this->client->api('repo')->contents()->show($this->user, $this->repo, $path, $this->branch);
        } else {
            $data = [
                'sessions' => [],
                'owners' => []
            ];
        }

        //add this session to main index
        if(!isset($data['sessions'][$session])){
            $data['sessions'][$session] = [
                'id' => $session,
                'date' => time(),
                'owner' => $owner
            ];
        }

        //add this session to number index
        if(!isset($data['owners'][$owner])){
            $data['owners'][$owner] = [
                'sessions' => []
            ];
        }

        if(!isset($data['owners'][$owner]['sessions'][$session])){
            $data['owners'][$owner]['sessions'][$session] = [
                'id' => $session,
                'date' => time(),
                'owner' => $owner
            ];
        }

        $content = json_encode($data, JSON_PRETTY_PRINT);

        //commit
        if($update){
            $response = $this->client->api('repo')->contents()->update($this->user, $this->repo, $path, $content, 'Adding Session ' . PHP_EOL . $session, $info['sha'], $this->branch);
        } else {
            $response = $this->client->api('repo')->contents()->create($this->user, $this->repo, $path, $content, 'Created data file and added session ' . PHP_EOL . $session, $this->branch);
        }
    }

    public function getPhotoList()
    {
        $list = [];
        if(!$this->client->api('repo')->contents()->exists($this->user, $this->repo, 'photos', $this->branch)){
            return $list;
        }


        foreach($this->client->api('repo')->contents()->show($this->user, $this->repo, 'photos', $this->branch) as $file){
            $sequence = substr($file['name'], -8);
            $sequence = substr($sequence, 0, 4);
            $list[] = $sequence;
        }

        return $list;
    }

    public function caption($session, $caption)
    {
        // TODO: Implement caption() method.
    }

    protected function getPhotoFilename(File $file, $size = null)
    {
        return 'photos/tek2015-' . $file->getSequence() . ($size?'-'.$size:'') . '.jpg';
    }

    protected function niceHtml($content)
    {
        $content = \Mihaeu\HtmlFormatter::format((string) $content);
        return preg_replace("#\n\s*\n#", "\n", $content);
    }
}