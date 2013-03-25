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
    //put your code here
    
    public static function checkWithDictionary($message)
    {
        $word  = "chuj";
        Dictionary::connectToDatabase();
        
        if (strpos($message, $word) !== false) {
            return false;
        }
        return true;
    }
    
    
    public static function raportujZlamanieRegulaminu($user, $prefix)
    {
        $str = "update ".$prefix."members set ban_counter = 7 where ban_counter is null";

        Dictionary::connectToDatabase();
        
        Dictionary::executeQuery($str);
        
        $licznik = 1;
        
        return "Twoj post jest niez $db_server, $db_user, $db_passwd godny z regulaminem! To $licznik ostrzeżenie! $str";
    }
    
    private static function addBanToUser($user, $timeInSeconds)
    {
        
    }
    
    
    //do panelu
    public static function addWordToDictionary($word, $active)
    {
        
    }
    //do panelu
    public static function getAllWordsFromDictionary()
    {
        
    }
    //do panelu
    public static function changeActivePropertyOfWord($word)
    {
        
    }
    
    private static function connectToDatabase()
    {
        global $db_server, $db_user, $db_passwd, $db_name, $db_show_debug, $ssi_db_user, $ssi_db_passwd;
        mysql_connect($db_server, $db_user, $db_passwd);
        mysql_select_db($db_name);
    }
    
    private static function executeQuery($query)
    {
        mysql_query($query);
    }
    
}

?>
