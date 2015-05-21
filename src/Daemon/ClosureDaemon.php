<?php
namespace TekBooth\Daemon;

class ClosureDaemon {
    /**
     * @var callable
     */
    protected $setup;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var callable
     */
    protected $shutdown;

    /**
     * @var bool
     */
    public $run = false;

    /**
     * @var int
     */
    protected $count;

    public function __construct(callable $callback, callable $setup = null, callable $shutdown = null)
    {
        $this->callback = $callback;

        if(!$setup){
            $setup = function(){error_log('no setup defined');};
        }

        if(!$shutdown){
            $shutdown = function(){error_log('no shutdown defined');};
        }

        $this->setup = $setup;
        $this->shutdown = $shutdown;

        pcntl_signal(SIGINT,  [$this, 'signalStop']);
        pcntl_signal(SIGHUP,  [$this, 'signalReload']);
        pcntl_signal(SIGTERM, [$this, 'signalStop']);
    }

    protected function setup()
    {
        error_log('daemon setup');
        $setup = $this->setup;
        $setup($this);
    }

    protected function shutdown()
    {
        error_log('daemon shutdown');
        $shutdown = $this->shutdown;
        $shutdown();
    }

    protected function run()
    {
        $callback = $this->callback;
        $this->count = 0;
        error_log('daemon starting...');
        while($this->run){
            $this->count++;

            $run = $callback($this);

            //only process the signals here
            pcntl_signal_dispatch();
        }
    }

    public function signalStop($signal)
    {
        error_log('caught shutdown signal [' . $signal . ']');
        $this->run = false;
    }

    public function signalReload($signal)
    {
        error_log('caught reload signal [' . $signal . ']');
        $this->setup();
    }

    public function start()
    {
        $this->setup();
        $this->run = true;
        $this->run();
        $this->shutdown();
    }
}