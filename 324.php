<?php
$dir = '/run/media/amtf/Windows8_OS/Users/huanle0610.HL-PC/Documents/english/2017/';
$di = new DirectoryIterator($dir);

$queue = [
  '秘密花园英国名著【英文有声书】经典入门',
  '书虫•Oliver Twist',
];

$cmd = 'ffmpeg -loop 1 -framerate 1/25 -i apple.png -i %s -shortest -acodec copy -brand mp42 %s 2>&1';

foreach ($di as $item) {
    if ($item->isDir() && in_array($item->getFilename(), $queue)) {
        echo $item->getFilename(), "\n";
        $path = $item->getRealPath();
        $mp3Files = new ExtensionFilterIterator(new DirectoryIterator($path), ['mp3']);
        $mp4Dir = $path . '-mp4';
        if(!is_dir($mp4Dir))
        {
            mkdir($mp4Dir, 0755);
        }
        /* @var $file DirectoryIterator */
        foreach ($mp3Files as $file) {
            $trackNum = getTrackNum($file->getRealPath());
            $trackNumStr = $trackNum ? sprintf("%02d",$trackNum) : '';
            $mp4File = $mp4Dir . "/$trackNumStr-" . $file->getBasename($file->getExtension()) . 'mp4';
//            echo $mp4File, "\n";
            if(file_exists($mp4File) && filesize($mp4File) == 0)
            {
                echo $mp4File, "\n";
                unlink($mp4File);
            }

            if(file_exists($mp4File))
            {
                continue;
            }

            $safeCmd = sprintf($cmd . "\n", escapeshellarg($file->getRealPath()), escapeshellarg($mp4File));
            echo $safeCmd, "\n";
            exec($safeCmd);
        }
    }
}

function getTrackNum($file)
{
    $tmpOut = '/tmp/ff.out';
    $getTrackCmd = "ffprobe -hide_banner %s > $tmpOut 2>&1";

    if(file_exists($tmpOut))
    {
        unlink($tmpOut);
    }
    $safeGetTrackCmd = sprintf($getTrackCmd, escapeshellarg($file));
    exec($safeGetTrackCmd, $out, $ret);
    if(0 === $ret)
    {
        $outString = file_get_contents($tmpOut);
        if(preg_match('@track\s*:\s*(\d+)@', $outString, $match))
        {
            return $match[1];
        }
    }

    return false;
}

class ExtensionFilterIterator extends FilterIterator
{
    private $filter;

    public function __construct(Iterator $iterator, $filter)
    {
        parent::__construct($iterator);
        $this->filter = $filter;
    }

    public function accept()
    {
        $current = $this->getInnerIterator()->current();
        return in_array($current->getExtension(), $this->filter);
    }
}