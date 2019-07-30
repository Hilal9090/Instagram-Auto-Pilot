<?php
require("../../lib/vendor/autoload.php");

class InstagramAutoPilot {

    public function init($ACC_NAME, $ACC_PASS, $GET_NEW_IMAGES, $FOLLOW_USERS, $UNFOLLOW_USERS, $ACCOUNTS, $TAGS, $COMMENT) {
        $instagram = new \Instagram\Instagram();
        $instagram->login($ACC_NAME, $ACC_PASS);

        // Задаем задержку после запуска скрипта.
        // sleep(rand(32, 50));

        if($GET_NEW_IMAGES == true) { $this->getNewImage($ACC_NAME, $instagram, $ACCOUNTS, $TAGS); } 

        if($FOLLOW_USERS == true) { $this->followUsers($instagram, $ACCOUNTS, $ACC_NAME); }

        if($UNFOLLOW_USERS == true) { $this->unfollowUsers($instagram, $ACC_NAME); }

        if($COMMENT != "") { $this->comment($instagram, $ACCOUNTS, $COMMENT); }
    }



    /* MARK: Core Functionality    */
    /*******************************/
    function getNewImage($ACC_NAME, $instagram, $accounts, $tags) {
        $keepGoing = true;
        $randomAccount = array($accounts[rand(0, (sizeof($accounts) - 1))]);
        $user = $instagram->getUserByUsername($randomAccount[0]);
        $userFeed = $instagram->getUserFeed($user);
        $userFeed = $instagram->getUserFeed($user, $userFeed->getNextMaxId()); // page 2

        foreach($userFeed->getItems() as $feedItem){
            $idOfImage = $feedItem->getId(); //ID Ленты
            $images = $feedItem->getImageVersions2()->getCandidates(); //Сохраняем список изображений
            $photoUrl = $images[0]->getUrl(); //Сохраняем ссылку на первую фотографию

            if($keepGoing) { // проверяем, не загружалось ли нами ранее
                $filename = "repostedFrom/" . $randomAccount[0] . ".txt";
                $keepGoing = $this->checkIfIdSeenBefore($filename, $idOfImage);

                if($keepGoing == false) {
                    $photoLocationOnDisk = "imagesUploaded/" . $idOfImage . ".jpg";
                    
                    copy($photoUrl, $photoLocationOnDisk); // копируем фотографию в локальную папку

                    $caption = $feedItem->getCaption()->getText();
                    
                    $instagram->postPhoto($photoLocationOnDisk, $caption); // загружаем фото

                    $file = "";
                    if(!file_exists($filename)) { 
                       $file = fopen($filename, "w");  
                    }  
                    else {
                       $file = fopen($filename, "a");  
                    }
                    fwrite($file, "\n" . $idOfImage);  
                    fclose($file); 


                    $this->writeToLogs("\n\Выполнен репост. [" . date("Y-m-d h:i:sa", time()) . "]");
                }
            }
        }
    }

    function comment($instagram, $accounts, $comment) {
        $maxDelayTime = 15; // Максимальное время между API запросами
        $totalCommentsToMake = rand(9, 30);
        $keepGoing = true;
        $randomAccount = array($accounts[rand(0, (sizeof($accounts) - 1))]);
        $user = $instagram->getUserByUsername($randomAccount[0]);
      
        $followers = $instagram->getUserFollowers($user);
        foreach($followers->getFollowers() as $follower) {
           
            $userFeed = $instagram->getUserFeed($follower);

            if(sizeof($userFeed->getItems()) > 0) {
                foreach($userFeed->getItems() as $feedItem){
                    $idOfImage = $feedItem->getId(); //Feed Item ID
                    $images = $feedItem->getImageVersions2()->getCandidates(); //Сохраняем список изображений
                    $photoUrl = $images[0]->getUrl(); //Сохраняем ссылку на первую фотографию

                    if($keepGoing) { // если находим фотографию, то не комментируем ее
                        $filename = "comments.txt";
                        $keepGoing = $this->checkIfIdSeenBefore($filename, $follower->getUsername());

                        if($keepGoing == false) {
                                if($totalCommentsToMake > 0) {
                                $instagram->commentOnMedia($idOfImage, $comment);

                                // $photoLocationOnDisk = "imagesCommented/" . $idOfImage . ".jpg";
                                // copy($photoUrl, $photoLocationOnDisk); // копируем фотографию в локальную папку

                                $file = "";
                                if(!file_exists($filename)) { 
                                   $file = fopen($filename, "w");  
                                }  
                                else {
                                   $file = fopen($filename, "a");  
                                }
                                fwrite($file, "\n" . $follower->getUsername());  
                                fclose($file); 

                                // reset
                                $keepGoing = true;
                                $totalCommentsToMake--;

                                $delayTime = rand(8, $maxDelayTime);

                                $this->writeToLogs("\nПрокомментировали " . $follower->getUsername() . ". Засыпаю на " . $delayTime . " секунд-(ы). [" . date("Y-m-d h:i:sa", time()) . "]");

                                sleep($delayTime);
                            }
                        }
                    }
                }
            }
        }
    }

    function followUsers($instagram, $accounts, $accountName) {
        $maxDelayTime = 15; // Максимальное время между API запросами
        $maxFollow = rand(40, 80); // Максимальное количество пользователей для подписки
        $shouldFollow = rand(0, 1);
        $randomAccount = array($accounts[rand(0, 10)]);
        $user = $instagram->getUserByUsername($randomAccount[0]);
        $followers = $instagram->getUserFollowers($user);

        $myAccount = $instagram->getUserByUsername($accountName);
        $alreadyfollowing = $instagram->getUserFollowing($myAccount);

        if($shouldFollow == 1) {
            $this->writeToLogs("\n\nИнформация о подписавшихся пользователях...");

            foreach($followers->getFollowers() as $toFollow) {

                // если не подписчик больше
                if(!in_array($toFollow, $alreadyfollowing->getFollowers()) && $maxFollow > 0) {
                    $instagram->followUser($toFollow);
                    
                    $delayTime = rand(8, $maxDelayTime);

                    $this->writeToLogs("\nПодписался на " . $toFollow->getUsername() . " Засыпаю на " . $delayTime . " секунд-(ы). [" . date("Y-m-d h:i:sa", time()) . "]");
                    $maxFollow--;

                    sleep($delayTime);
                }
            }
        }
    }

    function unfollowUsers($instagram, $accountName) {
        $maxDelayTime = 15; // Максимальное время между API запросами
        $maxUnfollow = rand(20, 240); // Максимальное количество пользователей для отписки

        $myAccount = $instagram->getUserByUsername($accountName);
        $alreadyfollowing = $instagram->getUserFollowing($myAccount);

        $this->writeToLogs("\n\nИнформация об отписавшихся пользователях...");

        foreach($alreadyfollowing->getFollowers() as $toUnfollow) {
            if($maxUnfollow > 0) {
                $instagram->unfollowUser($toUnfollow);
                $maxUnfollow--;
                
                $delayTime = rand(8, $maxDelayTime);

                $this->writeToLogs("\nОтписался от " . $toUnfollow->getUsername() . " Засыпаю на " . $delayTime . " секунд-(ы). [" . date("Y-m-d h:i:sa", time()) . "]");
                
                sleep($delayTime);
            }
        }
    }

    function getRandomTags($tags) {
        $usedTags = array();
        $tagsString = "";

        if(sizeof($tags) > 20) {
            for($i = 0; $i < 21; $i++) {
                $randomTag = $tags[array_rand($tags)];
                if(!in_array($randomTag, $usedTags)) {
                    $tagsString .= " #" . $randomTag;
                    array_push($usedTags, $randomTag);
                }
            }
        }
        else {
            $tagsString = implode(" ", $tags);
        }

        return $tagsString;
    }

    /**
    * Проверяем txt файлы на их наличие
    **/
    function checkIfIdSeenBefore($filename, $id) {
        $file = @fopen($filename, "r");
        $idSeen = false;

        if ($file) {
            while (($line = fgets($file)) !== false) {
                $line = str_replace("\n", "", $line); // скрываемся

                if(strcmp($id, $line) === 0) {
                    $idSeen = true;
                }
            }

            fclose($file);
        } 
        else {
            // ошибка открытия файла.
        } 

        return $idSeen;
    }   

    function writeToLogs($textToWrite) {
        $currentfile = "Logs.txt";
        $updatedFile = file_get_contents($currentfile);
        $updatedFile .= $textToWrite;
        file_put_contents($currentfile, $updatedFile);
    }  
}
?>
