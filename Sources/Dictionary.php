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
        //Checking content of post with dictionary
        Dictionary::connectToDatabase();
        $words = Dictionary::getAllWordsFromDictionary();
        $counter = 0;
        foreach ($words as $w) {
            if (strpos($message, $w->word) !== false) {
                $counter++;
            }
        }
        return $counter;
    }
    
    
    public static function raportujZlamanieRegulaminu($user_id, $ilosc_bledow)
    {
        global $context;
        $prefix = $context['my_prefix'];
        $str = "update ".$prefix."members set ban_counter = ban_counter + 1 where id_member = '$user_id' ";
        
        $str2 = "select ban_counter as licznik from ".$prefix."members where id_member = '$user_id'";
        Dictionary::connectToDatabase();
        Dictionary::executeQuery($str);
        $obj = Dictionary::getArray($str2);
        $licznik = $obj[0]->licznik;
        $mes = "Twoj post jest w $ilosc_bledow miejscach niezgodny z regulaminem! To Twoje $licznik ostrzeżenie!";
        if($licznik > 2) //tu nalezy uzyc zmiennej z panelu administracyjnego
        {
            
            Dictionary::addBanToUser($user_id);
            $mes = $mes." Otrzymałeś 14 dniowy zakaz dodawania postów.";
        }
        return $mes;
    }
    
    private static function addBanToUser($user)
    {
        global $context;
        $t = time();
        $t2 = $t+ (60*60*24*14);
        $prefix = $context['my_prefix'];
        
        Dictionary::connectToDatabase();
        
        $str1 = "insert into ".$prefix."ban_groups (name,ban_time,expire_time,cannot_access, cannot_register, cannot_post, cannot_login)
            values ('ban_from_autoSfm$t', '$t', '$t2', 0, 0, 1, 0)";
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
    
    
    
    
    //do panelu
    public static function addWordToDictionary($word)
    {
        global $context;
        $prefix = $context['my_prefix'];
        $str = "insert into ".$prefix."dictionary_admin (word, word_status, active_word) values ('$word', '1','1') ";
        Dictionary::executeQuery($str);
    }
    //do panelu
    public static function getAllWordsFromDictionaryToEdit()
    {
        global $context;
        $prefix = $context['my_prefix'];
        $str = "SELECT word FROM ".$prefix."dictionary_admin ";
        Dictionary::connectToDatabase();
        return Dictionary::getArray($str);
    }
    //do panelu
    public static function changeActivePropertyOfWord($word)
    {
        global $context;
        $prefix = $context['my_prefix'];
        $str = "update ".$prefix."dictionary_admin set active_word = 1 - active_word where word = '$word' ";
        Dictionary::executeQuery($str);
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
