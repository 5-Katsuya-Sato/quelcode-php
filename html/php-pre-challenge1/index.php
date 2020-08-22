<?php
for ($i = 1; $i <= 100; $i++) {
    //ここからコードを書く
    if(($i % 3 === 0) && ($i % 5 === 0)){
        print('3と5の倍数<br />');
    } elseif($i % 5 === 0){
        print('5の倍数<br />');
    } elseif($i % 3 === 0){
        print('3の倍数<br />');
    } else {
        print($i.'<br />');
    }
}
?>
