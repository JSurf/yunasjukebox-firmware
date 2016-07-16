<?php
require "../../vendor/autoload.php";
require "../../config.php";
require 'App.php';
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new AppJson();
$request = Request::createFromGlobals();
if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
    $data = json_decode($request->getContent(), true);
    $request->request->replace(is_array($data) ? $data : array());
}

$app->get_p('/rfid/upload', function () use($app, $config)
{
    
    $flconfig = new \Flow\Config();
    $flconfig->setTempDir($config["mpdpath"] . '/chunks_temp');
    $file = new \Flow\File($flconfig);
    
    $flrequest = new \Flow\Request();
    
    if ($file->checkChunk()) {
        return new Response('Chunk available', 200);
    } else {
        return new Response('Chunk not available', 404);
    }
});

$app->post_p('/rfid/upload', function () use($app, $config)
{
    $flconfig = new \Flow\Config();
    $flconfig->setTempDir($config["mpdpath"] . '/chunks_temp');
    $file = new \Flow\File($flconfig);
    
    $flrequest = new \Flow\Request();
    
    if ($file->validateChunk()) {
         $file->saveChunk();
    } else {
          // error, invalid chunk upload request, retry
          return new Response('Invalid Chunk', 400);
    }

    if ($file->validateFile() && $file->save($config["mpdpath"] . '/files/' . $flrequest->getFileName())) {
        // File upload was completed
        return new Response('Upload finished', 200);
    } else {
        // This is not a final chunk, continue to upload
        return new Response('Waiting for next Chunk', 200);
    }
});

$app->post_p('/rfid/register', function () use($app, $config, $request)
{
    $rfidTag = $request->request->get('rfidTag');
    
    $info = array();
    $info['title'] = $request->request->get('title');
    
    $rfidPath = $config["mpdpath"] . '/music/' . $app->quote($rfidTag);
    mkdir($rfidPath, 777, true);
    file_put_contents($rfidPath . '/info.json', json_encode($info));
    
    $files = $request->request->get('files');
    foreach ($files as $file) {
        rename($config["mpdpath"] . '/files/' . $file['relativePath'], $rfidPath . '/' . $file['relativePath']);
    }
    
    $mpd = new mpd($config["mpdserver"], 6600);
    $mpd->DBRefresh();
    $mpd->Disconnect();
    
    return "OK";
});

$app->get_p('/rfid/list', function () use($app, $config)
{
    $rfidTags = array();
    foreach (new DirectoryIterator($config["mpdpath"] . '/music') as $fileInfo) {
        if ($fileInfo->isDot())
            continue;
        if ($fileInfo->isDir()) {
            if (is_file($fileInfo->getPathname() . "/info.json")) {
                $tag = array();
                $tag['tag'] = $fileInfo->getFilename();
                $tag['info'] = json_decode(file_get_contents($fileInfo->getPathname() . "/info.json"));
                $tag['tracks'] = array();
                
                $path = $fileInfo->getPathname();
                if (is_file($path . "/info.json")) {
                    $tag['info'] = json_decode(file_get_contents($path . "/info.json"));
                    foreach (new DirectoryIterator($path) as $fileInfo) {
                        if ($fileInfo->isDot()) {
                            continue;
                        }
                        if ($fileInfo->isFile() && $fileInfo->getFilename() !== "info.json") {
                            $tag['tracks'][] = $fileInfo->getFilename();
                        }
                    }
                }
				sort($tag['tracks']);
                $rfidTags[] = $tag;
            }
        }
    }
    return $rfidTags;
});

$app->get_p('/rfid/last', function () use($app, $config)
{
    if (is_file($config["mpdpath"] . "/rfidlast")) {
        return file_get_contents($config["mpdpath"] . "/rfidlast");
    }
    return "none";
});

$app->get_p('/rfid/:p', function ($rfidTag) use($app, $config)
{
    $tag = array();
    $tag['tag'] = $rfidTag;
    $tag['info'] = array();
    $tag['tracks'] = array();
    
    $path = $config["mpdpath"] . '/music/' . $app->quote($rfidTag);
    if (is_file($path . "/info.json")) {
        $tag['info'] = json_decode(file_get_contents($path . "/info.json"));
        foreach (new DirectoryIterator($path) as $fileInfo) {
            if ($fileInfo->isDot())
                continue;
            if ($fileInfo->isFile() && $fileInfo->getFilename() !== "info.json") {
                $tag['tracks'][] = $fileInfo->getFilename();
            }
        }
		sort($tag['tracks']);
    }
    return $tag;
});
$app->get_p('/rfid/delete/:p', function ($rfidTag) use($app, $config)
{
    $path = $config["mpdpath"] . '/music/' . $app->quote($rfidTag);
    if (is_file($path . "/info.json")) {
        
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        
        echo rmdir($path);
    }
    return "OK";
});

$app->get('/player/:p', function ($command) use($app, $config)
{
    $mpd = new mpd($config["mpdserver"], 6600);
    $mpd->SendCommand($command);
    $mpd->Disconnect();
    return $mpd;
});

$app->get_p('/player/play/:p', function ($rfidTag) use($app, $config)
{
    $mpd = new mpd($config["mpdserver"], 6600);
    $mpd->PLClear();
    $mpd->PLAdd($rfidTag);
    $mpd->Play();
    $mpd->Disconnect();
    return $mpd;
});
