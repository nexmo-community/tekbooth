<?php
namespace TekBooth\Controller;
use Pubnub\Pubnub;

/**
 * The Photographers Assistant, this IVR communicates with the general public and passes messages onto the Photographer.
 * Responsible for answering the phone, letting the Customer know to get ready, letting the Photographer know to take
 * the photos, and sending the Customer a link to the photos.
 *
 * Since the Developer needs time to upload the photos, the Assistant must generate a placeholder page, and send the
 * link to that. It's expected that the Developer will replace that placeholder once the photos are uploaded.
 */

class Assistant
{
    /**
     * @var Pubnub
     */
    protected $pubnub;

    /**
     * @var string
     */
    protected $vxmlPath;

    public function __construct(Pubnub $pubnub, $vxmlPath)
    {
        $this->pubnub = $pubnub;
        $this->vxmlPath = $vxmlPath;
    }

    public function getVxml($number, $callid)
    {
        ob_start();
        include ($this->vxmlPath . '/ivr.phtml');
        return ob_get_clean();
    }

    public function takePhoto($number, $callid, $optional = array())
    {
        $data = array_merge($optional, [
            'session' => $callid,
            'number'  => $number,
        ]);

        $this->pubnub->publish('tekbooth', $data);
    }
}