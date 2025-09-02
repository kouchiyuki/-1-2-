<?php
$dbh =new PDO('mysql:host=mysql2;dbname=example_db', 'root', '');

if (isset($_POST['body'])) {
  $image_filename = null;

  if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
    // 画像であるか確認
    if (preg_match('/^image\//', $_FILES['image']['type']) !== 1) {
      header("HTTP/1.1 302 Found");
      header("Location: ./bbsimagetest.php");
      return;
    }

    // 5MBより大きい画像は拒否
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
      echo "アップロードできる画像は5MB以下です。";
      exit;
    }

    // 拡張子取得とファイル名生成
    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $image_filename = time() . bin2hex(random_bytes(25)) . '.' . $extension;
    $filepath = '/var/www/upload/image/' . $image_filename;

    move_uploaded_file($_FILES['image']['tmp_name'], $filepath);
  }

  // データベースに保存
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
  $insert_sth->execute([
    ':body' => $_POST['body'],
    ':image_filename' => $image_filename,
  ]);

  // リダイレクト
  header("HTTP/1.1 302 Found");
  header("Location: ./bbsimagetest2.php");
  return;
}

// 投稿の取得
$select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at DESC');
$select_sth->execute();
?>

<form method="POST" action="./bbsimagetest.php" enctype="multipart/form-data">
  <textarea name="body" required></textarea>
  <div style="margin: 1em 0;">
    <input type="file" accept="image/*" name="image">
  </div>
  <button type="submit">送信</button>
</form>

<script>
const MAX_SIZE_MB = 5;

document.getElementById('bbsForm').addEventListener('submit', async function(event) {
  event.preventDefault();

  const form = event.target;
  const fileInput = document.getElementById('imageInput');
  const file = fileInput.files[0];

  if (file && file.size > MAX_SIZE_MB * 1024 * 1024) {
    try {
      const resizedBlob = await resizeImageToMaxSize(file, MAX_SIZE_MB);
      if (!resizedBlob) {
        alert('画像の縮小に失敗しました。');
        return;
      }

      const formData = new FormData(form);
      formData.set('image', resizedBlob, file.name);

      const res = await fetch(form.action, {
        method: form.method,
        body: formData,
      });

      if (res.ok) {
        window.location.reload();
      } else {
        alert('送信に失敗しました');
      }
    } catch (err) {
      alert('画像処理中にエラーが発生しました');
      console.error(err);
    }
  } else {
    form.submit();
  }
});

function resizeImageToMaxSize(file, maxMB) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    const url = URL.createObjectURL(file);

    img.onload = () => {
      URL.revokeObjectURL(url);

      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');

      let width = img.width;
      let height = img.height;

      const MAX_DIMENSION = 1920;

      if (width > MAX_DIMENSION || height > MAX_DIMENSION) {
        const scale = Math.min(MAX_DIMENSION / width, MAX_DIMENSION / height);
        width = Math.floor(width * scale);
        height = Math.floor(height * scale);
      }

      canvas.width = width;
      canvas.height = height;
      ctx.drawImage(img, 0, 0, width, height);

      const qualitySteps = [0.9, 0.8, 0.7, 0.6, 0.5];

      function tryBlob(qualityIndex = 0) {
        canvas.toBlob(blob => {
          if (!blob) {
            reject(new Error('Blob生成に失敗'));
            return;
          }
          if (blob.size <= maxMB * 1024 * 1024 || qualityIndex === qualitySteps.length - 1) {
            resolve(blob);
          } else {
            tryBlob(qualityIndex + 1);
          }
        }, file.type, qualitySteps[qualityIndex]);
      }
      tryBlob();
    };

    img.onerror = () => {
      URL.revokeObjectURL(url);
      reject(new Error('画像読み込みエラー'));
    };

    img.src = url;
  });
}
</script>
<hr>

<?php foreach($select_sth as $entry): ?>
  <dl style="margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">
    <dt>ID</dt>
    <dd>
    <a href="#post-<?= htmlspecialchars($entry['id']) ?>"><?= htmlspecialchars($entry['id']) ?></a>
    <a href="./enshu1_view.php?id=<?= htmlspecialchars($entry['id']) ?>">詳細</a>
    </dd>
    <dt>日時</dt>
    <dd><?= $entry['created_at'] ?></dd>
    <dt>内容</dt>
    <dd>
      <?= nl2br(htmlspecialchars($entry['body'])) ?>
      <?php if (!empty($entry['image_filename'])): ?>
        <div>
          <img src="/image/<?= htmlspecialchars($entry['image_filename']) ?>" style="max-height: 10em;">
        </div>
      <?php endif; ?>
    </dd>
  </dl>
<?php endforeach ?>


