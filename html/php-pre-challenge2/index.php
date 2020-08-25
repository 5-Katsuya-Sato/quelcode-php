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


/*
<?php
//explode ( 区切り文字、文字列 )　　分割した文字列の配列が返される
$array = explode(',', $_GET['array']);
// 修正はここから
//この式は意味不明↓　　このsorted_aryは配列であることを示している
$sorted_ary = [];
//$countにゼロを代入し、countゼロから$array配列のcount合計(12)より小さい間に繰り返し処理
$count = 0;
while($count < count($array)) {
//$tmpにnull(値を持たない)を代入　　変数を入れ替えるときにどうするか、aとbの値を入れ替えたいときに、もう一つ変数がないとa=bにしたときに、既にbはあの値になっている
 $tmp = null;
//foreachで、配列の中身を最後まで取り出し
 foreach ( $array as $key => $val ) {
//in_arrayファンクションで、$val（探す値）がsorted_aryにあるかどうかを確認。ある場合は残りの処理を飛ばして、次のループへ。
   if (in_array($val, $sorted_ary)) {
       continue;
   }
//$tmpがnullであれば、$valの値を$tmpに代入
   if ($tmp === null) {
     $tmp = $val;
   } else {
//パラメータ($tmp, $val)の中で最も小さい値を$tmpに代入
     $tmp = min($tmp, $val);
   }
 }
 //算出した、最も小さい値を$sorted_ary[]に代入
 $sorted_ary[] = $tmp;
 $count++;
}
//ここでは、最初の配列$arrayの最小値から順に新しい配列$sorted_aryに入れています。
// 修正はここまで

echo "<pre>";
print_r($sorted_ary);
echo "</pre>";
?>*/