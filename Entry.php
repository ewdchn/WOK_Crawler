<?php
/**
 * Created by PhpStorm.
 * User: ewdchn
 * Date: 10/27/13
 * Time: 6:34 PM
 */

class Entry
{
    private $attrArr;
    private $ID;

    function __construct($_p)
    {
        if (!isset($_p->title)) return false;
        global $categoriesArr;
        $this->attrArr = array();
        $tmp = &$this->attrArr;
        $tmp['citation']=array();
        $tmp['authors']=array();
        foreach ($_p as $key => $value) {
            //author
            if ($key == 'author') {
                $tmp['authors'][] = trim($value, ' ');
                //Source
            } else if ($key == "Source") {
                $tmp['source'] = trim(strtolower((string)$value));
                if (isset($categoriesArr[$tmp['source']])) $tmp['categories'] = $categoriesArr[$tmp['source']];
                //other values
            } else {
                if (($v = (trim($value))))
                    $tmp[(string)$key] = strtolower($v);
            }
        }
//        $this->genSQL();
//        print_r($this->attrArr);
    }

    public function getAttr()
    {
        return $this->attrArr;
    }
    public function addCitation($id){
        $this->attrArr['citation'][$id]=true;
    }
    public function setID($_id)
    {
        $this->ID = $_id;
    }
    public function getID(){
        return $this->ID;
    }
    public function merge($_p)
    {


    }
    public function genSQL(){
        $paper = $this->attrArr;
        $tmpttl = addslashes($paper['title']);
        $tmpaut = addslashes(myserialize($paper['authors']));
        $tmpDOI = isset($paper['DOI']) ? addslashes($paper['DOI']) : "";
        $tmpUT = isset($paper['UT']) ? addslashes($paper['UT']) : "";
        $tmpSrc = isset($paper['source']) ? addslashes($paper['source']) : "";
        $tmpCat = isset($paper['categories']) ? addslashes($paper['categories']) : "";
        $tmpcit = isset($paper['citations']) ? addslashes(myserializeAlt($paper['citations'])) : "";
        $sql = "INSERT INTO all_papers (id,title,author,DOI,UT,source,categories,citation)VALUES
            ($this->ID,'$tmpttl','$tmpaut','$tmpDOI','$tmpUT','$tmpSrc','$tmpCat','$tmpcit')";
//        echo $sql."\n";
        return $sql;
    }
    public static function processAuthor(&$_author)
    {
        if (count(preg_split("/[,\s]+/", $_author, 2)) < 2) {
            $_author = strtoupper($_author);
            return strtoupper($_author);
        }
        list($firstName, $lastName) = preg_split("/[,\s]+/", $_author, 2);
        $lastName = trim($lastName);
        $firstName = trim($firstName) . ",";
        $lastNameToken = preg_split('/[\s.]+/', $lastName);
        if (count($lastNameToken) == 1) {
            if (preg_match("/[A-Z]+/", $lastNameToken[0], $tok))
                $firstName .= $tok[0];
        } else {
            foreach ($lastNameToken as $tok) {
                empty($tok) ? : $firstName .= $tok[0];
//            $firstName.=substr($tok, 0, 1);
            }
        }
        $_author = strtoupper($firstName);
        return strtoupper($firstName);
    }

    public static function  authorMatch($_autArr1, $_autArr2)
    {
        $ctr = 0;
        array_walk($_autArr1, "Entry::rocessAuthor");
        array_walk($_autArr2, "Entry::processAuthor");
        foreach ($_autArr1 as $auth1) {
            foreach ($_autArr2 as $auth2) {
                if ($auth1 == $auth2) {
                    $ctr++;
                    break;
                }
            }
        }
        if ($ctr >= min(array(count($_autArr1), count($_autArr2))))
            return true;
        else
            return false;
    }
} 