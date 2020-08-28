<?php
$array = explode(',', $_GET['array']);

// 修正はここから
$length = count($array);
for ($i = 0; $i < $length; $i++) {
    $target= $length-$i;
    for($j=1; $j<$target; $j++){
        if($array[$j]<$array[$j-1]){
            $tmp=$array[$j];
            $array[$j]=$array[$j-1];
            $array[$j-1]=$tmp;
        }
    }
}
// 修正はここまで
echo "<pre>";
print_r($array);
echo "</pre>";
