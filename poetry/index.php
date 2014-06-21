<?php
  /*  require_once 'config.php';
    require_once CORE_PATH . "/sys.php";

	sys::init();
*/
	
class NumPP{
    /**
     * All single chinese figures
     *
     * @var array
     */
    var $chFigure = array("零", "壹", "貳", "三", "肆", "伍", "陸", "柒", "捌", "玖");
    /**
     * Small orders of magnitude
     *
     * @var array
     */
    var $chSmallUnit = array("拾", "佰", "仟");
    /**
     * Big orders of magnitude
     *
     * @var array
     */
    var $chBigUnit = array("萬", "億");

    /**
     * Constructor
     */
    function NumPP(){
    }

    /**
     * The main method by which an arabic numeral is converted into a chinese figure
     */
    function arabic2chinese($num){
        $chFgr = ''; //The unsigned chinese figure converted by this method
        $sign = ''; //The sign of chinese figure
        $intPart = '';
        $decPart = '';
        // This class does not validate the number given , but converts it into a string directly
        $num = strval($num);
        // Get the sign
        if (substr($num, 0, 1) == '-'){
            $sign = '負';
            $num = substr($num, 1);
        }
        // Split a floating number into two parts , integer part and decimal part
        $numInfo = explode(".", $num);
        $intPart = $numInfo[0];
        $decPart = $numInfo[1];
        // Transform the integer part
        $tmp = $intPart;
        $flagBigUnit = -1; //Indicate which big unit is going to be used
        while (strlen($tmp)){
            $seg = substr($tmp, - (strlen($tmp) <4 ? strlen($tmp): 4)); //Get a new segment from the end to the begining
            $chSeg = $this -> _parseSeg($seg);
            $chSegWithBigUnit = $chSeg . (strlen($chSeg) == 0 ? '' : $this -> chBigUnit[$flagBigUnit]); 
//Deside which big unit is to be used here
            $withZero = false; 
//Deside whether a chinese figure 0 should be added by the end of the segment that was just transformed
            if (substr($seg, -1) == '0' && strlen($chFgr) != 0 && substr($chFgr, 0, 2) != '零'){
                $withZero = true;
            }
            $chFgr = $chSegWithBigUnit . ($withZero ? '零' : '') . $chFgr;
            $flagBigUnit = $flagBigUnit == -1 ? 0 : ($flagBigUnit == 0 ? 1 : 0); 
//Switch between the two big units
            $tmp = substr($tmp, 0, - (strlen($tmp) <4 ? strlen($tmp) : 4)); 
//Truncate the integer part
        }
        // In case that the integer part is zero
        if (strlen($chFgr) == 0){
            $chFgr = '零';
        }
        // Transform the decimal part
        if (strlen($decPart)> 0){
            $chFgr .= '點';
            for($i = 0;$i <strlen($decPart);$i++){
                $chFgr .= $this -> chFigure[$decPart[$i]];
            }
        }

        return $sign . $chFgr;
    }

    /**
     * A big integer will be split into some segments with a length of 4 bits or less ,
     * this method transforms every segment into a chinese figure
     */
    function _parseSeg($seg){
        $chSeg = '';

        $len = strlen($seg);
        $formerIsZero = false; //Mark if the former bit is zero
        for($i = 0;$i <$len;$i++){
            if (substr($seg, -1) == '0'){ // If the current bit is zero ...
                if ($i != 0){ // If the current bit is not in the unit position ...
                    $chSeg = $formerIsZero ? $chSeg : '零' . $chSeg;
                }
                $formerIsZero = true;
            }else{ // If the current bit is not zero ...
                $chSeg = $this -> chFigure[substr($seg, -1)] . $this -> chSmallUnit[$i-1] . $chSeg;
                $formerIsZero = false;
            }
            $seg = substr($seg, 0, -1);
        }

        return $chSeg;
    }
}



$npp = new NumPP();
echo $npp->arabic2chinese('103');