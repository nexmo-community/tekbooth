<?php
namespace TekBooth\Service\GoPro;


class File
{
    /**
     * Can be used to identify continuous shots, special videos.
     * @var string
     */
    protected $prefix;

    /**
     * Autonumbered Counter
     * @var string
     */
    protected $sequence;

    /**
     * The File Type
     * @var string
     */
    protected $type;

    /**
     * Full path to the image (after DCIM)
     * @var string
     */
    protected $path;

    public function __construct($path, $file)
    {
        $info = pathinfo($file);

        //can identify special settings
        $this->prefix = substr($info['filename'], 0, 4);
        $this->sequence = substr($info['filename'], 4);
        $this->path = $path . $file;
        $this->type = $info['extension'];
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    function __toString()
    {
        return $this->getPath();
    }


}