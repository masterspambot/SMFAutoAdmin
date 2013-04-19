<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Dictionary
 *
 * @author Mateusz
 */
include (dirname(__FILE__) . '/../Settings.php');
class Dictionary {
    public static function checkWithDictionary($message)
    {   
        global $modSettings;
        $list_ban_word = $modSettings['mod_ban_list'];
        $list_ban_word = str_replace(" ", "", $list_ban_word);
        $list = explode(',', $list_ban_word);
        $counter = 0;
        foreach ($list as $w) {
            if (strpos($message, $w) !== false) {
                $counter++;
            }
        } 
        return $counter;
    }
    
    public static function checkForbiddenWords($message)
    {
        global $modSettings;
        $list_forbidden_words = $modSettings['mod_forb_list'];
        $list_forbidden_words = str_replace(" ", "", $list_forbidden_words);
        $list = explode(',', $list_forbidden_words);
        Dictionary::connectToDatabase();
        $words = Dictionary::getAllWordsFromDictionary();
        $counter = 0;
        foreach ($words as $w) {
            if (strpos($message, $w->word) !== false) {
                $counter++;
            }
        }
        foreach ($list as $w) {
            if (strpos($message, $w) !== false) {
                $counter++;
            }
        }
        return $counter;
    }
    public static function muteAction($counter, $user, $obligatoryBan)
    {
        $mes = "Znaleziono $counter slow łamiących regulamin!. ";
        $mes.= Dictionary::raportujZlamanieRegulaminu($user, $obligatoryBan);
        //user 1 is admin
        Dictionary::sendPrivateMessage($mes, "1", $user);
        return array(FALSE, NULL);
    }
    
    public static function WarnAction($counter, $user, $obligatoryBan)
    {
        $mes = "Znaleziono $counter slow łamiących regulamin!. ";
        $mes.= Dictionary::raportujZlamanieRegulaminu($user, $obligatoryBan);
        return array(TRUE, $mes);
    }

    public static function ModerateAction()
    {
        return array(TRUE, "Twoj post zostanie poddany moderacji.");
    }
    
    public static function DropAction($user)
    {
        Dictionary::addBanToUser($user, 2);
        return array(TRUE, "Twoje konto zostalo usuniete");
    }

    public static function sendPrivateMessage($content, $from, $to)
    {
        $t = time();
        global $context;
        $prefix = $context['my_prefix'];
        $str = "insert into ".$prefix."personal_messages(id_pm_head, id_member_from, deleted_by_sender, from_name, msgtime, subject, body) values 
            ('2', '$from', '1', 'admin', '$t', 'Ostrzezenie', '$content')"; 
        Dictionary::executeQuery($str);
        $str2 = "select id_pm from ".$prefix."personal_messages where msgtime = '$t'";
        $w = Dictionary::getArray($str2);
        $pm_id = $w[0]->id_pm;
        
        $str2 = "insert into ".$prefix."pm_recipients (id_pm, id_member, labels, bcc, is_read, is_new, deleted) values ('$pm_id', '$to', '-1', 0, 0, 1, 0)";
        Dictionary::executeQuery($str2);
        
    }

    public static function makeDictionaryAnalyse($message, $user, $messageid)
    {
        global $modSettings;
        $settings = $modSettings['mod_ban_word'];
        $forbSetting = $modSettings['mod_forb_word'];
        $forb_result = Dictionary::checkForbiddenWords($message);
        $ban_result = Dictionary::checkWithDictionary($message);

        $forbAnswer = false;
        
        if($forb_result > 0) {
        if($forbSetting == 0)
            $forbAnswer = Dictionary::WarnAction($forb_result, $user, 0);
        else if($forbSetting == 1)
            $forbAnswer = Dictionary::muteAction($forb_result, $user, 0);
        else if($forbSetting == 2)
            $forbAnswer = Dictionary::ModerateAction();
        else if($forbSetting == 3)
            $forbAnswer = Dictionary::DropAction($user);
        }
        
        $banAnswer = false;
        if($ban_result > 0) {
        if($settings == 0)
            $banAnswer = Dictionary::WarnAction ($ban_result,$user, 1);
        else if($settings == 1)
            $banAnswer = Dictionary::muteAction ($ban_result,$user, 1);
        else if($settings == 2)
            $banAnswer = Dictionary::ModerateAction();
        else if($settings == 3)
            $banAnswer = Dictionary::DropAction ($user);
        }
        
        if($banAnswer[0] || $forbAnswer[0])
        {
            if($settings == 2)
                $res = 3;
            else 
                $res = 1;
        }
        else 
            $res = 2;

        $message = $banAnswer[1]." ".$forbAnswer[1];
        return array($res, $message);
    }
    public static function addModerationLog($msg_id)
    {
        global $context;
        $prefix = $context['my_prefix'];
            $str2 = "select id_topic, id_board, id_member, poster_name as member, body from ".$prefix."messages where id_msg = '$msg_id'";
            Dictionary::connectToDatabase();
            $obj = Dictionary::getArray($str2);
            $id_topic = $obj[0]->id_topic;
            $id_board = $obj[0]->id_board;
            $id_member = $obj[0]->id_member;
            $member = $obj[0]->member;
            $body = $obj[0]->body;
            $t = time();
            $str2 = "insert into ".$prefix."log_reported (id_msg, id_topic, id_board, id_member, membername, subject, body, time_started, time_updated, num_reports, closed, ignore_all) values 
            ('$msg_id', '$id_topic', '$id_board', '$id_member', '$member', 'Auto Ostrzezenie', '$body', '$t', '$t', '1', '0', '0')";
            Dictionary::executeQuery($str2);
            
            $str3 = "select id_report from ".$prefix."log_reported where time_started = '$t'";
            $obj2 = Dictionary::getArray($str3);
            $id_rep = $obj2[0]->id_report;
            $str4 = "insert into ".$prefix."log_reported_comments (id_report, id_member, membername, comment, time_sent) values 
            ('$id_rep', '$id_member', '$member', 'Użytkownik złamał regulamin!', '$t')";
            Dictionary::executeQuery($str4);
    }

    public static function raportujZlamanieRegulaminu($user_id, $obligatoryBan)
    {
        global $context;
        global $modSettings;
        
        if($obligatoryBan == 1)
        {
                Dictionary::addBanToUser($user_id, 1);
                $mes = $mes." Otrzymałeś 14 dniowy zakaz dodawania postów.";
        } else {
            $maxWarnings = $modSettings['warning_watch'];
            $prefix = $context['my_prefix'];
            $str0 = "update ".$prefix."members set ban_counter = 0 where ban_counter is null ";
            $str = "update ".$prefix."members set ban_counter = ban_counter + 1 where id_member = '$user_id' ";
            $str2 = "select ban_counter as licznik from ".$prefix."members where id_member = '$user_id'";
            Dictionary::connectToDatabase();
            Dictionary::executeQuery($str0);
            Dictionary::executeQuery($str);
            $obj = Dictionary::getArray($str2);
            $warningsCounter = $obj[0]->licznik;
        
            if($warningsCounter > $maxWarnings)
            { 
                $mes = "To Twoje $warningsCounter ostrzeżenie!";
                Dictionary::addBanToUser($user_id, 1);
                $mes = $mes." Otrzymałeś 14 dniowy zakaz dodawania postów.";
            }
        }
        
        return $mes;
    }
    
    private static function addBanToUser($user, $type)
    {
        global $context;
        
        $prefix = $context['my_prefix'];
        Dictionary::connectToDatabase();
        $t = time();
        if($type == 1)
        {
            $t2 = $t+ (60*60*24*14);
            $str1 = "insert into ".$prefix."ban_groups (name,ban_time,expire_time,cannot_access, cannot_register, cannot_post, cannot_login)
                values ('ban_smf_$t', '$t', '$t2', 0, 0, 1, 0)";
        }
        else 
        {
            $t2 = $t+ (60*60*24*360);
            $str1 = "insert into ".$prefix."ban_groups (name,ban_time,expire_time,cannot_access, cannot_register, cannot_post, cannot_login)
            values ('ban_smf_$t', '$t', '$t2', 1, 1, 1, 1)";
        }
        Dictionary::executeQuery($str1);
        $str2 = "select id_ban_group from ".$prefix."ban_groups where ban_time = '$t'";
        $w = Dictionary::getArray($str2);
        $ban_id = $w[0]->id_ban_group;
        
        $str3 = "insert into ".$prefix."ban_items (id_ban_group, id_member) values ('$ban_id', '$user')";
        Dictionary::executeQuery($str3);
    }
    public static function getAllWordsFromDictionary()
    {
        global $context;
        $prefix = $context['my_prefix'];
        $str = "SELECT word FROM ".$prefix."dictionary_admin where active_word = '1' ";
        Dictionary::connectToDatabase();
        return Dictionary::getArray($str);
    }

    //database function
    private static function connectToDatabase()
    {
        global $context;
        mysql_connect($context['my_serwer'], $context['my_nazwa'], $context['my_haslo']);
        mysql_select_db($context['my_baza']);
    }
    
    private static function executeQuery($query)
    {
        mysql_query($query);
    }
    
    private static function getArray($str)
    {
        $result = mysql_query($str);
        $table = array();
        $i = 0;
        
        while ($obj = mysql_fetch_object($result))
        {
            $table[$i] = $obj;
            $i++;
        }
        
        if($i > 0)
            return $table;
        else            return null;
    }
    
}

?>
