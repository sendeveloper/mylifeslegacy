<?php
    
    // make sure that you're using newest ffmpeg version!

    // because we've different ffmpeg commands for windows & linux
    // that's why following script is used to fetch target OS
    $OSList = array
    (
        'Windows 3.11' => 'Win16',
        'Windows 95' => '(Windows 95)|(Win95)|(Windows_95)',
        'Windows 98' => '(Windows 98)|(Win98)',
        'Windows 2000' => '(Windows NT 5.0)|(Windows 2000)',
        'Windows XP' => '(Windows NT 5.1)|(Windows XP)',
        'Windows Server 2003' => '(Windows NT 5.2)',
        'Windows Vista' => '(Windows NT 6.0)',
        'Windows 7' => '(Windows NT 7.0)',
        'Windows NT 4.0' => '(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)',
        'Windows ME' => 'Windows ME',
        'Open BSD' => 'OpenBSD',
        'Sun OS' => 'SunOS',
        'Linux' => '(Linux)|(X11)',
        'Mac OS' => '(Mac_PowerPC)|(Macintosh)',
        'QNX' => 'QNX',
        'BeOS' => 'BeOS',
        'OS/2' => 'OS/2',
        'Search Bot'=>'(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp)|(MSNBot)|(Ask Jeeves/Teoma)|(ia_archiver)'
    );
    // Loop through the array of user agents and matching operating systems
    foreach($OSList as $CurrOS=>$Match)
    {
        // Find a match
        if (preg_match("/".$Match."/i", $_SERVER['HTTP_USER_AGENT']))
        {
            // We found the correct match
            break;
        }
    }

    $msg = array("result" => "failed", "message" => "Unknown error");

    $upload_url = "uploads/temp/"; $upload_save_url = "uploads/temp/";
    // if it is audioblob
    // if (isset($_FILES["audioblob"])) {
        // $uploadDirectory = $upload_url . $_POST["filename"].'.mp4';
        // if (!move_uploaded_file($_FILES["audioblob"]["tmp_name"], $uploadDirectory)) {
            $msg['result'] = "failed";
            $msg['message'] = "Problem writing audio file to disk!";
        // }
        // else {
            // $msg['result'] = "success";
            // $msg['message'] = "Video is successfully uploaded!\n";

            // if it is videoblob
            if (isset($_FILES["videoblob"])) {
                $uploadDirectory = $upload_url . $_POST["filename"].'.webm';
                if (!move_uploaded_file($_FILES["videoblob"]["tmp_name"], $uploadDirectory)) {
                    $msg['result'] = "failed";
                    $msg['message'] = "Problem writing video file to disk!";
                }
                else {
                    $audioFile = $upload_url . $_POST["filename"].'.mp4';
                    $videoFile = $upload_url . $_POST["filename"].'.webm';
                    
                    $mergedFile = $upload_save_url . $_POST["filename"].'.mp4';
                    
                    // ffmpeg depends on yasm
                    // libvpx depends on libvorbis
                    // libvorbis depends on libogg
                    // make sure that you're using newest ffmpeg version!
                    
                    // if(!strrpos($CurrOS, "Windows")) {

                        // $cmd = '-y -i '.$audioFile.' -i '.$videoFile.' -map 1:v:0 -map 0:a:0 '.$mergedFile;
                    // }
                    // else {
                        // $cmd = '-y -i '.$audioFile.' -i '.$videoFile.' -c:v mpeg4 -c:a vorbis -b:v 64k -b:a 12k -strict experimental '.$mergedFile;
                        $cmd = '-y -i '.$videoFile.' '.$mergedFile;
                    // }
                    
                    exec('ffmpeg '.$cmd.' 2>&1', $out, $ret);
                    if ($ret){
                        $msg['result'] = "failed";
                        $msg['message'] = "There was a problem!\n";
                        $msg['message'] .= $cmd.'\n';
                        $msg['message'] .= var_export($out, true);
                    } else {
                        $msg['result'] = "success";
                        $msg['message'] = "Video is successfully uploaded!\n";
                        // unlink($audioFile);
                        unlink($videoFile);
                    }
                }
            }
        // }
    // }

    echo json_encode($msg);
	
