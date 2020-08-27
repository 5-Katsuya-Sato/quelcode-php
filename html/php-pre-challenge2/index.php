<?php
$array = explode(',', $_GET['array']);

// 修正はここから
for ($i = 0; $i < count($array); $i++) {
    $target= count($array)-$i;
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