<?php
$dbh = new PDO('mysql:host=mysql2;dbname=example_db', 'root', '');

// GETパラメータにidがあるかチェック
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    echo '不正なアクセスです。';
    exit;
}
$id = (int)$_GET['id'];

// IDに対応する投稿を取得
$select_sth = $dbh->prepare('SELECT * FROM bbs_entries WHERE id = :id');
$select_sth->execute([':id' => $id]);
$entry = $select_sth->fetch();

if (!$entry) {
    echo '投稿が見つかりませんでした。';
    exit;
}

// レスアンカー >>1 をリンクに変換する関数
function convertAnchor($text) {
    $escaped = htmlspecialchars($text);
    $linked = preg_replace_callback('/&gt;&gt;(\d+)/', function($matches) {
        $id = htmlspecialchars($matches[1]);
        return "<a href=\"./enshu1_view.php?id={$id}\">>>{$id}</a>";
    }, $escaped);
    return nl2br($linked);
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>投稿詳細 (ID: <?= htmlspecialchars($entry['id']) ?>)</title>
  <style>
    body {
      font-family: sans-serif;
      padding: 1em;
      max-width: 800px;
      margin: auto;
    }
    img {
      max-width: 100%;
      height: auto;
    }
  </style>
</head>
<body>
  <h1>投稿詳細</h1>

  <dl style="margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">
    <dt>ID</dt>
    <dd><?= $entry['id'] ?></dd>

    <dt>日時</dt>
    <dd><?= $entry['created_at'] ?></dd>

    <dt>内容</dt>
    <dd><?= convertAnchor($entry['body']) ?></dd>

    <?php if (!empty($entry['image_filename'])): ?>
      <dt>画像</dt>
      <dd><img src="/image/<?= htmlspecialchars($entry['image_filename']) ?>" alt="投稿画像"></dd>
    <?php endif; ?>
  </dl>

  <p><a href="./finalassignment2.php">← 一覧に戻る</a></p>
</body>
</html>
