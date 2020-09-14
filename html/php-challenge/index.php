<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
	// ログインしている
	$_SESSION['time'] = time();

	$members = $db->prepare('SELECT * FROM members WHERE id=?');
	$members->execute(array($_SESSION['id']));
	$member = $members->fetch();
} else {
	// ログインしていない
	header('Location: login.php'); exit();
}

// 投稿を記録する
if (!empty($_POST)) {
	if ($_POST['message'] != '') {
		$message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_post_id=?, created=NOW()');
		$message->execute(array(
			$member['id'],
			$_POST['message'],
			$_POST['reply_post_id']
		));
		header('Location: index.php'); exit();
	}
}

//　goodボタン押された投稿IDが、既に投稿されている投稿のIDに存在しているかどうかをselectで確認。もしユーザーが10000とか指定してきたら、エラーが起きる。　そのIDが存在することを確認している　SELECTで検索すると当たり前にその数値が返ってくる。存在を確認するためだとしたら、他のやり方。COUNTを使う。countで行数を確認していたら、存在を確認していることがわかる。
if(isset($_REQUEST['good'])){
	$gd_posts = $db->prepare('SELECT COUNT(*) AS count FROM posts WHERE id=?');
	$gd_posts->execute(array($_REQUEST['good']));
	$gd_post = $gd_posts->fetch();
	// ↓　ログインしている人が既にurlのidの投稿をいいねしているかどうかを検索（1だったら、いいね済み。空だったら、未いいね）
	$count_of_gds = $db->prepare('SELECT COUNT(*) AS gdcount FROM goods WHERE post_id=? AND member_id=?');
	$count_of_gds->execute(array(
		$_REQUEST['good'],
		$member['id']
		));
	$count_of_gd = $count_of_gds->fetch();

	// 3つ目のifで使う
	$rt_post_ids = $db->prepare('SELECT rt_post_id FROM posts WHERE id=?');
	$rt_post_ids->execute(array($_REQUEST['good']));
	$rt_post_id = $rt_post_ids->fetch();
	
	$rtpostid_gd = (int)$rt_post_id['rt_post_id'];
	
	//直下のif文で、postsに、goodボタンが押された投稿と同じidを持つ投稿が存在することを確認する（セキュリティ目的）。その次に、入れ子ifで、goodボタンパラメータと同じidがgoodsテーブルに存在しているかを確かめる処理を書く
	if((int)$gd_post['count']===1) {
		// いいねされていなかったら、その$requestのidをpost_idにいれる
		if($rtpostid_gd === 0){
			$gdzero_cnts = $db->prepare('SELECT COUNT(*) AS count FROM goods WHERE post_id=? AND member_id=?');
			$gdzero_cnts->execute(array(
				$_REQUEST['good'],
				$member['id']
			));
			$gdzero_cnt = $gdzero_cnts->fetch();
			
			if(empty($gdzero_cnt['count'])) {
				$goods = $db->prepare('INSERT INTO goods SET post_id=?, member_id=?');
				$goods->execute(array(
					$_REQUEST['good'],
					$member['id']
				));
				header('Location: index.php'); exit();
			} else {
				$gdzero_dl = $db->prepare('DELETE FROM goods WHERE post_id=? AND member_id=?');
				$gdzero_dl->execute(array(
					$_REQUEST['good'],
					$member['id']
				));
				header('Location: index.php'); exit();
			}
		} else {
			$gdelse = $db->prepare('SELECT rt_post_id FROM posts WHERE id=?');
			$gdelse->execute(array($_REQUEST['good']));
			$gd_else = $gdelse->fetch();

			$gdelse_cnts = $db->prepare('SELECT COUNT(*) AS gdcnt FROM goods WHERE post_id=? AND member_id=?');
			$gdelse_cnts->execute(array(
				$gd_else['rt_post_id'],
				$member['id']
			));
			$gdelse_cnt = $gdelse_cnts->fetch();

			if(empty($gdelse_cnt['gdcnt'])){
				$gd_zero_ins = $db->prepare('INSERT INTO goods SET post_id=?, member_id=?');
				$gd_zero_ins->execute(array(
					$gd_else['rt_post_id'],
					$member['id']
				));
				header('Location: index.php'); exit();
			} else {
				$gd_delete = $db->prepare('DELETE FROM goods WHERE post_id=? AND member_id=?');
				$gd_delete->execute(array(
					$gd_else['rt_post_id'],
					$member['id']
				));
				header('Location: index.php'); exit();
			}
		}
	}
}


// 返信の場合 を参考に postテーブルの中身の取得
if (isset($_REQUEST['retweet'])) {
	// まずここ、$_REQUESTから受け取ったURLの値が、postsテーブルに存在しているかチェック（セキュリティ）
	$rt_posts = $db->prepare('SELECT COUNT(*) AS rtcount FROM posts WHERE id=?');
	$rt_posts->execute(array($_REQUEST['retweet']));
	$rt_post = $rt_posts->fetch();

	//URLの値がidのrt_post_idを取る
	$rtpostid_urls = $db->prepare('SELECT rt_post_id FROM posts WHERE id=?');
	$rtpostid_urls->execute(array($_REQUEST['retweet']));
	$rtpostid_url = $rtpostid_urls->fetch();

	$rtpostid_int = (int)$rtpostid_url['rt_post_id'];

	//  ログインしている人がRT済みかどうかの値を取得。　$_REQUEST['retweet']の値がpostsテーブルのrt_post_idに存在しているか（RTされているかどうか）検索
	$count_of_rts = $db->prepare('SELECT COUNT(*) AS retweetcount FROM posts WHERE rt_post_id=? AND rt_member_id=?');
	$count_of_rts->execute(array(
		$_REQUEST['retweet'],
		$member['id']
		));
	$count_of_rt = $count_of_rts->fetch();

	//postsテーブルにURLの値が存在する時（セキュリティ）
	if((int)$rt_post['rtcount']===1){

		$rt_message = $db->prepare('SELECT * FROM posts WHERE id=?');
		$rt_message->execute(array($_REQUEST['retweet']));
		$rt_table = $rt_message->fetch();

		if($rtpostid_int === 0){
			if(empty($count_of_rt['retweetcount'])){
				$retweets = $db->prepare('INSERT INTO posts SET message=?, member_id=?, rt_post_id=?, rt_member_id=?, rt_name=?, created=now()');
				$retweets->execute(array(
					$rt_table['message'],
					$rt_table['member_id'],
					$_REQUEST['retweet'],
					$member['id'],
					$member['name']
				));
				header('Location: index.php'); exit();
			} else {
				$dlretweets = $db->prepare('DELETE FROM posts WHERE rt_post_id=? AND rt_member_id=?');
				$dlretweets->execute(array(
					$_REQUEST['retweet'],
					$member['id']
				));
				header('Location: index.php'); exit();
			}
		} else{
			$count_rts = $db->prepare('SELECT COUNT(*) AS rtcnt FROM posts WHERE rt_post_id=? AND rt_member_id=?');
			$count_rts->execute(array(
				$rt_table['rt_post_id'],
				$member['id']
				));
			$count_rt = $count_rts->fetch();

			if(empty($count_rt['rtcnt'])) {
				$already_retweets = $db->prepare('INSERT INTO posts SET message=?, member_id=?, rt_post_id=?, rt_member_id=?, rt_name=?, created=now()');
				$already_retweets->execute(array(
					$rt_table['message'], 
					$rt_table['member_id'],
					$rtpostid_url['rt_post_id'],
					$member['id'],
					$member['name']
				));
				header('Location: index.php'); exit();
			} else {
				$dlrts = $db->prepare('DELETE FROM posts WHERE id=?');
				$dlrts->execute(array($_REQUEST['retweet']));
				header('Location: index.php'); exit();
			}
		}
	}
}

// 投稿を取得する
$page = $_REQUEST['page'];
if ($page == '') {
	$page = 1;
}
$page = max($page, 1);

// 最終ページを取得する
$counts = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$cnt = $counts->fetch();
$maxPage = ceil($cnt['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page - 1) * 5;
$start = max(0, $start);

$posts = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id ORDER BY p.created DESC LIMIT ?, 5');
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();

// 返信の場合
if (isset($_REQUEST['res'])) {
	$response = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
	$response->execute(array($_REQUEST['res']));

	$table = $response->fetch();
	$message = '@' . $table['name'] . ' ' . $table['message'];
}

// htmlspecialcharsのショートカット
function h($value) {
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// 本文内のURLにリンクを設定します
function makeLink($value) {
	return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)", '<a href="\1\2">\1\2</a>' , $value);
}
?>


<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>ひとこと掲示板</title>
	<script src="https://kit.fontawesome.com/58440588d4.js" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="style.css" />
</head>

<body>
<div id="wrap">
  <div id="head">
    <h1>ひとこと掲示板</h1>
  </div>
  <div id="content">
  	<div style="text-align: right"><a href="logout.php">ログアウト</a></div>
    <form action="" method="post">
      <dl>
        <dt><?php echo h($member['name']); ?>さん、メッセージをどうぞ</dt>
        <dd>
          <textarea name="message" cols="50" rows="5"><?php echo h($message); ?></textarea>
          <input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>" />
        </dd>
      </dl>
      <div>
        <p>
          <input type="submit" value="投稿する" />
        </p>
      </div>
    </form>

<?php
foreach ($posts as $post):
?>	
<div class="msg">
	<?php
		$post_a = (int)$post['rt_post_id'];
		if($post_a > 0):
	?>
	<p><?php print($post['rt_name']); ?>さんがRTしました</p>
	<?php
	endif;
	?>
    <img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>" />
    <p><?php echo makeLink(h($post['message'])); ?><span class="name">（<?php echo h($post['name']); ?>）</span>[<a href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]</p>
	<?php
		//RTされている投稿のIDが、今のターンの投稿IDと同じ行で、ログインしている人にRTされているか検索
		$rt_counts = $db->prepare('SELECT COUNT(*) AS countrt FROM posts WHERE rt_post_id=? AND rt_member_id=?');
		$rt_counts->execute(array(
			$post['id'],
			$member['id']
		));
		$rt_count = $rt_counts->fetch();
		
		//ログインしている人による、このターンのrt_post_id($post['rt_post_id']) と一致するrt_post_idのカウントを取る
		$count_rpis = $db->prepare('SELECT COUNT(*) AS countrpi FROM posts WHERE rt_post_id=? AND rt_member_id=?');
		$count_rpis->execute(array(
			$post['rt_post_id'],
			$member['id']
		));
		$count_rpi = $count_rpis->fetch();
		// DBを読み取って、色を変えるためのコード　ログインしている人が今のターンの投稿をRTしていたら　(色を変化させてあげる)　　ログインしている人が、(rt_post_idが0じゃない時)今のターンのrt_post_idの投稿をRTしていたら
		if ((int)$rt_count['countrt'] >= 1):
		?>
		<a href="index.php?retweet=<?php echo h($post['id'],ENT_QUOTES);?>"><i class="fas fa-retweet" style="color:green;"></i></a>
		<?php
		elseif($post['rt_member_id'] === $member['id']):
		?>
		<a href="index.php?retweet=<?php echo h($post['id'],ENT_QUOTES);?>"><i class="fas fa-retweet" style="color:green;"></i></a>
		<?php
		elseif(!empty($count_rpi['countrpi'])):
		?>
		<a href="index.php?retweet=<?php echo h($post['id'],ENT_QUOTES);?>"><i class="fas fa-retweet" style="color:green;"></i></a>
		<?php
		else:
		?>
		<a href="index.php?retweet=<?php echo h($post['id'],ENT_QUOTES);?>"><i class="fas fa-retweet" style="color:gray;"></i></a>
		<?php
		endif;
		?>		
		<!-- RTの回数表示のコーディング -->
		<?php
		$post_ct = (int)$post['rt_post_id'];
		if($post_ct === 0){
			$total_rts = $db->prepare('SELECT COUNT(*) AS totalrt FROM posts WHERE rt_post_id=?');
			$total_rts->execute(array(
				$post['id']
			));
			$total_rt = $total_rts->fetch();
			print($total_rt['totalrt']);	
		} else {
			$total_retweets = $db->prepare('SELECT COUNT(*) AS totalretweet FROM posts WHERE rt_post_id=?');
			$total_retweets->execute(array(
				$post['rt_post_id']
			));
			$total_retweet = $total_retweets->fetch();
			print($total_retweet['totalretweet']);
		}
	?>
	<?php
		$gd_counts = $db->prepare('SELECT COUNT(*) AS countgd FROM goods WHERE post_id=? AND member_id=?');
		$gd_counts->execute(array(
			$post['id'],
			$member['id']
		));
		$gd_count = $gd_counts->fetch();

		$gdrt_counts = $db->prepare('SELECT COUNT(*) AS gdrtcnt FROM goods WHERE post_id=? AND member_id=?');
		$gdrt_counts->execute(array(
			$post['rt_post_id'],
			$member['id']
		));
		$gdrt_count = $gdrt_counts->fetch();

		if($gd_count['countgd']==1):
	?>
		<a href="index.php?good=<?php echo h($post['id'],ENT_QUOTES);?>"><i class="fas fa-heart" style="color:red;"></i></a>
		<?php 
		elseif($gdrt_count['gdrtcnt']):
		?>
		<a href="index.php?good=<?php echo h($post['id'],ENT_QUOTES);?>"><i class="fas fa-heart" style="color:red;"></i></a>
		<?php 
		else:
		?>
		<a href="index.php?good=<?php echo h($post['id'],ENT_QUOTES);?>"><i class="far fa-heart" style="color:gray;"></i></a>
		<?php
		endif;
		?>
		<!-- 回数数えて表示する処理 -->
		<?php
		if($post_ct === 0){
			$total_gds = $db->prepare('SELECT COUNT(*) AS totalgd FROM goods WHERE post_id=?');
			$total_gds->execute(array(
				$post['id']
			));
			$total_gd = $total_gds->fetch();
			print($total_gd['totalgd']);
		} else {
			$total_goods = $db->prepare('SELECT COUNT(*) AS totalgood FROM goods WHERE post_id=?');
			$total_goods->execute(array(
				$post['rt_post_id']
			));
			$total_good = $total_goods->fetch();
			print($total_good['totalgood']);
		}
		?>

	<p class="day"><a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a>
		<?php
		if ($post['reply_post_id'] > 0):
		?>
		<a href="view.php?id=<?php echo
		h($post['reply_post_id']); ?>">
		返信元のメッセージ</a>
		<?php
		endif;
		?>
		<?php
		if ($_SESSION['id'] == $post['member_id']):
		?>
		[<a href="delete.php?id=<?php echo h($post['id']); ?>"
		style="color: #F33;">削除</a>]
		<?php
		endif;
		?>
    </p>
    </div>
<?php
endforeach;
?>
<ul class="paging">
<?php
if ($page > 1) {
?>
<li><a href="index.php?page=<?php print($page - 1); ?>">前のページへ</a></li>
<?php
} else {
?>
<li>前のページへ</li>
<?php
}
?>
<?php
if ($page < $maxPage) {
?>
<li><a href="index.php?page=<?php print($page + 1); ?>">次のページへ</a></li>
<?php
} else {
?>
<li>次のページへ</li>
<?php
}
?>
</ul>
  </div>
</div>
</body>
</html>
