<?php
namespace Robo\Task;

use Lurker\Event\FilesystemEvent;
use Lurker\ResourceWatcher;
use Robo\Result;
use Symfony\Component\EventDispatcher\Event;

/**
 * Watches files for changes and runs task on change.
 */
trait Watch {

    protected function taskWatch()
    {
        return new WatchTask($this);
    }

}

/**
 * Runs task when specified file or dir was changed.
 * Uses Lurker library.
 *
 * ``` php
 * <?php
 * $this->taskWatch()
 *  ->monitor('composer.json', function() {
 *      $this->taskComposerUpdate()->run();
 * })->monitor('src', function() {
 *      $this->taskExec('phpunit')->run();
 * })->run();
 * ?>
 * ```
 */
class WatchTask {
    use \Robo\Output;

    protected $closure;
    protected $monitor = [];
    protected $bindTo;

    public function __construct($bindTo)
    {
        $this->bindTo = $bindTo;
    }

    public function monitor($paths, \Closure $callable)
    {
        if (!is_array($paths)) {
            $paths = [$paths];
        }
        $this->monitor[] = [$paths, $callable];
        return $this;

    }

    public function run()
    {
        $watcher = new ResourceWatcher();

        foreach ($this->monitor as $k => $monitor) {
            foreach ($monitor[0] as $dir) {
                $watcher->track("fs.$k", $dir, FilesystemEvent::MODIFY);
                $this->printTaskInfo("watching $dir for changes...");
            }
            $closure = $monitor[1];
            $closure->bindTo($this->bindTo);
            $watcher->addListener("fs.$k", $closure);
        }

        $watcher->start();
        return Result::success($this);
    }

}